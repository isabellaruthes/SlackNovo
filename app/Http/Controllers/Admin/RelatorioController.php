<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Produto;
use App\Models\Venda;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Schema;

class RelatorioController extends Controller
{
    public function produtos(Request $request): Response
    {
        $produtos = Produto::query()
            ->when($request->filled('categoria'), fn ($query) => $query->where('id_categoria', (int) $request->input('categoria')))
            ->when($request->filled('genero'), fn ($query) => $query->where('genero', $request->input('genero')))
            ->when($request->filled('tamanho'), fn ($query) => $query->where('tamanho', $request->input('tamanho')))
            ->get();

        return response($produtos->toJson(JSON_PRETTY_PRINT), 200, ['Content-Type' => 'application/json']);
    }

    public function exportar(Request $request): Response
    {
        $data = $request->validate([
            'inicio' => ['nullable', 'date'],
            'fim' => ['nullable', 'date', 'after_or_equal:inicio'],
        ]);

        $inicio = $data['inicio'] ?? null;
        $fim = $data['fim'] ?? null;

        $colunaDataVenda = Schema::hasColumn('vendas', 'data_hora') ? 'data_hora' : 'created_at';
        $registros = Venda::query()
            ->when($inicio, fn ($q) => $q->whereDate($colunaDataVenda, '>=', $inicio))
            ->when($fim, fn ($q) => $q->whereDate($colunaDataVenda, '<=', $fim))
            ->orderBy($colunaDataVenda)
            ->get();

        $linhas = ['Relatório de Vendas', ''];
        foreach ($registros as $venda) {
            $linhas[] = sprintf(
                '#%s | Produto: %s | Comprador: %s | Valor: R$ %s | Data: %s',
                $venda->id,
                $venda->nome ?? ('ID '.$venda->id_produto),
                $venda->comprador ?? 'Não informado',
                number_format((float) ($venda->valor_venda_total ?? 0), 2, ',', '.'),
                optional($venda->{$colunaDataVenda})->format ? $venda->{$colunaDataVenda}->format('d/m/Y H:i') : (string) $venda->{$colunaDataVenda}
            );
        }

        if ($registros->isEmpty()) {
            $linhas[] = 'Nenhuma venda encontrada no período informado.';
        }

        $pdf = $this->gerarPdfSimples($linhas);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="relatorio-vendas.pdf"',
        ]);
    }

    private function gerarPdfSimples(array $linhas): string
    {
        $conteudo = "BT\n/F1 10 Tf\n40 800 Td\n";
        $primeira = true;
        foreach ($linhas as $linha) {
            $texto = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $linha);
            $conteudo .= $primeira ? "({$texto}) Tj\n" : "0 -14 Td\n({$texto}) Tj\n";
            $primeira = false;
        }
        $conteudo .= "ET";

        $objetos = [];
        $objetos[] = '1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj';
        $objetos[] = '2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj';
        $objetos[] = '3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >> endobj';
        $objetos[] = '4 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj';
        $objetos[] = '5 0 obj << /Length '.strlen($conteudo).' >> stream'."\n".$conteudo."\n".'endstream endobj';

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objetos as $obj) {
            $offsets[] = strlen($pdf);
            $pdf .= $obj."\n";
        }
        $xref = strlen($pdf);
        $pdf .= 'xref'."\n".'0 '.(count($objetos) + 1)."\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= count($objetos); $i++) {
            $pdf .= sprintf('%010d 00000 n ', $offsets[$i])."\n";
        }
        $pdf .= 'trailer << /Size '.(count($objetos) + 1).' /Root 1 0 R >>'."\n";
        $pdf .= 'startxref'."\n".$xref."\n%%EOF";

        return $pdf;
    }
}
