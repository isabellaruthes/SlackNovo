<?php

namespace Tests\Feature;

use App\Models\Produto;
use App\Models\SaidaCaixa;
use App\Models\User;
use App\Models\Usuario;
use App\Models\Venda;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class CriticalFlowsTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_rehashes_the_password_in_the_senha_column(): void
    {
        $user = User::create(['nome' => 'operador', 'senha' => Hash::make('secret', ['rounds' => 5])]);

        $this->post('/login', ['nome' => 'operador', 'password' => 'secret'])
            ->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
        $this->assertFalse(Hash::needsRehash($user->fresh()->senha));
        $this->assertTrue(Hash::check('secret', $user->fresh()->senha));
    }

    public function test_admin_can_manage_categories_with_validation_and_feedback(): void
    {
        $this->signIn();

        $this->post(route('admin.categorias.store'), ['nome' => 'Material escolar'])
            ->assertSessionHas('success');

        $categoria = \App\Models\Categoria::firstOrFail();
        $this->assertDatabaseHas('categorias', ['nome' => 'Material escolar']);

        $this->put(route('admin.categorias.update', $categoria), ['nome' => 'Papéis e materiais'])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('categorias', ['id' => $categoria->id, 'nome' => 'Papéis e materiais']);

        $this->delete(route('admin.categorias.destroy', $categoria))->assertSessionHas('success');
        $this->assertDatabaseMissing('categorias', ['id' => $categoria->id]);

        $this->post(route('admin.categorias.store'), ['nome' => ''])
            ->assertSessionHasErrors('nome');
    }

    public function test_alternative_usuario_model_supports_password_rehashing(): void
    {
        $user = Usuario::create(['nome' => 'operador', 'senha' => Hash::make('secret', ['rounds' => 5])]);
        $provider = new EloquentUserProvider(app('hash'), Usuario::class);

        $provider->rehashPasswordIfRequired($user, ['password' => 'secret']);

        $this->assertFalse(Hash::needsRehash($user->fresh()->senha));
        $this->assertTrue(Hash::check('secret', $user->fresh()->senha));
    }

    public function test_a_sold_product_cannot_be_made_available_without_a_refund(): void
    {
        $this->signIn();
        $produto = $this->produto();
        $this->venda($produto);
        $produto->update(['status' => 'vendido']);

        $this->put(route('admin.produtos.update', $produto), $this->productData())
            ->assertSessionHasErrors('status');

        $this->assertSame('vendido', $produto->fresh()->status);
    }

    public function test_an_active_sale_blocks_duplicate_sales_even_with_inconsistent_stock_status(): void
    {
        $this->signIn();
        $produto = $this->produto();
        $this->venda($produto);

        $this->post(route('admin.caixa.venda'), [
            'id_produto' => $produto->id, 'nome' => 'Venda', 'comprador' => 'Cliente', 'valor_venda' => 30,
        ])->assertSessionHas('error');

        $this->assertDatabaseCount('vendas', 1);
    }

    public function test_refund_is_idempotent_and_allows_resale(): void
    {
        $this->signIn();
        $produto = $this->produto();
        $venda = $this->venda($produto);
        $produto->update(['status' => 'vendido']);

        $this->post(route('admin.caixa.reembolso', $venda))->assertSessionHas('success');
        $this->assertSame('disponivel', $produto->fresh()->status);
        $this->post(route('admin.caixa.reembolso', $venda))->assertSessionHas('error');
        $this->post(route('admin.caixa.venda'), [
            'id_produto' => $produto->id, 'nome' => 'Revenda', 'comprador' => 'Cliente', 'valor_venda' => 30,
        ])->assertSessionHas('success');

        $this->assertSame('vendido', $produto->fresh()->status);
        $this->assertSame(1, Venda::where('reembolsada', false)->count());
    }

    public function test_refunding_an_old_sale_preserves_stock_when_another_sale_is_active(): void
    {
        $this->signIn();
        $produto = $this->produto();
        $venda = $this->venda($produto);
        $this->venda($produto);
        $produto->update(['status' => 'vendido']);

        $this->post(route('admin.caixa.reembolso', $venda))->assertSessionHas('success');

        $this->assertTrue($venda->fresh()->reembolsada);
        $this->assertSame('vendido', $produto->fresh()->status);
    }

    public function test_repeated_consignment_updates_only_record_one_payment(): void
    {
        $this->signIn();
        $produto = $this->produto();
        $data = array_replace($this->productData(), ['estado' => 'consignado', 'consignado_pago' => 1]);

        $this->put(route('admin.produtos.update', $produto), $data)->assertSessionHas('success');
        $this->put(route('admin.produtos.update', $produto), $data)->assertSessionHas('success');

        $this->assertDatabaseCount('saida_caixas', 1);
        $this->assertDatabaseHas('saida_caixas', ['valor' => 10]);
    }

    public function test_failed_consignment_payment_rolls_back_product_creation_and_update(): void
    {
        $this->signIn();
        $produto = $this->produto();
        $data = array_replace($this->productData(), ['estado' => 'consignado', 'consignado_pago' => 1]);
        $this->withoutExceptionHandling();
        $dispatcher = SaidaCaixa::getEventDispatcher();
        SaidaCaixa::setEventDispatcher(clone $dispatcher);
        SaidaCaixa::creating(function (): void {
            throw new RuntimeException('Payment failed');
        });

        try {
            foreach (['post', 'put'] as $method) {
                try {
                    $url = $method === 'post' ? route('admin.produtos.store') : route('admin.produtos.update', $produto);
                    $this->{$method}($url, $data);
                    $this->fail('Expected payment failure');
                } catch (RuntimeException $exception) {
                    $this->assertSame('Payment failed', $exception->getMessage());
                }

                $this->assertDatabaseCount('produtos', 1);
                $this->assertDatabaseCount('saida_caixas', 0);
                $this->assertFalse((bool) $produto->fresh()->consignado_pago);
                $this->assertSame('novo', $produto->fresh()->estado);
            }
        } finally {
            SaidaCaixa::setEventDispatcher($dispatcher);
        }
    }

    private function signIn(): void
    {
        $this->actingAs(User::create(['nome' => 'operador', 'senha' => Hash::make('secret')]));
    }

    private function productData(): array
    {
        return [
            'nome' => 'Produto', 'tipo_produto' => 'roupa', 'estado' => 'novo',
            'preco_compra' => 10, 'preco_venda' => 30, 'status' => 'disponivel',
        ];
    }

    private function produto(): Produto
    {
        return Produto::create($this->productData());
    }

    private function venda(Produto $produto): Venda
    {
        return Venda::create([
            'nome' => 'Venda', 'id_produto' => $produto->id, 'comprador' => 'Cliente',
            'valor_unitario' => 30, 'valor_venda_total' => 30, 'valor_compra_total' => 10,
            'data_hora' => now(), 'reembolsada' => false,
        ]);
    }
}
