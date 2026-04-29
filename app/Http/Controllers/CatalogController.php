<?php

namespace App\Http\Controllers;

use App\Models\Produto;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function index(Request $request): View
    {
        $busca = trim((string) $request->string('q'));
        $ordenar = (string) $request->string('ordenar', 'default');

        $produtos = Produto::query()
            ->with(['categoria:id,nome', 'cor:id,nome', 'material:id,nome'])
            ->where('status', 'disponivel')
            ->when($busca !== '', fn ($query) => $query->where('nome', 'like', "%{$busca}%"))
            ->when($ordenar === 'price_asc', fn ($query) => $query->orderBy('preco_venda'))
            ->when($ordenar === 'price_desc', fn ($query) => $query->orderByDesc('preco_venda'))
            ->when($ordenar === 'name_asc', fn ($query) => $query->orderBy('nome'))
            ->when($ordenar === 'name_desc', fn ($query) => $query->orderByDesc('nome'))
            ->when($ordenar === 'default', fn ($query) => $query->latest('id'))
            ->get();

        return view('catalog.dashboard', [
            'produtos' => $produtos,
            'busca' => $busca,
            'ordenar' => $ordenar,
        ]);
    }
}