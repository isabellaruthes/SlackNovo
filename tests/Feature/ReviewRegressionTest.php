<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Produto;
use App\Models\User;
use App\Models\Venda;
use Barryvdh\DomPDF\PDF;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ReviewRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::create(['nome' => 'review', 'senha' => Hash::make('secret')]));
    }

    public function test_pdf_paginates_a_large_history_and_preserves_accents_dates_and_refund_status(): void
    {
        config(['app.display_timezone' => 'America/Sao_Paulo']);
        $produto = $this->produto();
        for ($i = 1; $i <= 100; $i++) {
            $this->venda($produto, [
                'nome' => 'Peça ação '.$i,
                'comprador' => $i === 100 ? str_repeat('Á', 50) : 'João (á à ã â é ê í ó ô õ ú ç)',
                'reembolsada' => $i === 1,
            ]);
        }

        $pdf = $this->capturePdf();
        $response = $this->get(route('admin.relatorios.exportar'))
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');

        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertGreaterThan(1, $pdf->getDomPDF()->getCanvas()->get_page_count());
        $text = $pdf->reviewHtml;
        for ($i = 1; $i <= 100; $i++) {
            $this->assertStringContainsString('Peça ação '.$i, $text);
        }
        $this->assertStringContainsString('João (á à ã â é ê í ó ô õ ú ç)', $text);
        // Stored at 12:00 UTC, printed in the operator's timezone (SLK-09).
        $this->assertStringContainsString('08/11/2026 09:00', $text);
        $this->assertStringContainsString('Datas no fuso America/Sao_Paulo', $text);
        $this->assertStringContainsString('Reembolsada', $text);
        $this->assertStringContainsString('Ativa', $text);
        $this->assertStringContainsString('DejaVuSans', $response->getContent());
    }

    #[DataProvider('reportDateFilters')]
    public function test_pdf_date_filter_includes_the_entire_final_day(array $query, bool $includesBefore, bool $includesAfter): void
    {
        // Boundaries below are written in UTC; the operator-timezone case is covered by
        // InterfaceFeedbackMessagesTest (SLK-09).
        config(['app.display_timezone' => 'UTC']);
        $produto = $this->produto();
        $this->venda($produto, ['nome' => 'Dentro do período', 'data_hora' => '2026-11-08 23:59:59']);
        $this->venda($produto, ['nome' => 'Antes do período', 'data_hora' => '2026-11-07 23:59:59']);
        $this->venda($produto, ['nome' => 'Depois do período', 'data_hora' => '2026-11-09 00:00:00']);

        $pdf = $this->capturePdf();
        $this->get(route('admin.relatorios.exportar', $query))->assertOk();
        $text = $pdf->reviewHtml;
        $this->assertStringContainsString('Dentro do período', $text);
        $this->assertSame($includesBefore, str_contains($text, 'Antes do período'));
        $this->assertSame($includesAfter, str_contains($text, 'Depois do período'));
    }

    public static function reportDateFilters(): array
    {
        return [
            'both bounds' => [['inicio' => '2026-11-08', 'fim' => '2026-11-08'], false, false],
            'start only' => [['inicio' => '2026-11-08'], false, true],
            'end only' => [['fim' => '2026-11-08'], true, false],
            'empty start from form' => [['inicio' => '', 'fim' => '2026-11-08'], true, false],
        ];
    }

    public function test_empty_pdf_is_valid_and_explains_that_no_sales_were_found(): void
    {
        $pdf = $this->capturePdf();
        $this->get(route('admin.relatorios.exportar'))->assertOk();

        $this->assertSame(1, $pdf->getDomPDF()->getCanvas()->get_page_count());
        $this->assertStringContainsString('Nenhuma venda encontrada', $pdf->reviewHtml);
    }

    public function test_dashboard_shows_one_product_and_the_active_buyer_after_refund_and_resale_at_the_same_second(): void
    {
        $produto = $this->produto(['status' => 'vendido']);
        $this->venda($produto, ['comprador' => 'Anterior', 'reembolsada' => true]);
        $atual = $this->venda($produto, ['comprador' => 'Atual']);

        $response = $this->get(route('dashboard'))->assertOk();
        $produtos = $response->viewData('produtos');
        $this->assertCount(1, $produtos);
        $this->assertSame('Atual', $produtos->first()->comprador);

        $this->post(route('admin.caixa.reembolso', $atual))->assertSessionHas('success');
        $produtos = $this->get(route('dashboard'))->assertOk()->viewData('produtos');
        $this->assertCount(1, $produtos);
        $this->assertNull($produtos->first()->comprador);
        $this->assertNull($produtos->first()->data_venda);
    }

    public function test_dashboard_uses_id_to_break_a_tie_between_legacy_active_sales(): void
    {
        $produto = $this->produto(['status' => 'vendido']);
        $this->venda($produto, ['comprador' => 'Primeiro']);
        $this->venda($produto, ['comprador' => 'Último']);

        $produtos = $this->get(route('dashboard'))->assertOk()->viewData('produtos');
        $this->assertCount(1, $produtos);
        $this->assertSame('Último', $produtos->first()->comprador);
    }

    #[DataProvider('invalidDashboardFilters')]
    public function test_dashboard_rejects_invalid_filters_and_redirects_to_a_safe_url(array $query, string $field): void
    {
        $url = route('dashboard', $query);
        $this->from($url)->get($url)
            ->assertRedirect(route('dashboard'))
            ->assertSessionHasErrors($field);
        $this->get(route('dashboard'))->assertOk();
    }

    public static function invalidDashboardFilters(): array
    {
        return [
            'array query' => [['q' => ['texto']], 'q'],
            'long query' => [['q' => str_repeat('x', 101)], 'q'],
            'array status' => [['status' => ['vendido']], 'status'],
            'unknown status' => [['status' => 'desconhecido'], 'status'],
            'invalid category' => [['categoria' => 'abc'], 'categoria'],
            'array category' => [['categoria' => ['1']], 'categoria'],
            'array size' => [['tamanho' => ['p']], 'tamanho'],
        ];
    }

    public function test_dashboard_keeps_valid_combined_filters_working(): void
    {
        $categoria = Categoria::create(['nome' => 'Categoria']);
        $esperado = $this->produto(['nome' => 'Camisa azul', 'id_categoria' => $categoria->id, 'tamanho' => 'p']);
        $this->produto(['nome' => 'Camisa azul', 'id_categoria' => $categoria->id, 'tamanho' => 'g']);
        $this->produto(['nome' => 'Camisa azul', 'id_categoria' => $categoria->id, 'tamanho' => 'p', 'status' => 'vendido']);

        $produtos = $this->get(route('dashboard', [
            'q' => 'Camisa', 'status' => 'disponivel', 'categoria' => $categoria->id, 'tamanho' => 'p',
        ]))->assertOk()->viewData('produtos');

        $this->assertSame([$esperado->id], $produtos->pluck('id')->all());
    }

    private function capturePdf(): PDF
    {
        $pdf = new class(app('dompdf'), app('config'), app('files'), app('view')) extends PDF
        {
            public string $reviewHtml = '';

            public function loadHTML(string $string, ?string $encoding = null): self
            {
                $this->reviewHtml = $string;

                return parent::loadHTML($string, $encoding);
            }
        };
        $this->app->instance('dompdf.wrapper', $pdf);

        return $pdf;
    }

    private function produto(array $overrides = []): Produto
    {
        return Produto::create(array_replace([
            'nome' => 'Produto', 'tipo_produto' => 'roupa', 'preco_compra' => 10,
            'preco_venda' => 20, 'status' => 'disponivel',
        ], $overrides));
    }

    private function venda(Produto $produto, array $overrides = []): Venda
    {
        return Venda::create(array_replace([
            'nome' => 'Venda', 'id_produto' => $produto->id, 'comprador' => 'Cliente',
            'valor_unitario' => 20, 'valor_venda_total' => 20, 'valor_compra_total' => 10,
            'data_hora' => '2026-11-08 12:00:00', 'reembolsada' => false,
        ], $overrides));
    }
}
