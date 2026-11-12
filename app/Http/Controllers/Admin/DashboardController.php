<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Produto;
use App\Models\SaidaCaixa;
use App\Models\Venda;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class DashboardController extends Controller
{
    private const PRODUTOS_POR_PAGINA = 24;

    public function index(Request $request): View
    {
        $validator = Validator::make($request->all(), [
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:disponivel,vendido'],
            'categoria' => ['nullable', 'integer', 'min:1'],
            'tamanho' => ['nullable', 'string', 'max:4'],
        ]);

        if ($validator->fails()) {
            // Do not redirect back to the malformed query and create a GET loop.
            throw (new ValidationException($validator))->redirectTo(route('dashboard'));
        }

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
                ->when(Schema::hasColumn('vendas', 'reembolsada'), fn ($query) => $query->where('reembolsada', false))
                ->get([$colunaDataVenda])
                ->groupBy(fn (Venda $venda) => Carbon::parse($venda->{$colunaDataVenda})->format('Y-m'))
                ->map(fn ($grupo) => $grupo->count());
        }

        $meses = collect(range(0, 5))->map(fn (int $i) => Carbon::now()->startOfMonth()->subMonths(5 - $i));
        $labelsMeses = $meses->map(fn (Carbon $data) => $data->translatedFormat('M/Y'));
        $totaisMeses = $meses->map(fn (Carbon $data) => (int) ($vendasPorMes[$data->format('Y-m')] ?? 0));

        $entradas = $colunaValorVenda
            ? (float) Venda::query()
                ->when(Schema::hasColumn('vendas', 'reembolsada'), fn ($query) => $query->where('reembolsada', false))
                ->sum($colunaValorVenda)
            : 0.0;
        $saidas = $colunaValorSaida ? (float) SaidaCaixa::sum($colunaValorSaida) : 0.0;

        $produtosQuery = Produto::query()->with(['categoria']);

        $produtosQuery->select('produtos.*');

        if ($podeRelacionarVendaProduto) {
            $vendaAtual = Venda::query()
                ->whereColumn('vendas.id_produto', 'produtos.id')
                ->when(Schema::hasColumn('vendas', 'reembolsada'), fn ($query) => $query->where('reembolsada', false))
                ->orderByDesc($colunaDataVenda)
                ->orderByDesc('id')
                ->limit(1);

            $produtosQuery->addSelect([
                'data_venda' => (clone $vendaAtual)->select($colunaDataVenda),
            ]);
            if ($colunaCompradorVenda !== null) {
                $produtosQuery->addSelect([
                    'comprador' => (clone $vendaAtual)->select($colunaCompradorVenda),
                ]);
            } else {
                $produtosQuery->addSelect(DB::raw('NULL as comprador'));
            }
        } else {
            $produtosQuery->addSelect(DB::raw('NULL as comprador'), DB::raw('NULL as data_venda'));
        }

        $busca = trim((string) $request->string('q'));
        $status = (string) $request->string('status', '');
        $categoria = (string) $request->string('categoria', '');
        $tamanho = (string) $request->string('tamanho', '');

        $produtos = $produtosQuery
            ->when($busca !== '', fn ($query) => $query->where('produtos.nome', 'like', "%{$busca}%"))
            ->when($status !== '', fn ($query) => $query->where('produtos.status', $status))
            ->when($categoria !== '', fn ($query) => $query->where('produtos.id_categoria', $categoria))
            ->when($tamanho !== '', fn ($query) => $query->where('produtos.tamanho', $tamanho))
            ->orderByDesc('produtos.created_at')
            // Stable order across pages when several products share created_at.
            ->orderByDesc('produtos.id')
            ->paginate(self::PRODUTOS_POR_PAGINA)
            ->withQueryString();

        return view('admin.dashboard.index', [
            'labelsMeses' => $labelsMeses,
            'totaisMeses' => $totaisMeses,
            'entradas' => $entradas,
            'saidas' => $saidas,
            'saldo' => $entradas - $saidas,
            'produtos' => $produtos,
            'busca' => $busca,
            'status' => $status,
            'categoria' => $categoria,
            'tamanho' => $tamanho,
            'categorias' => Produto::query()
                ->join('categorias', 'categorias.id', '=', 'produtos.id_categoria')
                ->select('categorias.id', 'categorias.nome')
                ->distinct()
                ->orderBy('categorias.nome')
                ->get(),
            'tamanhos' => Produto::query()
                ->whereNotNull('tamanho')
                ->where('tamanho', '!=', '')
                ->select('tamanho')
                ->distinct()
                ->orderBy('tamanho')
                ->pluck('tamanho'),
        ]);
    }
}
