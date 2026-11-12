<?php

namespace Tests\Feature;

use App\Models\Produto;
use App\Models\SaidaCaixa;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * SLK-07 / BUG-07 — the migration that adds saida_caixas.id_produto_consignado must link the
 * payments recorded before the deploy, once per product. DatabaseMigrations (not a wrapping
 * transaction) is used because the test runs DDL, which MySQL commits implicitly.
 */
class ConsignmentPaymentMigrationTest extends TestCase
{
    use DatabaseMigrations;

    private const MIGRATION = 'migrations/2026_11_12_000000_add_id_produto_consignado_to_saida_caixas_table.php';

    protected function tearDown(): void
    {
        // DatabaseMigrations rolls every migration back after the test. The pre-existing
        // down() of 2026_08_28_000000_make_produto_imagen_nullable fails while a product
        // without image exists (backlog BUG-19), so the products are removed first.
        if (isset($this->app)) {
            DB::table('produtos')->delete();
        }

        parent::tearDown();
    }

    public function test_the_migration_links_existing_automatic_payments_once_per_product(): void
    {
        $migration = require database_path(self::MIGRATION);
        $migration->down();
        $this->assertFalse(Schema::hasColumn('saida_caixas', 'id_produto_consignado'));

        $pagamento5 = $this->saida('Pagamento consignado produto #5', 10);
        $repetido5 = $this->saida('Pagamento consignado produto #5', 10);
        $pagamento12 = $this->saida('Pagamento consignado produto #12', 20);
        $manual = $this->saida('Pagamento consignado produto #12 (parcial)', 3);
        $outra = $this->saida('Compra de cabides', 7);

        $migration->up();

        $vinculos = DB::table('saida_caixas')->pluck('id_produto_consignado', 'id')->all();
        $this->assertSame(5, (int) $vinculos[$pagamento5]);
        $this->assertNull($vinculos[$repetido5]);
        $this->assertSame(12, (int) $vinculos[$pagamento12]);
        $this->assertNull($vinculos[$manual]);
        $this->assertNull($vinculos[$outra]);
    }

    public function test_a_consignment_paid_before_the_deploy_is_not_paid_again(): void
    {
        $migration = require database_path(self::MIGRATION);
        $produto = Produto::create([
            'nome' => 'Produto', 'tipo_produto' => 'roupa', 'estado' => 'consignado', 'consignado_pago' => true,
            'cliente_consignado' => 'Consignante', 'preco_compra' => 10, 'preco_venda' => 30, 'status' => 'disponivel',
        ]);
        $motivo = 'Pagamento consignado produto #'.$produto->id;

        // State before the deploy: the payment exists only as an outflow with the automatic text.
        $migration->down();
        $this->saida($motivo, 10);
        $migration->up();

        $this->actingAs(User::create(['nome' => 'operador', 'senha' => Hash::make('secret')]));
        $this->put(route('admin.produtos.update', $produto), [
            'nome' => 'Produto editado', 'tipo_produto' => 'roupa', 'estado' => 'consignado', 'consignado_pago' => 1,
            'cliente_consignado' => 'Consignante', 'preco_compra' => 10, 'preco_venda' => 30, 'status' => 'disponivel',
        ])->assertSessionHasNoErrors()->assertSessionHas('success');

        $this->assertSame(1, SaidaCaixa::query()->where('motivo', $motivo)->count());
        $this->assertSame(1, SaidaCaixa::query()->where('id_produto_consignado', $produto->id)->count());
    }

    private function saida(string $motivo, float $valor): int
    {
        return DB::table('saida_caixas')->insertGetId([
            'valor' => $valor, 'motivo' => $motivo, 'data_saidacaixa' => now(),
        ]);
    }
}
