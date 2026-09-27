<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('produtos', function (Blueprint $table): void {
            $table->id();
            $table->string('imagen', 120)->nullable();
            $table->string('nome', 50);
            $table->enum('estado', ['novo', 'usado', 'consignado'])->nullable();
            $table->enum('tamanho', ['pp', 'p', 'm', 'g', 'gg', 'g1', 'g2', 'g3', 'g4'])->nullable();
            $table->decimal('preco_compra', 10, 2);
            $table->decimal('preco_venda', 10, 2);
            $table->enum('genero', ['masculino', 'feminino', 'unissex'])->nullable();
            $table->enum('status', ['disponivel', 'vendido'])->default('disponivel');
            $table->text('descricao')->nullable();
            $table->foreignId('id_categoria')->nullable()->constrained('categorias')->nullOnDelete();
            $table->foreignId('id_cor')->nullable()->constrained('cores')->nullOnDelete();
            $table->foreignId('id_material')->nullable()->constrained('materiais')->nullOnDelete();
            $table->foreignId('id_fornecedor')->nullable()->constrained('fornecedores')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produtos');
    }
};
