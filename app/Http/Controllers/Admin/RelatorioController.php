<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Produto;
use App\Models\SaidaCaixa;
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
            'tipo' => ['required', 'in:vendas,entradas,saidas'],
            'inicio' => ['nullable', 'date'],
            'fim' => ['nullable', 'date', 'after_or_equal:inicio'],
        ]);

        $inicio = $data['inicio'] ?? null;
        $fim = $data['fim'] ?? null;

        $colunaDataVenda = Schema::hasColumn('vendas', 'data_hora') ? 'data_hora' : 'created_at';
        $colunaDataSaida = Schema::hasColumn('saida_caixas', 'data_saidacaixa') ? 'data_saidacaixa' : 'created_at';

        if ($data['tipo'] === 'saidas') {
            $registros = SaidaCaixa::query()
                ->when($inicio, fn ($q) => $q->whereDate($colunaDataSaida, '>=', $inicio))
                ->when($fim, fn ($q) => $q->whereDate($colunaDataSaida, '<=', $fim))
                ->orderBy($colunaDataSaida)
                ->get();
        } else {
            $registros = Venda::query()
                ->when($inicio, fn ($q) => $q->whereDate($colunaDataVenda, '>=', $inicio))
                ->when($fim, fn ($q) => $q->whereDate($colunaDataVenda, '<=', $fim))
                ->orderBy($colunaDataVenda);

            if ($data['tipo'] === 'vendas') {
                $registros = $registros->get();
            } else {
                $colunas = ['id'];
                foreach (['nome', 'comprador', 'valor_venda_total', $colunaDataVenda] as $coluna) {
                    if (Schema::hasColumn('vendas', $coluna)) {
                        $colunas[] = $coluna;
                    }
                }

                $registros = $registros->get(array_values(array_unique($colunas)));
            }
        }

        return response($registros->toJson(JSON_PRETTY_PRINT), 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="relatorio-'.$data['tipo'].'.json"',
        ]);
    }
}
