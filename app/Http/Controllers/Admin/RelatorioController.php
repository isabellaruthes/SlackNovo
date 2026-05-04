<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Produto;
use App\Models\SaidaCaixa;
use App\Models\Venda;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

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

        if ($data['tipo'] === 'saidas') {
            $registros = SaidaCaixa::query()
                ->when($inicio, fn ($q) => $q->whereDate('data_saidacaixa', '>=', $inicio))
                ->when($fim, fn ($q) => $q->whereDate('data_saidacaixa', '<=', $fim))
                ->orderBy('data_saidacaixa')
                ->get();
        } else {
            $registros = Venda::query()
                ->when($inicio, fn ($q) => $q->whereDate('data_hora', '>=', $inicio))
                ->when($fim, fn ($q) => $q->whereDate('data_hora', '<=', $fim))
                ->orderBy('data_hora');

            if ($data['tipo'] === 'vendas') {
                $registros = $registros->get();
            } else {
                $registros = $registros->get(['id', 'nome', 'comprador', 'valor_venda_total', 'data_hora']);
            }
        }

        return response($registros->toJson(JSON_PRETTY_PRINT), 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="relatorio-'.$data['tipo'].'.json"',
        ]);
    }
}
