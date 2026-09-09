<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Produto;
use App\Models\SaidaCaixa;
use App\Models\Venda;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CaixaController extends Controller
{
    public function index(): View
    {
        $entradas = Venda::where('reembolsada', false)->sum('valor_venda_total');
        $saidas = SaidaCaixa::sum('valor');
        $vendas = Venda::with('produto')->latest('data_hora')->get();

        $registros = $vendas
            ->map(function (Venda $venda): array {
                $lucro = (float) $venda->valor_venda_total - (float) $venda->valor_compra_total;

                return [
                    'tipo' => 'venda',
                    'id' => $venda->id,
                    'data_hora' => $venda->data_hora,
                    'valor' => (float) $venda->valor_venda_total,
                    'lucro' => $lucro,
                    'comprador' => $venda->comprador,
                    'venda' => $venda,
                    'reembolsada' => (bool) $venda->reembolsada,
                ];
            })
            ->merge(
                SaidaCaixa::latest('data_saidacaixa')->get()->map(function (SaidaCaixa $saida): array {
                    return [
                        'tipo' => 'saida',
                        'id' => $saida->id,
                        'data_hora' => $saida->data_saidacaixa,
                        'valor' => (float) $saida->valor,
                        'lucro' => null,
                        'comprador' => '-',
                        'venda' => null,
                        'reembolsada' => false,
                    ];
                })
            )
            ->sortByDesc('data_hora')
            ->values();

        return view('admin.caixa.index', [
            'saldo' => $entradas - $saidas,
            'entradas' => $entradas,
            'saidas' => $saidas,
            'ultimasVendas' => Venda::latest('data_hora')->limit(10)->get(),
            'ultimasSaidas' => SaidaCaixa::latest('data_saidacaixa')->limit(10)->get(),
            'registros' => $registros,
        ]);
    }

    public function registrarSaida(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'valor' => ['required', 'numeric', 'min:0.01'],
            'motivo' => ['required', 'string', 'max:50'],
        ]);

        SaidaCaixa::create($data + ['data_saidacaixa' => Carbon::now()]);

        return back()->with('success', 'Saída registrada com sucesso.');
    }

    public function registrarVenda(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'id_produto' => ['required', 'integer', 'exists:produtos,id'],
            'comprador' => ['required', 'string', 'max:50'],
            'nome' => ['required', 'string', 'max:50'],
            'valor_venda' => ['required', 'numeric', 'min:0.01'],
        ]);

        $registrada = DB::transaction(function () use ($data): bool {
            $produto = Produto::query()->lockForUpdate()->findOrFail($data['id_produto']);

            if ($produto->status === 'vendido'
                || Venda::where('id_produto', $produto->id)->where('reembolsada', false)->exists()) {
                return false;
            }

            Venda::create([
                'nome' => $data['nome'],
                'id_produto' => $produto->id,
                'comprador' => $data['comprador'],
                'valor_unitario' => $data['valor_venda'],
                'valor_venda_total' => $data['valor_venda'],
                'valor_compra_total' => $produto->preco_compra,
                'data_hora' => Carbon::now(),
                'reembolsada' => false,
            ]);

            $produto->update(['status' => 'vendido']);

            return true;
        });

        if (! $registrada) {
            return back()->with('error', 'Este produto já foi vendido.');
        }

        return back()->with('success', 'Venda registrada com sucesso.');
    }

    public function reembolsarVenda(Venda $venda): RedirectResponse
    {
        $reembolsada = DB::transaction(function () use ($venda): bool {
            $produto = Produto::query()->lockForUpdate()->find($venda->id_produto);
            $venda = Venda::query()->lockForUpdate()->findOrFail($venda->id);

            if ($venda->reembolsada) {
                return false;
            }

            $venda->update(['reembolsada' => true]);

            if ($produto !== null
                && ! Venda::where('id_produto', $produto->id)->where('reembolsada', false)->exists()) {
                $produto->update(['status' => 'disponivel']);
            }

            return true;
        });

        if (! $reembolsada) {
            return back()->with('error', 'Essa venda já foi reembolsada.');
        }

        return back()->with('success', 'Venda reembolsada com sucesso.');
    }
}
