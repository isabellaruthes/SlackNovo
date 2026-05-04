<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Produto;
use App\Models\SaidaCaixa;
use App\Models\Venda;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index(): View
    {
        $colunaDataVenda = Schema::hasColumn('vendas', 'data_hora') ? 'data_hora' : 'created_at';
        $colunaCompradorVenda = Schema::hasColumn('vendas', 'comprador') ? 'comprador' : null;
        $colunaValorVenda = Schema::hasColumn('vendas', 'valor_venda_total') ? 'valor_venda_total' : null;
        $colunaValorSaida = Schema::hasColumn('saida_caixas', 'valor') ? 'valor' : null;
        $vendasTemIdProduto = Schema::hasColumn('vendas', 'id_produto');
        $vendasTemData = Schema::hasColumn('vendas', $colunaDataVenda);
        $podeRelacionarVendaProduto = $vendasTemIdProduto && $vendasTemData;
        $inicioJanela = Carbon::now()->startOfMonth()->subMonths(5);

        $vendasPorMes = collect();
        if ($vendasTemData) {
            $vendasPorMes = Venda::query()
                ->where($colunaDataVenda, '>=', $inicioJanela)
                ->get([$colunaDataVenda])
                ->groupBy(fn (Venda $venda) => Carbon::parse($venda->{$colunaDataVenda})->format('Y-m'))
                ->map(fn ($grupo) => $grupo->count());
        }

        $meses = collect(range(0, 5))->map(fn (int $i) => Carbon::now()->startOfMonth()->subMonths(5 - $i));
        $labelsMeses = $meses->map(fn (Carbon $data) => $data->translatedFormat('M/Y'));
        $totaisMeses = $meses->map(fn (Carbon $data) => (int) ($vendasPorMes[$data->format('Y-m')] ?? 0));

        $entradas = $colunaValorVenda ? (float) Venda::sum($colunaValorVenda) : 0.0;
        $saidas = $colunaValorSaida ? (float) SaidaCaixa::sum($colunaValorSaida) : 0.0;

        $produtosQuery = Produto::query()->with(['categoria']);

        if ($podeRelacionarVendaProduto) {
            $produtosQuery
                ->leftJoinSub(
                    Venda::query()
                        ->select('id_produto', DB::raw('MAX('.$colunaDataVenda.') as ultima_venda_data'))
                        ->groupBy('id_produto'),
                    'ult_venda',
                    'ult_venda.id_produto',
                    '=',
                    'produtos.id'
                )
                ->leftJoin('vendas as venda_final', function ($join) use ($colunaDataVenda): void {
                    $join->on('venda_final.id_produto', '=', 'produtos.id')
                        ->on('venda_final.'.$colunaDataVenda, '=', 'ult_venda.ultima_venda_data');
                });
        }

        $produtosQuery->select('produtos.*');
        $produtosQuery->addSelect($colunaCompradorVenda ? 'venda_final.'.$colunaCompradorVenda.' as comprador' : DB::raw('NULL as comprador'));
        $produtosQuery->addSelect($podeRelacionarVendaProduto ? 'venda_final.'.$colunaDataVenda.' as data_venda' : DB::raw('NULL as data_venda'));

        $produtos = $produtosQuery
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