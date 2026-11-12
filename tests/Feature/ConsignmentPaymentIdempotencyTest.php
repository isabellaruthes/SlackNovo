<?php

namespace Tests\Feature;

use App\Models\Produto;
use App\Models\SaidaCaixa;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * SLK-07 / BUG-07 — the consignment payment used the free-text "motivo" as its idempotency key,
 * so a manual cash outflow with the same text suppressed the real payment to the consignor.
 */
class ConsignmentPaymentIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_manual_outflow_with_the_payment_text_does_not_suppress_the_real_payment(): void
    {
        $this->signIn();
        $produto = $this->produto();

        $this->post(route('admin.caixa.saida'), ['valor' => '1.00', 'motivo' => 'Pagamento consignado produto #'.$produto->id])
            ->assertSessionHas('success');

        $this->put(route('admin.produtos.update', $produto), $this->consignadoPago())
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $this->assertDatabaseCount('saida_caixas', 2);
        $pagamento = SaidaCaixa::query()->where('id_produto_consignado', $produto->id)->sole();
        $this->assertEquals(10, $pagamento->valor);
        $this->assertSame('Pagamento consignado produto #'.$produto->id, $pagamento->motivo);

        // The cash balance shows both the manual outflow and the full payment.
        $this->get(route('admin.caixa.index'))->assertOk()->assertViewHas('saidas', fn ($saidas) => (float) $saidas === 11.0);
    }

    public function test_saving_a_paid_consignment_again_keeps_a_single_linked_payment(): void
    {
        $this->signIn();
        $produto = $this->produto();

        $this->put(route('admin.produtos.update', $produto), $this->consignadoPago())->assertSessionHas('success');
        $this->put(route('admin.produtos.update', $produto), $this->consignadoPago())->assertSessionHas('success');

        $this->assertSame(1, SaidaCaixa::query()->where('id_produto_consignado', $produto->id)->count());
        $this->assertDatabaseCount('saida_caixas', 1);
    }

    public function test_a_manual_outflow_cannot_be_linked_to_a_product_through_the_cash_form(): void
    {
        $this->signIn();
        $produto = $this->produto();

        $this->post(route('admin.caixa.saida'), ['valor' => '5.00', 'motivo' => 'Ajuste', 'id_produto_consignado' => $produto->id])
            ->assertSessionHas('success');

        $this->assertNull(SaidaCaixa::query()->sole()->id_produto_consignado);
    }

    public function test_the_database_rejects_a_second_payment_for_the_same_product(): void
    {
        $produto = $this->produto();
        $pagamento = fn () => (new SaidaCaixa)->forceFill([
            'valor' => 10, 'motivo' => 'Pagamento', 'data_saidacaixa' => now(), 'id_produto_consignado' => $produto->id,
        ])->save();
        $pagamento();

        $this->expectException(UniqueConstraintViolationException::class);
        $pagamento();
    }

    private function signIn(): void
    {
        $this->actingAs(User::create(['nome' => 'operador', 'senha' => Hash::make('secret')]));
    }

    private function produto(): Produto
    {
        return Produto::create([
            'nome' => 'Produto', 'tipo_produto' => 'roupa', 'estado' => 'novo',
            'preco_compra' => 10, 'preco_venda' => 30, 'status' => 'disponivel',
        ]);
    }

    private function consignadoPago(): array
    {
        return [
            'nome' => 'Produto', 'tipo_produto' => 'roupa', 'estado' => 'consignado', 'consignado_pago' => 1,
            'cliente_consignado' => 'Consignante', 'preco_compra' => 10, 'preco_venda' => 30, 'status' => 'disponivel',
        ];
    }
}
