<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('saida_caixas', 'valor')) {
            Schema::table('saida_caixas', function (Blueprint $table) {
                $table->decimal('valor', 10, 2)->nullable();
            });
        }

        if (! Schema::hasColumn('saida_caixas', 'motivo')) {
            Schema::table('saida_caixas', function (Blueprint $table) {
                $table->string('motivo', 255)->nullable();
            });
        }

        if (! Schema::hasColumn('saida_caixas', 'data_saidacaixa')) {
            Schema::table('saida_caixas', function (Blueprint $table) {
                $table->dateTime('data_saidacaixa')->nullable();
            });
        }
    }

    public function down(): void
    {
        $columns = ['valor', 'motivo', 'data_saidacaixa'];

        foreach ($columns as $column) {
            if (Schema::hasColumn('saida_caixas', $column)) {
                Schema::table('saida_caixas', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }
};
