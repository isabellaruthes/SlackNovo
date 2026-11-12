<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Cor;
use App\Models\Fornecedor;
use App\Models\Material;
use App\Models\Produto;
use App\Models\User;
use App\Models\Venda;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * SLK-09 — standard components and visual states: name-only registers share one page
 * pattern (labelled fields, empty state, named delete confirmation, Bootstrap pagination,
 * error on the right row), product status uses one badge, refunded sales show no profit.
 */
class StandardComponentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->actingAs(User::create(['nome' => 'operador', 'senha' => Hash::make('secret')]));
    }

    public static function cadastros(): array
    {
        return [
            'categorias' => [Categoria::class, 'admin.categorias', 'Nova categoria', 'Nenhuma categoria cadastrada.'],
            'cores' => [Cor::class, 'admin.cores', 'Nova cor', 'Nenhuma cor cadastrada.'],
            'materiais' => [Material::class, 'admin.materiais', 'Novo material', 'Nenhum material cadastrado.'],
            'fornecedores' => [Fornecedor::class, 'admin.fornecedores', 'Novo fornecedor', 'Nenhum fornecedor cadastrado.'],
        ];
    }

    #[DataProvider('cadastros')]
    public function test_every_field_of_a_register_page_has_a_label(string $model, string $rota, string $novoRotulo): void
    {
        $item = $model::create(['nome' => 'Item exemplo']);
        $xpath = $this->xpath($this->get(route($rota.'.index'))->assertOk()->getContent());

        $this->assertSame($novoRotulo, trim($this->element($xpath, '//label[@for="cadastro_novo_nome"]')->textContent));
        $this->assertSame('Novo nome para Item exemplo', trim($this->element($xpath, '//label[@for="cadastro_nome_'.$item->id.'"]')->textContent));
        $this->assertStringContainsString('“Item exemplo”', $this->element($xpath, '//form[contains(@class, "js-confirm-delete")]')->getAttribute('data-item-label'));
    }

    #[DataProvider('cadastros')]
    public function test_an_empty_register_page_explains_what_to_do(string $model, string $rota, string $novoRotulo, string $vazio): void
    {
        $this->get(route($rota.'.index'))->assertOk()
            ->assertSee($vazio)
            ->assertSee('Use o formulário acima para adicionar.')
            ->assertDontSee('<table', false);
    }

    #[DataProvider('cadastros')]
    public function test_register_pagination_uses_the_bootstrap_component(string $model, string $rota): void
    {
        for ($i = 1; $i <= 16; $i++) {
            $model::create(['nome' => sprintf('Item %02d', $i)]);
        }

        $this->get(route($rota.'.index'))->assertOk()
            ->assertSee('class="pagination"', false)
            ->assertSee('16 registros');
    }

    public function test_a_failed_edit_highlights_only_the_row_that_was_submitted(): void
    {
        $camisa = Categoria::create(['nome' => 'Camisas']);
        $calca = Categoria::create(['nome' => 'Calças']);

        $html = $this->from(route('admin.categorias.index'))->followingRedirects()
            ->put(route('admin.categorias.update', $calca), ['nome' => '', '_origem' => 'editar-'.$calca->id])
            ->assertOk()
            ->getContent();
        $xpath = $this->xpath($html);

        $editado = $this->element($xpath, '//input[@id="cadastro_nome_'.$calca->id.'"]');
        $this->assertStringContainsString('is-invalid', $editado->getAttribute('class'));
        $this->assertSame('erro_cadastro_nome_'.$calca->id, $editado->getAttribute('aria-describedby'));
        $this->assertStringNotContainsString('is-invalid', $this->element($xpath, '//input[@id="cadastro_nome_'.$camisa->id.'"]')->getAttribute('class'));
        $this->assertStringNotContainsString('is-invalid', $this->element($xpath, '//input[@id="cadastro_novo_nome"]')->getAttribute('class'));
        $this->assertSame('Calças', $calca->fresh()->nome);
    }

    public function test_product_status_uses_the_same_badge_on_the_list_and_the_dashboard(): void
    {
        $this->produto('Camisa disponível', 'disponivel');
        $this->produto('Camisa vendida', 'vendido');

        foreach ([route('admin.produtos.index'), route('dashboard')] as $url) {
            $this->get($url)->assertOk()
                ->assertSee('<span class="badge text-bg-success">Disponível</span>', false)
                ->assertSee('<span class="badge text-bg-secondary">Vendido</span>', false)
                ->assertDontSee('>disponivel<', false);
        }
    }

    public function test_a_refunded_sale_shows_no_profit_and_a_status_badge(): void
    {
        $produto = $this->produto('Vestido', 'disponivel');
        Venda::create([
            'nome' => 'Vestido', 'id_produto' => $produto->id, 'comprador' => 'Cliente',
            'valor_unitario' => 30, 'valor_venda_total' => 30, 'valor_compra_total' => 10,
            'data_hora' => now(), 'reembolsada' => true,
        ]);

        $this->get(route('admin.caixa.index'))->assertOk()
            ->assertViewHas('registros', fn ($registros) => $registros->first()['lucro'] === null)
            ->assertSee('<span class="badge text-bg-secondary">Reembolsada</span>', false)
            ->assertDontSee('R$ 20,00');
    }

    private function produto(string $nome, string $status): Produto
    {
        return Produto::create([
            'nome' => $nome, 'tipo_produto' => 'roupa', 'estado' => 'novo',
            'preco_compra' => 10, 'preco_venda' => 30, 'status' => $status,
        ]);
    }

    private function xpath(string $html): DOMXPath
    {
        $document = new DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8">'.$html);

        return new DOMXPath($document);
    }

    private function element(DOMXPath $xpath, string $query): DOMElement
    {
        $element = $xpath->query($query)->item(0);
        $this->assertInstanceOf(DOMElement::class, $element, "Not found: {$query}");

        return $element;
    }
}
