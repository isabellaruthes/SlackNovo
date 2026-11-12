<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * SLK-07 / BUG-07: the consignment payment is identified by a dedicated column instead of the
 * free-text "motivo", so a manual outflow with the same text no longer suppresses the payment.
 * The unique index also guarantees a single payment per product at the database level.
 */
return new class extends Migration
{
    private const MOTIVO_PAGAMENTO = '/\APagamento consignado produto #(\d+)\z/';

    public function up(): void
    {
        if (! Schema::hasColumn('saida_caixas', 'id_produto_consignado')) {
            Schema::table('saida_caixas', function (Blueprint $table): void {
                // No foreign key: the payment must remain in the cash history if the product is deleted.
                $table->unsignedBigInteger('id_produto_consignado')->nullable();
                $table->unique('id_produto_consignado', 'saida_caixas_id_produto_consignado_unique');
            });
        }

        $this->vincularPagamentosExistentes();
    }

    public function down(): void
    {
        if (Schema::hasColumn('saida_caixas', 'id_produto_consignado')) {
            Schema::table('saida_caixas', function (Blueprint $table): void {
                $table->dropUnique('saida_caixas_id_produto_consignado_unique');
                $table->dropColumn('id_produto_consignado');
            });
        }
    }

    /**
     * Link the payments recorded before this column existed: the oldest outflow whose motivo is
     * exactly the automatic text becomes the product's payment. Without this, saving an already
     * paid consignment after the deploy would record the payment a second time.
     */
    private function vincularPagamentosExistentes(): void
    {
        $vinculados = DB::table('saida_caixas')->whereNotNull('id_produto_consignado')
            ->pluck('id_produto_consignado')->map(fn ($id) => (int) $id)->flip();

        $pagamentos = [];
        DB::table('saida_caixas')
            ->whereNull('id_produto_consignado')
            ->where('motivo', 'like', 'Pagamento consignado produto #%')
            ->orderBy('id')
            ->select(['id', 'motivo'])
            ->each(function (object $saida) use ($vinculados, &$pagamentos): void {
                if (! preg_match(self::MOTIVO_PAGAMENTO, (string) $saida->motivo, $matches)) {
                    return;
                }

                $idProduto = (int) $matches[1];
                if (! $vinculados->has($idProduto) && ! isset($pagamentos[$idProduto])) {
                    $pagamentos[$idProduto] = $saida->id;
                }
            });

        foreach ($pagamentos as $idProduto => $idSaida) {
            DB::table('saida_caixas')->where('id', $idSaida)->update(['id_produto_consignado' => $idProduto]);
        }
    }
};
