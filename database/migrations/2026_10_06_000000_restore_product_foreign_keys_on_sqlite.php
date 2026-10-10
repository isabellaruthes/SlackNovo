<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // SQLite only changes foreign_keys outside transactions. The rebuild below
    // starts its own transaction after temporarily disabling the constraint checks.
    public $withinTransaction = false;

    private const REFERENCES = [
        'id_categoria' => 'categorias',
        'id_cor' => 'cores',
        'id_material' => 'materiais',
        'id_fornecedor' => 'fornecedores',
    ];

    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            return;
        }

        $this->rebuild(function (string $definition): string {
            $foreignKeys = Schema::getForeignKeys('produtos');
            $constraints = [];

            foreach (self::REFERENCES as $column => $parent) {
                $existing = array_values(array_filter(
                    $foreignKeys,
                    fn (array $key): bool => in_array($column, $key['columns'], true)
                ));

                if ($existing !== []) {
                    if (count($existing) !== 1
                        || $existing[0]['columns'] !== [$column]
                        || $existing[0]['foreign_table'] !== $parent
                        || $existing[0]['foreign_columns'] !== ['id']
                        || strtolower($existing[0]['on_delete']) !== 'set null') {
                        throw new RuntimeException("A chave existente de produtos.{$column} difere da relação esperada; revise-a antes de migrar.");
                    }

                    continue;
                }

                $constraints[] = $this->constraint($column, $parent);
            }

            if ($constraints === []) {
                return $definition;
            }

            $this->assertNoOrphans();
            $closingParenthesis = strrpos($definition, ')');

            if ($closingParenthesis === false) {
                throw new RuntimeException('Não foi possível interpretar a definição da tabela produtos.');
            }

            return substr_replace($definition, implode('', $constraints), $closingParenthesis, 0);
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            return;
        }

        $this->rebuild(function (string $definition): string {
            foreach (self::REFERENCES as $column => $parent) {
                // Remove only the named constraints introduced by this migration.
                $definition = str_replace($this->constraint($column, $parent), '', $definition);
            }

            return $definition;
        });
    }

    private function constraint(string $column, string $parent): string
    {
        return ', CONSTRAINT "produtos_restore_'.$column.'_foreign" FOREIGN KEY ("'.$column.'") REFERENCES "'.$parent.'" ("id") ON DELETE SET NULL';
    }

    private function assertNoOrphans(): void
    {
        $problems = [];

        foreach (self::REFERENCES as $column => $parent) {
            $query = DB::table('produtos as p')
                ->leftJoin($parent.' as parent', 'parent.id', '=', 'p.'.$column)
                ->whereNotNull('p.'.$column)
                ->whereNull('parent.id');

            $count = (clone $query)->count();

            if ($count > 0) {
                $ids = $query->orderBy('p.id')->limit(10)->pluck('p.id')->implode(', ');
                $problems[] = "{$column}: {$count} vínculo(s) órfão(s); IDs de produtos (até 10): {$ids}. "
                    ."Consulta completa: SELECT p.id, p.{$column} FROM produtos p LEFT JOIN {$parent} r ON r.id = p.{$column} WHERE p.{$column} IS NOT NULL AND r.id IS NULL;";
            }
        }

        if ($problems !== []) {
            throw new RuntimeException(
                "Migração interrompida; nenhum vínculo foi removido. ".implode(' ', $problems)
                .' Exporte e confira as referências: substitua cada ID por um registro existente ou atribua NULL somente após aprovação explícita do saneamento. Depois execute novamente php artisan migrate.'
            );
        }
    }

    private function rebuild(Closure $transform): void
    {
        if (DB::transactionLevel() !== 0 || DB::connection()->getPdo()->inTransaction()) {
            throw new RuntimeException('Execute esta migration fora de uma transação externa para preservar as tabelas relacionadas no SQLite.');
        }

        $foreignKeysEnabled = (bool) DB::scalar('PRAGMA foreign_keys');
        DB::statement('PRAGMA foreign_keys = OFF');

        try {
            if ((bool) DB::scalar('PRAGMA foreign_keys')) {
                throw new RuntimeException('Não foi possível suspender as verificações de chaves estrangeiras para reconstruir produtos.');
            }

            DB::transaction(function () use ($transform): void {
                $original = DB::selectOne('SELECT sql FROM sqlite_master WHERE type = ? AND name = ?', ['table', 'produtos']);

                if ($original === null) {
                    throw new RuntimeException('A tabela produtos não existe. Execute as migrations anteriores primeiro.');
                }

                $definition = $transform($original->sql);

                if ($definition === $original->sql) {
                    return;
                }

                if (Schema::hasTable('produtos_integrity_tmp')) {
                    throw new RuntimeException('A tabela produtos_integrity_tmp já existe; revise-a antes de migrar.');
                }

                // Reuse SQLite's complete DDL: Schema::table() reconstructs from
                // column metadata and would discard existing CHECKs and triggers.
                $create = preg_replace(
                    '/\ACREATE\s+TABLE\s+(?:"produtos"|`produtos`|\[produtos\]|produtos)\s*\(/i',
                    'CREATE TABLE "produtos_integrity_tmp" (',
                    $definition,
                    1,
                    $replacements
                );

                if ($replacements !== 1) {
                    throw new RuntimeException('A definição de produtos não tem o formato esperado; nenhuma tabela foi substituída.');
                }

                $objects = DB::select(
                    "SELECT sql FROM sqlite_master WHERE tbl_name = ? AND type IN ('index', 'trigger') AND sql IS NOT NULL ORDER BY type, name",
                    ['produtos']
                );
                $sequence = DB::table('sqlite_sequence')->where('name', 'produtos')->value('seq');
                $columns = [];

                foreach (DB::select('PRAGMA table_xinfo("produtos")') as $column) {
                    if ((int) $column->hidden === 0) {
                        $columns[] = '"'.str_replace('"', '""', $column->name).'"';
                    }
                }

                $columnList = implode(', ', $columns);
                DB::statement($create);
                DB::statement('INSERT INTO "produtos_integrity_tmp" ('.$columnList.') SELECT '.$columnList.' FROM "produtos"');
                DB::statement('DROP TABLE "produtos"');

                // Views and triggers owned by other tables still refer to
                // produtos while it is temporarily absent. A normal RENAME
                // reparses those bodies and can fail before restoring the name.
                // Only rename the replacement; leave their references intact.
                $legacyAlterTable = (bool) DB::scalar('PRAGMA legacy_alter_table');

                try {
                    DB::statement('PRAGMA legacy_alter_table = ON');
                    DB::statement('ALTER TABLE "produtos_integrity_tmp" RENAME TO "produtos"');
                } finally {
                    DB::statement('PRAGMA legacy_alter_table = '.($legacyAlterTable ? 'ON' : 'OFF'));
                }

                foreach ($objects as $object) {
                    DB::statement($object->sql);
                }

                if ($sequence !== null) {
                    DB::table('sqlite_sequence')->updateOrInsert(['name' => 'produtos'], ['seq' => $sequence]);
                }

                if (DB::select('PRAGMA foreign_key_check') !== []) {
                    throw new RuntimeException('A verificação de integridade referencial falhou; a reconstrução de produtos foi revertida.');
                }
            });
        } finally {
            DB::statement('PRAGMA foreign_keys = '.($foreignKeysEnabled ? 'ON' : 'OFF'));
        }
    }
};
