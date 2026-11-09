<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Cor;
use App\Models\Fornecedor;
use App\Models\Material;
use App\Models\Produto;
use App\Models\User;
use App\Models\Venda;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FunctionalAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_auxiliary_registers_can_be_created_updated_and_deleted_through_their_routes(): void
    {
        $this->signIn();

        foreach (['cores' => Cor::class, 'materiais' => Material::class, 'fornecedores' => Fornecedor::class] as $module => $model) {
            $url = '/admin/'.$module;
            $this->from($url)->post($url, ['nome' => 'Inicial'])->assertRedirect($url)->assertSessionHasNoErrors();
            $record = $model::firstOrFail();
            $this->get($url)->assertOk()->assertSee('Inicial');
            $this->from($url)->put($url.'/'.$record->id, ['nome' => 'Alterado'])->assertRedirect($url);
            $this->assertSame('Alterado', $record->fresh()->nome);
            $this->get($url)->assertSee('Alterado');
            $this->from($url)->put($url.'/'.$record->id, ['nome' => ''])->assertSessionHasErrors('nome');
            $this->assertSame('Alterado', $record->fresh()->nome);
            $this->from($url)->delete($url.'/'.$record->id)->assertRedirect($url);
            $this->assertNull($record->fresh());
        }
    }

    public function test_catalog_filters_sorting_pagination_and_absent_image_are_usable(): void
    {
        $category = Categoria::create(['nome' => 'Camisas']);
        for ($i = 1; $i <= 25; $i++) {
            Produto::create($this->productData(['nome' => sprintf('Camisa %02d', $i), 'preco_venda' => $i + 10, 'id_categoria' => $category->id, 'tamanho' => 'm']));
        }
        Produto::create($this->productData(['nome' => 'Produto vendido', 'status' => 'vendido']));
        Produto::create($this->productData(['nome' => 'Outra categoria']));

        $query = ['categoria' => $category->id, 'tamanho' => 'm', 'ordenar' => 'price_asc', 'q' => 'Camisa'];
        $response = $this->get('/?'.http_build_query($query))->assertOk()->assertDontSee('Produto vendido')->assertDontSee('Outra categoria');
        $products = $response->viewData('produtos');
        $this->assertSame(25, $products->total());
        $this->assertCount(24, $products->items());
        $this->assertSame('Camisa 01', $products->first()->nome);
        $this->assertStringContainsString('categoria='.$category->id, $products->nextPageUrl());
        $response->assertSee('Sem imagem')->assertDontSee('src="http://localhost/storage/"', false);
        $this->get('/?'.http_build_query($query + ['page' => 2]))->assertOk()->assertViewHas('produtos', fn ($page) => count($page) === 1 && $page->first()->nome === 'Camisa 25');
    }

    public function test_editing_product_preserves_gender_and_failed_submission_retains_selected_gender(): void
    {
        $this->signIn();
        $product = Produto::create($this->productData(['genero' => 'feminino']));
        $this->get('/admin/produtos/'.$product->id.'/edit')->assertOk()->assertSee('value="feminino" selected', false);
        $this->put('/admin/produtos/'.$product->id, $this->productData(['genero' => 'feminino']))->assertSessionHasNoErrors();
        $this->assertSame('feminino', $product->fresh()->genero);
        $this->from('/admin/produtos/'.$product->id.'/edit')->followingRedirects()
            ->put('/admin/produtos/'.$product->id, $this->productData(['nome' => '', 'genero' => 'unissex']))
            ->assertOk()->assertSee('value="unissex" selected', false)->assertSee('role="alert"', false);
        $this->assertSame('feminino', $product->fresh()->genero);
    }

    public function test_cash_sale_expense_and_refund_produce_visible_feedback_and_correct_balances(): void
    {
        $this->signIn();
        $product = Produto::create($this->productData());
        $this->get('/admin/caixa')->assertOk()->assertSee('name="valor_venda"', false);
        $sale = ['id_produto' => $product->id, 'nome' => 'Venda funcional', 'comprador' => 'Cliente', 'valor_venda' => '30.00'];
        $this->from('/admin/caixa')->followingRedirects()->post('/admin/caixa/venda', $sale)
            ->assertOk()->assertSee('Venda registrada com sucesso.')->assertViewHas('entradas', 30);
        $this->assertSame('vendido', $product->fresh()->status);
        $this->from('/admin/caixa')->followingRedirects()->post('/admin/caixa/venda', $sale)
            ->assertOk()->assertSee('Este produto já foi vendido.');
        $this->from('/admin/caixa')->followingRedirects()->post('/admin/caixa/saida', ['valor' => '5.00', 'motivo' => 'Embalagem'])
            ->assertOk()->assertSee('Saída registrada com sucesso.')->assertViewHas('saldo', 25);
        $id = Venda::firstOrFail()->id;
        $this->from('/admin/caixa')->followingRedirects()->post('/admin/caixa/venda/'.$id.'/reembolso')
            ->assertOk()->assertSee('Venda reembolsada com sucesso.')->assertSee('Reembolsado')->assertViewHas('saldo', -5);
        $this->assertSame('disponivel', $product->fresh()->status);
    }

    public function test_product_create_and_delete_show_feedback_and_the_empty_list_is_explained(): void
    {
        $this->signIn();
        $this->get('/admin/produtos')->assertOk()->assertSee('Nenhum produto');
        $this->followingRedirects()->post('/admin/produtos', $this->productData())
            ->assertOk()->assertSee('Produto criado com sucesso.');
        $product = Produto::firstOrFail();
        $this->from('/admin/produtos')->followingRedirects()->delete('/admin/produtos/'.$product->id)
            ->assertOk()->assertSee('Produto removido com sucesso.')->assertSee('Nenhum produto');
        $this->assertDatabaseCount('produtos', 0);
    }

    private function signIn(): void
    {
        $this->actingAs(User::create(['nome' => 'aceite', 'senha' => Hash::make('test-only')]));
    }

    private function productData(array $overrides = []): array
    {
        return array_replace(['nome' => 'Produto de aceite', 'tipo_produto' => 'roupa', 'estado' => 'novo', 'preco_compra' => 10, 'preco_venda' => 30, 'status' => 'disponivel'], $overrides);
    }
}
