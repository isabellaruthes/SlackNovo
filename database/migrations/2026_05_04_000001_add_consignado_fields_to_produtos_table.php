<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produtos', function (Blueprint $table): void {
            $table->string('cliente_consignado', 120)->nullable()->after('estado');
            $table->boolean('consignado_pago')->default(false)->after('cliente_consignado');
        });
    }

    public function down(): void
    {
        Schema::table('produtos', function (Blueprint $table): void {
            $table->dropColumn(['cliente_consignado', 'consignado_pago']);
        });
    }
};
