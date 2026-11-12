<?php

namespace Tests\Feature;

use App\Models\Produto;
use App\Models\User;
use App\Models\Venda;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * SLK-07 / BUG-14 — a product with an active (non-refunded) sale must not be deleted,
 * otherwise the sale loses its product link and the stock history becomes inconsistent.
 */
class ProductDeletionWithActiveSaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_with_an_active_sale_is_not_deleted_and_the_operator_sees_why(): void
    {
        $this->signIn();
        $produto = $this->produto(['status' => 'vendido']);
        $venda = $this->venda($produto, reembolsada: false);

        $this->from(route('admin.produtos.index'))
            ->delete(route('admin.produtos.destroy', $produto))
            ->assertRedirect(route('admin.produtos.index'))
            ->assertSessionHas('error', 'Não é possível excluir um produto com venda ativa. Reembolse a venda antes de excluir o produto.');

        $this->assertDatabaseHas('produtos', ['id' => $produto->id]);
        $this->assertSame($produto->id, $venda->fresh()->produto?->id);

        $this->get(route('admin.produtos.index'))
            ->assertOk()
            ->assertSee('Não é possível excluir um produto com venda ativa.');
    }

    public function test_product_whose_sales_were_all_refunded_can_be_deleted(): void
    {
        $this->signIn();
        $produto = $this->produto();
        $venda = $this->venda($produto, reembolsada: true);

        $this->from(route('admin.produtos.index'))
            ->delete(route('admin.produtos.destroy', $produto))
            ->assertSessionHas('success', 'Produto removido com sucesso.');

        $this->assertDatabaseMissing('produtos', ['id' => $produto->id]);
        // The refunded sale stays in the history with its own name.
        $this->assertDatabaseHas('vendas', ['id' => $venda->id, 'nome' => 'Venda']);
    }

    public function test_product_without_sales_can_still_be_deleted(): void
    {
        $this->signIn();
        $produto = $this->produto();

        $this->from(route('admin.produtos.index'))
            ->delete(route('admin.produtos.destroy', $produto))
            ->assertSessionHas('success', 'Produto removido com sucesso.');

        $this->assertDatabaseMissing('produtos', ['id' => $produto->id]);
    }

    private function signIn(): void
    {
        $this->actingAs(User::create(['nome' => 'operador', 'senha' => Hash::make('secret')]));
    }

    private function produto(array $overrides = []): Produto
    {
        return Produto::create($overrides + [
            'nome' => 'Produto', 'tipo_produto' => 'roupa', 'estado' => 'novo',
            'preco_compra' => 10, 'preco_venda' => 30, 'status' => 'disponivel',
        ]);
    }

    private function venda(Produto $produto, bool $reembolsada): Venda
    {
        return Venda::create([
            'nome' => 'Venda', 'id_produto' => $produto->id, 'comprador' => 'Cliente',
            'valor_unitario' => 30, 'valor_venda_total' => 30, 'valor_compra_total' => 10,
            'data_hora' => now(), 'reembolsada' => $reembolsada,
        ]);
    }
}
