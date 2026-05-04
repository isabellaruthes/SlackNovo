<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Produto;
use App\Models\SaidaCaixa;
use App\Models\Venda;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(): View
    {
        $inicioJanela = Carbon::now()->startOfMonth()->subMonths(5);

        $vendasPorMes = Venda::query()
            ->where('data_hora', '>=', $inicioJanela)
            ->get(['data_hora'])
            ->groupBy(fn (Venda $venda) => Carbon::parse($venda->data_hora)->format('Y-m'))
            ->map(fn ($grupo) => $grupo->count());

        $meses = collect(range(0, 5))->map(fn (int $i) => Carbon::now()->startOfMonth()->subMonths(5 - $i));
        $labelsMeses = $meses->map(fn (Carbon $data) => $data->translatedFormat('M/Y'));
        $totaisMeses = $meses->map(fn (Carbon $data) => (int) ($vendasPorMes[$data->format('Y-m')] ?? 0));

        $entradas = (float) Venda::sum('valor_venda_total');
        $saidas = (float) SaidaCaixa::sum('valor');

        $produtos = Produto::query()
            ->with(['categoria'])
            ->leftJoinSub(
                Venda::query()
                    ->select('id_produto', DB::raw('MAX(data_hora) as ultima_venda_data'))
                    ->groupBy('id_produto'),
                'ult_venda',
                'ult_venda.id_produto',
                '=',
                'produtos.id'
            )
            ->leftJoin('vendas as venda_final', function ($join): void {
                $join->on('venda_final.id_produto', '=', 'produtos.id')
                    ->on('venda_final.data_hora', '=', 'ult_venda.ultima_venda_data');
            })
            ->select('produtos.*', 'venda_final.comprador', 'venda_final.data_hora as data_venda')
            ->orderByDesc('produtos.created_at')
            ->get();

        return view('admin.dashboard.index', [
            'labelsMeses' => $labelsMeses,
            'totaisMeses' => $totaisMeses,
            'entradas' => $entradas,
            'saidas' => $saidas,
            'saldo' => $entradas - $saidas,
            'produtos' => $produtos,
        ]);
    }
}