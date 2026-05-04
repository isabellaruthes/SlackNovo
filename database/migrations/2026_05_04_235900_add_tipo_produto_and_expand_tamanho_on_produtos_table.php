<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produtos', function (Blueprint $table): void {
            $table->enum('tipo_produto', ['roupa', 'calcado'])->default('roupa')->after('estado');
        });

        DB::statement("ALTER TABLE produtos MODIFY tamanho ENUM('pp','p','m','g','gg','g1','g2','g3','g4','14','15','16','17','18','19','20','21','22','23','24','25','26','27','28','29','30','31','32','33','34','35','36','37','38','39','40','41','42','43','44','45','46') NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE produtos MODIFY tamanho ENUM('pp','p','m','g','gg','g1','g2','g3','g4') NULL");

        Schema::table('produtos', function (Blueprint $table): void {
            $table->dropColumn('tipo_produto');
        });
    }
};
