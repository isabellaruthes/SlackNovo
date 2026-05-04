<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendas', function (Blueprint $table) {
            $table->string('nome', 50);
            $table->unsignedBigInteger('id_produto');
            $table->string('comprador', 50);
            $table->decimal('valor_unitario', 10, 2);
            $table->decimal('valor_venda_total', 10, 2);
            $table->decimal('valor_compra_total', 10, 2);
            $table->dateTime('data_hora');
        });
    }

    public function down(): void
    {
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
    }
};
