<?php

namespace Tests\Feature;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class ProductIntegrityMigrationTest extends TestCase
{
    private const MIGRATION = '2026_10_06_000000_restore_product_foreign_keys_on_sqlite.php';

    private const REFERENCES = [
        'id_categoria' => 'categorias',
        'id_cor' => 'cores',
        'id_material' => 'materiais',
        'id_fornecedor' => 'fornecedores',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        // Do not use RefreshDatabase: a schema rebuild must control the outer
        // transaction, and this connection must never resolve to the user's DB.
        config(['database.connections.product_integrity_test' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]]);
        DB::setDefaultConnection('product_integrity_test');
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
    }

    protected function tearDown(): void
    {
        DB::purge('product_integrity_test');

        parent::tearDown();
    }

    public function test_fresh_migrations_restore_all_foreign_keys_and_reject_invalid_references(): void
    {
        $this->migrate();
        $keys = collect(Schema::getForeignKeys('produtos'))->keyBy(fn (array $key): string => $key['columns'][0]);

        foreach (self::REFERENCES as $column => $parent) {
            $this->assertSame($parent, $keys[$column]['foreign_table']);
            $this->assertSame(['id'], $keys[$column]['foreign_columns']);
            $this->assertSame('set null', strtolower($keys[$column]['on_delete']));

            $this->assertRejectedByDatabase(
                fn () => DB::table('produtos')->insert($this->productData() + [$column => 999999]),
                'FOREIGN KEY constraint failed'
            );
        }

        $this->assertDatabaseCount('produtos', 0);
        $this->assertSame(1, (int) DB::scalar('PRAGMA foreign_keys'));
    }

    public function test_deleting_each_parent_nulls_the_reference_without_deleting_the_product(): void
    {
        $this->migrate();
        $references = [];

        foreach (self::REFERENCES as $column => $parent) {
            $references[$column] = DB::table($parent)->insertGetId(['nome' => $parent]);
        }

        $productId = DB::table('produtos')->insertGetId($this->productData() + $references);

        foreach (self::REFERENCES as $column => $parent) {
            DB::table($parent)->where('id', $references[$column])->delete();

            $this->assertDatabaseHas('produtos', ['id' => $productId, $column => null]);
            $this->assertDatabaseCount('produtos', 1);
        }
    }

    public function test_upgrade_preserves_product_sale_check_index_trigger_and_autoincrement(): void
    {
        $this->migrate(beforeCorrection: true);
        $this->assertSame([], Schema::getForeignKeys('produtos'));
        DB::statement("ALTER TABLE produtos ADD COLUMN codigo_auditoria TEXT DEFAULT 'original' CHECK(codigo_auditoria <> 'invalid')");
        DB::statement('CREATE INDEX produtos_nome_auditoria ON produtos (nome)');
        DB::statement('CREATE TABLE produto_audit_logs (nome TEXT)');
        DB::statement('CREATE TRIGGER produtos_nome_auditoria_trigger AFTER UPDATE OF nome ON produtos BEGIN INSERT INTO produto_audit_logs (nome) VALUES (NEW.nome); END');
        Schema::table('vendas', function (Blueprint $table): void {
            $table->foreign('id_produto')->references('id')->on('produtos')->cascadeOnDelete();
        });

        $categoryId = DB::table('categorias')->insertGetId(['nome' => 'Categoria existente']);
        $productId = DB::table('produtos')->insertGetId($this->productData() + ['id_categoria' => $categoryId]);
        DB::table('produtos')->insert($this->productData() + ['id' => 100]);
        DB::table('produtos')->where('id', 100)->delete();
        $saleId = DB::table('vendas')->insertGetId([
            'id_produto' => $productId,
            'nome' => 'Venda preservada',
            'comprador' => 'Cliente',
            'valor_unitario' => 30,
            'valor_compra_total' => 10,
            'valor_venda_total' => 30,
            'data_hora' => '2026-10-06 12:00:00',
        ]);
        $productBefore = (array) DB::table('produtos')->find($productId);
        $saleBefore = (array) DB::table('vendas')->find($saleId);

        $this->migration()->up();

        $this->assertSame($productBefore, (array) DB::table('produtos')->find($productId));
        $this->assertSame($saleBefore, (array) DB::table('vendas')->find($saleId));
        $this->assertCount(4, Schema::getForeignKeys('produtos'));
        $this->assertContains('produtos_nome_auditoria', array_column(Schema::getIndexes('produtos'), 'name'));
        $this->assertSame(101, DB::table('produtos')->insertGetId($this->productData()));
        $this->assertRejectedByDatabase(
            fn () => DB::table('produtos')->where('id', $productId)->update(['codigo_auditoria' => 'invalid']),
            'CHECK constraint failed'
        );
        DB::table('produtos')->where('id', $productId)->update(['nome' => 'Nome alterado']);
        $this->assertDatabaseHas('produto_audit_logs', ['nome' => 'Nome alterado']);
        $this->assertSame([], DB::select('PRAGMA foreign_key_check'));
        $this->assertSame(1, (int) DB::scalar('PRAGMA foreign_keys'));
    }

    public function test_orphans_abort_upgrade_without_changing_data_or_schema(): void
    {
        $this->migrate(beforeCorrection: true);
        $references = array_fill_keys(array_keys(self::REFERENCES), 999999);
        $productId = DB::table('produtos')->insertGetId($this->productData() + $references);
        $before = (array) DB::table('produtos')->find($productId);
        $definition = DB::scalar("SELECT sql FROM sqlite_master WHERE name = 'produtos'");

        try {
            $this->migration()->up();
            $this->fail('A migration deveria rejeitar vínculos órfãos.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('nenhum vínculo foi removido', $exception->getMessage());
            $this->assertStringContainsString('php artisan migrate', $exception->getMessage());

            foreach (array_keys(self::REFERENCES) as $column) {
                $this->assertStringContainsString($column, $exception->getMessage());
            }
        }

        $this->assertSame($before, (array) DB::table('produtos')->find($productId));
        $this->assertSame($definition, DB::scalar("SELECT sql FROM sqlite_master WHERE name = 'produtos'"));
        $this->assertSame([], Schema::getForeignKeys('produtos'));
        $this->assertFalse(Schema::hasTable('produtos_integrity_tmp'));
        $this->assertSame(1, (int) DB::scalar('PRAGMA foreign_keys'));

        // Explicit, reviewed remediation allows a subsequent migration attempt.
        DB::table('produtos')->where('id', $productId)->update(array_fill_keys(array_keys(self::REFERENCES), null));
        $this->migration()->up();
        $this->assertCount(4, Schema::getForeignKeys('produtos'));
    }

    public function test_upgrade_and_rollback_preserve_views_and_triggers_owned_by_other_tables(): void
    {
        $this->migrate(beforeCorrection: true);
        $productId = DB::table('produtos')->insertGetId($this->productData());
        DB::statement('CREATE VIEW produtos_catalogo AS SELECT id, nome FROM produtos');
        DB::statement('CREATE VIEW produtos_catalogo_encadeado AS SELECT id, nome FROM produtos_catalogo');
        DB::statement('CREATE TRIGGER produtos_catalogo_nome INSTEAD OF UPDATE OF nome ON produtos_catalogo BEGIN UPDATE produtos SET nome = NEW.nome WHERE id = OLD.id; END');
        DB::statement('CREATE TABLE produto_audit_logs (nome TEXT)');
        DB::statement('CREATE TRIGGER vendas_produto_auditoria AFTER INSERT ON vendas BEGIN INSERT INTO produto_audit_logs (nome) SELECT nome FROM produtos WHERE id = NEW.id_produto; END');
        DB::statement('PRAGMA legacy_alter_table = OFF');

        foreach (['up', 'down', 'up'] as $index => $direction) {
            $this->migration()->{$direction}();
            $name = 'Nome após migração '.$index;
            DB::table('produtos_catalogo')->where('id', $productId)->update(['nome' => $name]);
            DB::table('vendas')->insert(['id_produto' => $productId]);

            $this->assertSame($name, DB::table('produtos_catalogo_encadeado')->where('id', $productId)->value('nome'));
            $this->assertDatabaseHas('produto_audit_logs', ['nome' => $name]);
            $this->assertCount($direction === 'up' ? 4 : 0, Schema::getForeignKeys('produtos'));
            $this->assertSame(0, (int) DB::scalar('PRAGMA legacy_alter_table'));
            $this->assertSame(1, (int) DB::scalar('PRAGMA foreign_keys'));
        }

        $this->assertDatabaseCount('vendas', 3);
        $this->assertDatabaseCount('produto_audit_logs', 3);
    }

    public function test_failure_after_dropping_original_table_rolls_back_and_restores_connection_flags(): void
    {
        $this->migrate(beforeCorrection: true);
        $productId = DB::table('produtos')->insertGetId($this->productData());
        $before = (array) DB::table('produtos')->find($productId);
        $definition = DB::scalar("SELECT sql FROM sqlite_master WHERE name = 'produtos'");
        DB::statement('PRAGMA legacy_alter_table = OFF');
        DB::connection()->beforeExecuting(function (string $query): void {
            if ($query === 'ALTER TABLE "produtos_integrity_tmp" RENAME TO "produtos"') {
                throw new RuntimeException('Simulated rename failure');
            }
        });

        try {
            $this->migration()->up();
            $this->fail('A falha simulada deveria interromper a migration.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated rename failure', $exception->getMessage());
        }

        $this->assertSame($before, (array) DB::table('produtos')->find($productId));
        $this->assertSame($definition, DB::scalar("SELECT sql FROM sqlite_master WHERE name = 'produtos'"));
        $this->assertFalse(Schema::hasTable('produtos_integrity_tmp'));
        $this->assertSame([], Schema::getForeignKeys('produtos'));
        $this->assertSame(0, (int) DB::scalar('PRAGMA legacy_alter_table'));
        $this->assertSame(1, (int) DB::scalar('PRAGMA foreign_keys'));
    }

    public function test_repeated_up_and_rollback_preserve_rows_and_constraint_state(): void
    {
        $this->migrate();
        $productId = DB::table('produtos')->insertGetId($this->productData());
        $before = (array) DB::table('produtos')->find($productId);

        $this->migration()->up();
        $this->assertCount(4, Schema::getForeignKeys('produtos'));
        $this->migration()->down();
        $this->assertSame([], Schema::getForeignKeys('produtos'));
        $this->assertSame($before, (array) DB::table('produtos')->find($productId));
        $this->assertSame(1, (int) DB::scalar('PRAGMA foreign_keys'));
        $this->migration()->up();
        $this->assertCount(4, Schema::getForeignKeys('produtos'));
    }

    public function test_existing_transaction_is_rejected_before_changing_foreign_key_enforcement(): void
    {
        $this->migrate(beforeCorrection: true);
        DB::beginTransaction();

        try {
            try {
                $this->migration()->up();
                $this->fail('A reconstrução não deve executar dentro de transação externa.');
            } catch (RuntimeException $exception) {
                $this->assertStringContainsString('transação externa', $exception->getMessage());
            }

            $this->assertSame(1, (int) DB::scalar('PRAGMA foreign_keys'));
            $this->assertSame([], Schema::getForeignKeys('produtos'));
        } finally {
            DB::rollBack();
        }
    }

    private function migrate(bool $beforeCorrection = false): void
    {
        $paths = $beforeCorrection
            ? array_values(array_filter(glob(database_path('migrations/*.php')), fn (string $path): bool => basename($path) < self::MIGRATION))
            : [database_path('migrations')];

        $this->artisan('migrate', [
            '--database' => 'product_integrity_test',
            '--path' => $paths,
            '--realpath' => true,
            '--force' => true,
        ])->assertExitCode(0);
    }

    private function migration(): Migration
    {
        return require database_path('migrations/'.self::MIGRATION);
    }

    private function productData(): array
    {
        return [
            'nome' => 'Produto preservado',
            'tipo_produto' => 'roupa',
            'estado' => 'novo',
            'preco_compra' => 10,
            'preco_venda' => 30,
            'status' => 'disponivel',
        ];
    }

    private function assertRejectedByDatabase(\Closure $operation, string $message): void
    {
        try {
            $operation();
            $this->fail('A restrição do banco deveria rejeitar a operação.');
        } catch (QueryException $exception) {
            $this->assertStringContainsString($message, $exception->getMessage());
        }
    }
}
