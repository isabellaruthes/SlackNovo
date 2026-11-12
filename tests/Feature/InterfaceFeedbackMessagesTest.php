<?php

namespace Tests\Feature;

use App\Models\Produto;
use App\Models\User;
use App\Models\Venda;
use Barryvdh\DomPDF\PDF;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * SLK-09 — messages and feedback: validation in Portuguese with readable field names,
 * the invalid field highlighted with its own message, typed values kept and dates
 * shown in the operator's timezone (stored values stay in UTC).
 */
class InterfaceFeedbackMessagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->actingAs(User::create(['nome' => 'operador', 'senha' => Hash::make('secret')]));
    }

    public function test_validation_messages_are_in_portuguese_with_readable_field_names(): void
    {
        $this->from(route('admin.produtos.create'))
            ->post(route('admin.produtos.store'), ['tipo_produto' => 'roupa'])
            ->assertSessionHasErrors([
                'nome' => 'O campo nome é obrigatório.',
                'preco_compra' => 'O campo preço de compra é obrigatório.',
                'preco_venda' => 'O campo preço de venda é obrigatório.',
            ]);
    }

    public function test_money_format_errors_explain_the_expected_format(): void
    {
        $this->from(route('admin.caixa.index'))
            ->post(route('admin.caixa.saida'), ['valor' => '-5', 'motivo' => 'Teste'])
            ->assertSessionHasErrors([
                'valor' => 'Informe o valor da saída como um número positivo com até 2 casas decimais (ex.: 10.50).',
            ]);
    }

    public function test_the_invalid_product_field_is_highlighted_with_its_own_message(): void
    {
        $html = $this->from(route('admin.produtos.create'))->followingRedirects()
            ->post(route('admin.produtos.store'), ['tipo_produto' => 'roupa', 'nome' => 'Camisa', 'preco_compra' => '10'])
            ->assertOk()
            ->assertSee('Revise os dados informados:')
            ->getContent();
        $xpath = $this->xpath($html);

        $venda = $this->element($xpath, '//input[@id="produto_preco_venda"]');
        $this->assertStringContainsString('is-invalid', $venda->getAttribute('class'));
        $this->assertSame('true', $venda->getAttribute('aria-invalid'));
        $this->assertSame('erro_preco_venda', $venda->getAttribute('aria-describedby'));
        $this->assertSame('O campo preço de venda é obrigatório.', trim($this->element($xpath, '//*[@id="erro_preco_venda"]')->textContent));

        $nome = $this->element($xpath, '//input[@id="produto_nome"]');
        $this->assertStringNotContainsString('is-invalid', $nome->getAttribute('class'));
        $this->assertSame('Camisa', $nome->getAttribute('value'));
    }

    public function test_cash_forms_keep_typed_values_and_highlight_the_invalid_field(): void
    {
        $html = $this->from(route('admin.caixa.index'))->followingRedirects()
            ->post(route('admin.caixa.saida'), ['valor' => '12.345', 'motivo' => 'Compra de cabides'])
            ->assertOk()
            ->getContent();
        $xpath = $this->xpath($html);

        $valor = $this->element($xpath, '//input[@id="saida_valor"]');
        $this->assertStringContainsString('is-invalid', $valor->getAttribute('class'));
        $this->assertSame('12.345', $valor->getAttribute('value'));
        $this->assertSame('Compra de cabides', $this->element($xpath, '//input[@id="saida_motivo"]')->getAttribute('value'));
        $this->assertNotNull($xpath->query('//*[@id="erro_valor"]')->item(0));
    }

    public function test_dates_are_shown_in_the_operator_timezone_without_changing_stored_values(): void
    {
        config(['app.display_timezone' => 'America/Sao_Paulo']);
        $produto = Produto::create([
            'nome' => 'Vestido', 'tipo_produto' => 'roupa', 'estado' => 'novo',
            'preco_compra' => 10, 'preco_venda' => 30, 'status' => 'vendido',
        ]);
        $venda = Venda::create([
            'nome' => 'Vestido', 'id_produto' => $produto->id, 'comprador' => 'Cliente',
            'valor_unitario' => 30, 'valor_venda_total' => 30, 'valor_compra_total' => 10,
            'data_hora' => Carbon::parse('2026-11-12 15:00:00', 'UTC'), 'reembolsada' => false,
        ]);

        $this->get(route('admin.caixa.index'))->assertOk()->assertSee('12/11/2026 12:00');
        $this->get(route('dashboard'))->assertOk()->assertSee('12/11/2026 12:00');
        $this->assertSame('2026-11-12 15:00:00', $venda->fresh()->getRawOriginal('data_hora'));
    }

    public function test_the_report_period_uses_the_same_timezone_as_the_printed_dates(): void
    {
        config(['app.display_timezone' => 'America/Sao_Paulo']);
        $produto = Produto::create([
            'nome' => 'Vestido', 'tipo_produto' => 'roupa', 'estado' => 'novo',
            'preco_compra' => 10, 'preco_venda' => 30, 'status' => 'disponivel',
        ]);
        $venda = fn (string $nome, string $utc) => Venda::create([
            'nome' => $nome, 'id_produto' => $produto->id, 'comprador' => 'Cliente',
            'valor_unitario' => 30, 'valor_venda_total' => 30, 'valor_compra_total' => 10,
            'data_hora' => $utc, 'reembolsada' => true,
        ]);
        $venda('Primeiro minuto do dia', '2026-11-08 03:00:00');   // 08/11 00:00 em São Paulo
        $venda('Último segundo do dia', '2026-11-09 02:59:59');    // 08/11 23:59 em São Paulo
        $venda('Noite anterior', '2026-11-08 02:59:59');           // 07/11 23:59 em São Paulo
        $venda('Madrugada seguinte', '2026-11-09 03:00:00');       // 09/11 00:00 em São Paulo

        $pdf = new class(app('dompdf'), app('config'), app('files'), app('view')) extends PDF
        {
            public string $html = '';

            public function loadHTML(string $string, ?string $encoding = null): self
            {
                $this->html = $string;

                return parent::loadHTML($string, $encoding);
            }
        };
        $this->app->instance('dompdf.wrapper', $pdf);

        $this->get(route('admin.relatorios.exportar', ['inicio' => '2026-11-08', 'fim' => '2026-11-08']))->assertOk();

        $this->assertStringContainsString('Primeiro minuto do dia', $pdf->html);
        $this->assertStringContainsString('08/11/2026 00:00', $pdf->html);
        $this->assertStringContainsString('Último segundo do dia', $pdf->html);
        $this->assertStringContainsString('08/11/2026 23:59', $pdf->html);
        $this->assertStringNotContainsString('Noite anterior', $pdf->html);
        $this->assertStringNotContainsString('Madrugada seguinte', $pdf->html);
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
