<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Cor;
use App\Models\Fornecedor;
use App\Models\Material;
use App\Models\Produto;
use App\Models\SaidaCaixa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class ProductSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Storage::fake('public');
        $this->actingAs(User::create(['nome' => 'operador', 'senha' => Hash::make('secret')]));
    }

    #[DataProvider('invalidFields')]
    public function test_invalid_values_are_rejected_on_create_and_update(string $field, mixed $value): void
    {
        $produto = Produto::create($this->productData());
        $data = array_replace($this->productData(), [$field => $value]);

        $this->post(route('admin.produtos.store'), $data)->assertSessionHasErrors($field);
        $this->put(route('admin.produtos.update', $produto), $data)->assertSessionHasErrors($field);

        $this->assertDatabaseCount('produtos', 1);
        $this->assertDatabaseCount('saida_caixas', 0);
        $this->assertEquals(10, $produto->fresh()->preco_compra);
        $this->assertEquals(30, $produto->fresh()->preco_venda);
    }

    public static function invalidFields(): array
    {
        return [
            'negative cost' => ['preco_compra', '-100'],
            'negative sale price' => ['preco_venda', '-100'],
            'zero sale price' => ['preco_venda', '0'],
            'cost exceeds database precision' => ['preco_compra', '100000000'],
            'sale price exceeds database precision' => ['preco_venda', '100000000'],
            'cost has extra decimal places' => ['preco_compra', '1.001'],
            'sale price has extra decimal places' => ['preco_venda', '1.001'],
            'cost uses scientific notation' => ['preco_compra', '1e2'],
            'sale price uses scientific notation' => ['preco_venda', '1e2'],
            'missing category' => ['id_categoria', 999999],
            'missing color' => ['id_cor', 999999],
            'missing material' => ['id_material', 999999],
            'missing supplier' => ['id_fornecedor', 999999],
            'consignor must be a string' => ['cliente_consignado', ['unexpected']],
            'consignor is too long' => ['cliente_consignado', str_repeat('a', 121)],
            'payment must be boolean' => ['consignado_pago', 'yes'],
            'payment must not be an array' => ['consignado_pago', ['1']],
            'description must be a string' => ['descricao', ['unexpected']],
            'description is too long' => ['descricao', str_repeat('a', 5001)],
            'explicit null status' => ['status', null],
        ];
    }

    public function test_valid_relations_and_money_boundaries_are_accepted(): void
    {
        $data = array_replace($this->productData(), [
            'preco_compra' => '0',
            'preco_venda' => '99999999.99',
            'estado' => 'consignado',
            'consignado_pago' => '0',
            'cliente_consignado' => str_repeat('a', 120),
            'descricao' => str_repeat('a', 5000),
            'id_categoria' => Categoria::create(['nome' => 'Categoria'])->id,
            'id_cor' => Cor::create(['nome' => 'Cor'])->id,
            'id_material' => Material::create(['nome' => 'Material'])->id,
            'id_fornecedor' => Fornecedor::create(['nome' => 'Fornecedor'])->id,
        ]);

        $this->post(route('admin.produtos.store'), $data)->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertDatabaseCount('saida_caixas', 0);
        $produto = Produto::firstOrFail();
        $this->assertEquals(0, $produto->preco_compra);

        $data['preco_compra'] = '99999999.99';
        $data['preco_venda'] = '0.01';
        $data['consignado_pago'] = '1';
        $this->put(route('admin.produtos.update', $produto), $data)
            ->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertDatabaseHas('saida_caixas', ['valor' => '99999999.99']);
    }

    public function test_a_paid_consignment_cannot_have_zero_cost(): void
    {
        $produto = Produto::create($this->productData());
        $data = array_replace($this->productData(), [
            'preco_compra' => '0', 'estado' => 'consignado', 'consignado_pago' => 1,
        ]);

        $this->post(route('admin.produtos.store'), $data)->assertSessionHasErrors('preco_compra');
        $this->put(route('admin.produtos.update', $produto), $data)->assertSessionHasErrors('preco_compra');

        $this->assertDatabaseCount('produtos', 1);
        $this->assertDatabaseCount('saida_caixas', 0);
    }

    public function test_array_input_validation_errors_can_be_rendered(): void
    {
        Categoria::create(['nome' => 'Categoria']);
        Cor::create(['nome' => 'Cor']);
        Material::create(['nome' => 'Material']);
        Fornecedor::create(['nome' => 'Fornecedor']);
        $produto = Produto::create($this->productData());
        $data = array_fill_keys([
            'nome', 'cliente_consignado', 'descricao', 'preco_compra', 'preco_venda',
            'id_categoria', 'id_cor', 'id_material', 'id_fornecedor', 'estado',
            'tipo_produto', 'tamanho', 'status', 'consignado_pago',
        ], ['unexpected']);

        $this->from(route('admin.produtos.create'))->followingRedirects()
            ->post(route('admin.produtos.store'), $data)->assertOk()->assertViewIs('admin.produtos.create');
        $this->from(route('admin.produtos.edit', $produto))->followingRedirects()
            ->put(route('admin.produtos.update', $produto), $data)->assertOk()->assertViewIs('admin.produtos.edit');

        $this->assertDatabaseCount('produtos', 1);
    }

    public function test_unsafe_or_oversized_uploads_are_rejected(): void
    {
        $produto = Produto::create($this->productData());

        foreach (['post', 'put'] as $method) {
            $url = $method === 'post' ? route('admin.produtos.store') : route('admin.produtos.update', $produto);
            foreach ([
                UploadedFile::fake()->create('payload.php', 1, 'image/jpeg'),
                UploadedFile::fake()->create('payload.svg', 1, 'image/svg+xml'),
                UploadedFile::fake()->create('large.png', 5121, 'image/png'),
            ] as $image) {
                $this->{$method}($url, array_replace($this->productData(), ['imagen' => $image]))
                    ->assertSessionHasErrors('imagen');
            }
        }

        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->assertDatabaseCount('produtos', 1);
    }

    public function test_images_are_replaced_and_deleted_only_after_the_transaction_commits(): void
    {
        $this->post(route('admin.produtos.store'), array_replace($this->productData(), ['imagen' => $this->image()]))
            ->assertSessionHas('success');
        $produto = Produto::firstOrFail();
        $oldImage = $produto->imagen;
        Storage::disk('public')->assertExists($oldImage);

        DB::beginTransaction();
        $this->put(route('admin.produtos.update', $produto), array_replace($this->productData(), ['imagen' => $this->image()]))
            ->assertSessionHas('success');
        $newImage = $produto->fresh()->imagen;
        $this->assertNotSame($oldImage, $newImage);
        Storage::disk('public')->assertExists([$oldImage, $newImage]);
        DB::commit();
        Storage::disk('public')->assertMissing($oldImage);
        Storage::disk('public')->assertExists($newImage);

        DB::beginTransaction();
        $this->delete(route('admin.produtos.destroy', $produto))->assertSessionHas('success');
        Storage::disk('public')->assertExists($newImage);
        DB::commit();
        Storage::disk('public')->assertMissing($newImage);
        $this->assertDatabaseCount('produtos', 0);
    }

    public function test_a_failed_payment_removes_new_uploads_and_preserves_the_previous_image(): void
    {
        Storage::disk('public')->put('produtos/original.png', 'original');
        $produto = Produto::create(array_replace($this->productData(), ['imagen' => 'produtos/original.png']));
        SaidaCaixa::query();
        $dispatcher = SaidaCaixa::getEventDispatcher();
        SaidaCaixa::setEventDispatcher(clone $dispatcher);
        SaidaCaixa::creating(function (): void {
            throw new RuntimeException('Payment failed');
        });
        $this->withoutExceptionHandling();

        try {
            foreach (['post', 'put'] as $method) {
                $url = $method === 'post' ? route('admin.produtos.store') : route('admin.produtos.update', $produto);
                try {
                    $this->{$method}($url, array_replace($this->productData(), [
                        'estado' => 'consignado', 'consignado_pago' => 1, 'imagen' => $this->image(),
                    ]));
                    $this->fail('Expected payment failure');
                } catch (RuntimeException $exception) {
                    $this->assertSame('Payment failed', $exception->getMessage());
                }

                $this->assertSame(['produtos/original.png'], Storage::disk('public')->allFiles());
                $this->assertSame('produtos/original.png', $produto->fresh()->imagen);
                $this->assertDatabaseCount('produtos', 1);
                $this->assertDatabaseCount('saida_caixas', 0);
            }
        } finally {
            SaidaCaixa::setEventDispatcher($dispatcher);
        }
    }

    public function test_rolling_back_a_surrounding_transaction_removes_new_uploads(): void
    {
        Storage::disk('public')->put('produtos/original.png', 'original');
        $produto = Produto::create(array_replace($this->productData(), ['imagen' => 'produtos/original.png']));

        foreach (['post', 'put'] as $method) {
            DB::beginTransaction();
            $url = $method === 'post' ? route('admin.produtos.store') : route('admin.produtos.update', $produto);
            $this->{$method}($url, array_replace($this->productData(), ['imagen' => $this->image()]))
                ->assertSessionHas('success');
            $newImage = Produto::latest('id')->firstOrFail()->imagen;
            Storage::disk('public')->assertExists(['produtos/original.png', $newImage]);

            DB::rollBack();

            Storage::disk('public')->assertMissing($newImage);
            Storage::disk('public')->assertExists('produtos/original.png');
            $this->assertSame('produtos/original.png', $produto->fresh()->imagen);
            $this->assertDatabaseCount('produtos', 1);
        }
    }

    public function test_failed_deletion_preserves_the_image(): void
    {
        Storage::disk('public')->put('produtos/original.png', 'original');
        $produto = Produto::create(array_replace($this->productData(), ['imagen' => 'produtos/original.png']));
        $dispatcher = Produto::getEventDispatcher();
        Produto::setEventDispatcher(clone $dispatcher);
        Produto::deleting(function (): void {
            throw new RuntimeException('Deletion failed');
        });
        $this->withoutExceptionHandling();

        try {
            $this->delete(route('admin.produtos.destroy', $produto));
            $this->fail('Expected deletion failure');
        } catch (RuntimeException $exception) {
            $this->assertSame('Deletion failed', $exception->getMessage());
        } finally {
            Produto::setEventDispatcher($dispatcher);
        }

        Storage::disk('public')->assertExists('produtos/original.png');
        $this->assertDatabaseHas('produtos', ['id' => $produto->id]);
    }

    public function test_image_cleanup_cannot_delete_files_outside_the_product_directory(): void
    {
        Storage::disk('public')->put('outside.txt', 'keep');

        foreach (['outside.txt', 'produtos/../outside.txt', 'produtos\\..\\outside.txt'] as $path) {
            $produto = Produto::create(array_replace($this->productData(), ['imagen' => $path]));
            $this->delete(route('admin.produtos.destroy', $produto))->assertSessionHas('success');
            Storage::disk('public')->assertExists('outside.txt');
        }
    }

    private function image(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('product.png', base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aGcsAAAAASUVORK5CYII='
        ));
    }

    private function productData(): array
    {
        return [
            'nome' => 'Produto', 'tipo_produto' => 'roupa', 'estado' => 'novo',
            'preco_compra' => 10, 'preco_venda' => 30, 'status' => 'disponivel',
        ];
    }
}
