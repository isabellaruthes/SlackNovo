<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Produto;
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
}