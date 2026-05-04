<?php

namespace App\Http\Controllers;

use App\Models\Produto;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function index(Request $request): View
    {
        $busca = trim((string) $request->string('q'));
        $ordenar = (string) $request->string('ordenar', 'default');
        $categoria = (string) $request->string('categoria', '');

        $produtos = Produto::query()
            ->with(['categoria:id,nome', 'cor:id,nome', 'material:id,nome'])
            ->where('status', 'disponivel')
            ->when(
                $busca !== '',
                fn (Builder $query) => $query->where(function (Builder $subQuery) use ($busca): void {
                    $subQuery->where('nome', 'like', "%{$busca}%")
                        ->orWhere('descricao', 'like', "%{$busca}%")
                        ->orWhere('id', 'like', "%{$busca}%");
                })
            )
            ->when($categoria !== '', fn (Builder $query) => $query->where('id_categoria', $categoria))
            ->when($ordenar === 'price_asc', fn ($query) => $query->orderBy('preco_venda'))
            ->when($ordenar === 'price_desc', fn ($query) => $query->orderByDesc('preco_venda'))
            ->when($ordenar === 'name_asc', fn ($query) => $query->orderBy('nome'))
            ->when($ordenar === 'name_desc', fn ($query) => $query->orderByDesc('nome'))
            ->when($ordenar === 'default', fn ($query) => $query->latest('id'))
            ->get();

        return view('catalog.dashboard', [
            'produtos' => $produtos,
            'categorias' => Produto::query()
                ->join('categorias', 'categorias.id', '=', 'produtos.id_categoria')
                ->where('produtos.status', 'disponivel')
                ->select('categorias.id', 'categorias.nome')
                ->distinct()
                ->orderBy('categorias.nome')
                ->get(),
            'busca' => $busca,
            'ordenar' => $ordenar,
            'categoria' => $categoria,
        ]);
    }
}
