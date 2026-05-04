<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
<<<<<<< codex/fix-database-column-issue-in-vendas-table-1wnphw
        if (! Schema::hasColumn('vendas', 'nome')) {
            Schema::table('vendas', function (Blueprint $table) {
                $table->string('nome', 50)->nullable();
            });
        }

        if (! Schema::hasColumn('vendas', 'id_produto')) {
            Schema::table('vendas', function (Blueprint $table) {
                $table->unsignedBigInteger('id_produto')->nullable();
            });
        }

        if (! Schema::hasColumn('vendas', 'comprador')) {
            Schema::table('vendas', function (Blueprint $table) {
                $table->string('comprador', 50)->nullable();
            });
        }

        if (! Schema::hasColumn('vendas', 'valor_unitario')) {
            Schema::table('vendas', function (Blueprint $table) {
                $table->decimal('valor_unitario', 10, 2)->nullable();
            });
        }

        if (! Schema::hasColumn('vendas', 'valor_venda_total')) {
            Schema::table('vendas', function (Blueprint $table) {
                $table->decimal('valor_venda_total', 10, 2)->nullable();
            });
        }

        if (! Schema::hasColumn('vendas', 'valor_compra_total')) {
            Schema::table('vendas', function (Blueprint $table) {
                $table->decimal('valor_compra_total', 10, 2)->nullable();
            });
        }

        if (! Schema::hasColumn('vendas', 'data_hora')) {
            Schema::table('vendas', function (Blueprint $table) {
                $table->dateTime('data_hora')->nullable();
            });
        }
=======
        Schema::table('vendas', function (Blueprint $table) {
            $table->string('nome', 50);
            $table->unsignedBigInteger('id_produto');
            $table->string('comprador', 50);
            $table->decimal('valor_unitario', 10, 2);
            $table->decimal('valor_venda_total', 10, 2);
            $table->decimal('valor_compra_total', 10, 2);
            $table->dateTime('data_hora');
        });
>>>>>>> main
    }

    public function down(): void
    {
<<<<<<< codex/fix-database-column-issue-in-vendas-table-1wnphw
        $columns = [
            'nome',
            'id_produto',
            'comprador',
            'valor_unitario',
            'valor_venda_total',
            'valor_compra_total',
            'data_hora',
        ];

        foreach ($columns as $column) {
            if (Schema::hasColumn('vendas', $column)) {
                Schema::table('vendas', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
=======
        Schema::table('vendas', function (Blueprint $table) {
            $table->dropColumn([
                'nome',
                'id_produto',
                'comprador',
                'valor_unitario',
                'valor_venda_total',
                'valor_compra_total',
                'data_hora',
            ]);
        });
>>>>>>> main
    }
};
