<?php

namespace Tests\Feature;

use App\Models\Produto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * SLK-09 — dashboard on phones: the product list is paginated (it rendered every product,
 * ~8,200px tall with 49 items) and the sales chart has a text alternative.
 * The 46px horizontal overflow at 390px is verified in the browser (see SLK-09 report).
 */
class DashboardResponsiveTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->actingAs(User::create(['nome' => 'operador', 'senha' => Hash::make('secret')]));
    }

    public function test_the_product_list_is_paginated_and_keeps_the_filters(): void
    {
        for ($i = 1; $i <= 30; $i++) {
            $this->produto(sprintf('Peça %02d', $i));
        }

        $primeira = $this->get(route('dashboard', ['status' => 'disponivel']))->assertOk();
        $primeira->assertViewHas('produtos', fn ($produtos) => $produtos->count() === 24 && $produtos->total() === 30);
        $primeira->assertSee('Peça 30')->assertDontSee('Peça 06');
        $primeira->assertSee('status=disponivel&amp;page=2', false);
        $primeira->assertSee('aria-label="Paginação do catálogo administrativo"', false);

        $this->get(route('dashboard', ['status' => 'disponivel', 'page' => 2]))
            ->assertOk()
            ->assertViewHas('produtos', fn ($produtos) => $produtos->count() === 6)
            ->assertSee('Peça 06')->assertSee('Peça 01')->assertDontSee('Peça 07');
    }

    public function test_no_pagination_is_rendered_for_a_single_page(): void
    {
        $this->produto('Peça única');

        $this->get(route('dashboard'))->assertOk()
            ->assertDontSee('aria-label="Paginação do catálogo administrativo"', false);
    }

    public function test_the_sales_chart_has_a_text_alternative(): void
    {
        $this->get(route('dashboard'))->assertOk()
            ->assertSee('class="visually-hidden"', false)
            ->assertSee('0 produtos vendidos');
    }

    public function test_an_empty_filtered_result_says_that_nothing_matched(): void
    {
        $this->produto('Camisa');

        $this->get(route('dashboard', ['q' => 'inexistente']))->assertOk()
            ->assertSee('Nenhum produto encontrado com os filtros atuais.')
            ->assertDontSee('Nenhum produto cadastrado.');
    }

    private function produto(string $nome): Produto
    {
        return Produto::create([
            'nome' => $nome, 'tipo_produto' => 'roupa', 'estado' => 'novo',
            'preco_compra' => 10, 'preco_venda' => 30, 'status' => 'disponivel',
        ]);
    }
}
