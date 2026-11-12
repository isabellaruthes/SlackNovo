<?php

namespace Tests\Feature;

use App\Models\Produto;
use App\Models\User;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * SLK-07 / BUG-09 — the product form has one size select for clothes and another for shoes,
 * both named "tamanho". The inactive one used to be disabled only by JavaScript, so a browser
 * without JavaScript (or with a script error) submitted the empty shoe select and erased the size.
 */
class ProductFormTamanhoWithoutJavaScriptTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->actingAs(User::create(['nome' => 'operador', 'senha' => Hash::make('secret')]));
    }

    #[DataProvider('produtos')]
    public function test_saving_the_edit_form_without_javascript_preserves_the_size(string $tipo, string $tamanho): void
    {
        $produto = $this->produto(['tipo_produto' => $tipo, 'tamanho' => $tamanho]);

        $html = $this->get(route('admin.produtos.edit', $produto))->assertOk()->getContent();
        $fields = $this->formValues($html, route('admin.produtos.update', $produto));
        $this->assertSame($tamanho, $fields['tamanho']);

        $this->put(route('admin.produtos.update', $produto), $fields)
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $this->assertSame($tamanho, $produto->fresh()->tamanho);
    }

    public static function produtos(): array
    {
        return [
            'roupa' => ['roupa', 'm'],
            'calçado' => ['calcado', '38'],
        ];
    }

    public function test_the_create_form_only_enables_the_select_of_the_default_type(): void
    {
        $html = $this->get(route('admin.produtos.create'))->assertOk()->getContent();
        $xpath = $this->xpath($html);

        $this->assertFalse($this->select($xpath, 'tamanho_roupa')->hasAttribute('disabled'));
        $this->assertTrue($this->select($xpath, 'tamanho_calcado')->hasAttribute('disabled'));
    }

    public function test_the_size_select_of_the_submitted_type_is_kept_after_a_validation_error(): void
    {
        $html = $this->from(route('admin.produtos.create'))->followingRedirects()
            ->post(route('admin.produtos.store'), [
                'nome' => '', 'tipo_produto' => 'calcado', 'tamanho' => '40',
                'preco_compra' => 10, 'preco_venda' => 30,
            ])
            ->assertOk()
            ->getContent();

        $fields = $this->formValues($html, route('admin.produtos.store'));
        $this->assertSame('calcado', $fields['tipo_produto']);
        $this->assertSame('40', $fields['tamanho']);
    }

    #[DataProvider('tamanhosIncompativeis')]
    public function test_a_size_from_the_other_product_type_is_rejected(string $tipo, string $tamanho): void
    {
        $produto = $this->produto(['tipo_produto' => 'roupa', 'tamanho' => 'm']);

        $this->from(route('admin.produtos.edit', $produto))
            ->put(route('admin.produtos.update', $produto), [
                'nome' => 'Produto', 'tipo_produto' => $tipo, 'tamanho' => $tamanho, 'estado' => 'novo',
                'preco_compra' => 10, 'preco_venda' => 30, 'status' => 'disponivel',
            ])
            ->assertSessionHasErrors('tamanho');

        $this->assertSame('m', $produto->fresh()->tamanho);
    }

    public static function tamanhosIncompativeis(): array
    {
        return [
            'calçado com tamanho de roupa' => ['calcado', 'm'],
            'roupa com numeração de calçado' => ['roupa', '38'],
        ];
    }

    private function produto(array $overrides = []): Produto
    {
        return Produto::create($overrides + [
            'nome' => 'Produto', 'tipo_produto' => 'roupa', 'estado' => 'novo',
            'preco_compra' => 10, 'preco_venda' => 30, 'status' => 'disponivel',
        ]);
    }

    private function xpath(string $html): DOMXPath
    {
        $document = new DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8">'.$html);

        return new DOMXPath($document);
    }

    private function select(DOMXPath $xpath, string $id): DOMElement
    {
        $select = $xpath->query(sprintf('//select[@id="%s"]', $id))->item(0);
        $this->assertInstanceOf(DOMElement::class, $select);

        return $select;
    }

    /**
     * Serialize a rendered form as a browser without JavaScript would:
     * disabled controls are skipped and a later control overrides an earlier one with the same name.
     */
    private function formValues(string $html, string $action): array
    {
        $xpath = $this->xpath($html);
        $values = [];

        foreach ($xpath->query(sprintf('//form[@action="%s"]//*[@name]', $action)) as $element) {
            /** @var DOMElement $element */
            $name = $element->getAttribute('name');
            $type = strtolower($element->getAttribute('type'));

            if ($element->hasAttribute('disabled') || $type === 'file') {
                continue;
            }

            if (in_array($type, ['radio', 'checkbox'], true)) {
                if ($element->hasAttribute('checked')) {
                    $values[$name] = $element->getAttribute('value');
                }

                continue;
            }

            $values[$name] = match ($element->nodeName) {
                'select' => $this->selectedOption($xpath, $element),
                'textarea' => $element->textContent,
                default => $element->getAttribute('value'),
            };
        }

        return $values;
    }

    private function selectedOption(DOMXPath $xpath, DOMElement $select): string
    {
        $option = $xpath->query('.//option[@selected]', $select)->item(0)
            ?? $xpath->query('.//option', $select)->item(0);

        return $option?->getAttribute('value') ?? '';
    }
}
