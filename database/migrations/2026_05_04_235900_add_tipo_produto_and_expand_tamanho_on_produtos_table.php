<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('produtos', 'tipo_produto')) {
            Schema::table('produtos', function (Blueprint $table): void {
                $table->enum('tipo_produto', ['roupa', 'calcado'])->default('roupa')->after('estado');
            });
        }

        if (DB::getDriverName() === 'sqlite') {
            // SQLite: precisa recriar tabela para atualizar CHECK de tamanho
            DB::statement('PRAGMA foreign_keys=OFF');

            DB::statement("
                CREATE TABLE produtos_tmp (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    imagen VARCHAR(120),
                    nome VARCHAR(50) NOT NULL,
                    estado TEXT CHECK(estado IN ('novo','usado','consignado')) NULL,
                    tipo_produto TEXT CHECK(tipo_produto IN ('roupa','calcado')) NOT NULL DEFAULT 'roupa',
                    tamanho TEXT CHECK(tamanho IN (
                        'pp','p','m','g','gg','g1','g2','g3','g4',
                        '14','15','16','17','18','19','20','21','22','23','24','25','26','27','28','29','30','31','32','33','34','35','36','37','38','39','40','41','42','43','44','45','46'
                    )) NULL,
                    preco_compra NUMERIC NOT NULL,
                    preco_venda NUMERIC NOT NULL,
                    genero TEXT CHECK(genero IN ('masculino','feminino','unissex')) NULL,
                    status TEXT CHECK(status IN ('disponivel','vendido')) NOT NULL DEFAULT 'disponivel',
                    descricao TEXT NULL,
                    id_categoria INTEGER NULL,
                    id_cor INTEGER NULL,
                    id_material INTEGER NULL,
                    id_fornecedor INTEGER NULL,
                    cliente_consignado VARCHAR(255) NULL,
                    consignado_pago INTEGER NOT NULL DEFAULT 0,
                    created_at DATETIME NULL,
                    updated_at DATETIME NULL
                )
            ");

            DB::statement("
                INSERT INTO produtos_tmp (
                    id, imagen, nome, estado, tipo_produto, tamanho, preco_compra, preco_venda, genero, status, descricao,
                    id_categoria, id_cor, id_material, id_fornecedor, cliente_consignado, consignado_pago, created_at, updated_at
                )
                SELECT
                    id, imagen, nome, estado, COALESCE(tipo_produto, 'roupa'), tamanho, preco_compra, preco_venda, genero, status, descricao,
                    id_categoria, id_cor, id_material, id_fornecedor, cliente_consignado, COALESCE(consignado_pago, 0), created_at, updated_at
                FROM produtos
            ");

            DB::statement("DROP TABLE produtos");
            DB::statement("ALTER TABLE produtos_tmp RENAME TO produtos");
            DB::statement('PRAGMA foreign_keys=ON');
        } else {
            // MySQL/MariaDB
            DB::statement("ALTER TABLE produtos MODIFY tamanho ENUM('pp','p','m','g','gg','g1','g2','g3','g4','14','15','16','17','18','19','20','21','22','23','24','25','26','27','28','29','30','31','32','33','34','35','36','37','38','39','40','41','42','43','44','45','46') NULL");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE produtos MODIFY tamanho ENUM('pp','p','m','g','gg','g1','g2','g3','g4') NULL");
        }

        if (Schema::hasColumn('produtos', 'tipo_produto')) {
            Schema::table('produtos', function (Blueprint $table): void {
                $table->dropColumn('tipo_produto');
            });
        }
    }
};