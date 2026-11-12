<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Produto;
use App\Models\Venda;
use App\Support\DataHora;
use Barryvdh\DomPDF\Facade\Pdf;
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
            // The period is chosen in the operator's timezone, the same one used to print the dates.
            ->when($inicio, fn ($q) => $q->where($colunaDataVenda, '>=', DataHora::inicioDoDia($inicio)))
            ->when($fim, fn ($q) => $q->where($colunaDataVenda, '<=', DataHora::fimDoDia($fim)))
            ->orderBy($colunaDataVenda)
            ->get();

        return Pdf::loadView('admin.relatorios.vendas', [
            'registros' => $registros,
            'colunaDataVenda' => $colunaDataVenda,
            'inicio' => $inicio,
            'fim' => $fim,
        ])->setPaper('a4')->download('relatorio-vendas.pdf');
    }
}
