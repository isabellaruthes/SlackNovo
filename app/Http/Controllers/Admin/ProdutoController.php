<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Models\Cor;
use App\Models\Fornecedor;
use App\Models\Material;
use App\Models\Produto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProdutoController extends Controller
{
    public function index(): View
    {
        return view('admin.produtos.index', [
            'produtos' => Produto::with(['categoria', 'cor', 'material', 'fornecedor'])->latest('id')->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('admin.produtos.create', [
            'categorias' => Categoria::orderBy('nome')->get(),
            'cores' => Cor::orderBy('nome')->get(),
            'materiais' => Material::orderBy('nome')->get(),
            'fornecedores' => Fornecedor::orderBy('nome')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nome' => ['required', 'string', 'max:50'],
            'imagen' => ['nullable', 'string', 'max:120'],
            'estado' => ['nullable', 'in:novo,usado,consignado'],
            'tamanho' => ['nullable', 'in:pp,p,m,g,gg,g1,g2,g3,g4'],
            'preco_compra' => ['required', 'numeric'],
            'preco_venda' => ['required', 'numeric'],
            'genero' => ['nullable', 'in:masculino,feminino,unissex'],
            'status' => ['nullable', 'in:disponivel,vendido'],
            'descricao' => ['nullable', 'string'],
            'id_categoria' => ['nullable', 'integer'],
            'id_cor' => ['nullable', 'integer'],
            'id_material' => ['nullable', 'integer'],
            'id_fornecedor' => ['nullable', 'integer'],
        ]);

        Produto::create($data);

        return redirect()->route('admin.produtos.index')->with('success', 'Produto criado com sucesso.');
    }

    public function edit(Produto $produto): View
    {
        return view('admin.produtos.edit', [
            'produto' => $produto,
            'categorias' => Categoria::orderBy('nome')->get(),
            'cores' => Cor::orderBy('nome')->get(),
            'materiais' => Material::orderBy('nome')->get(),
            'fornecedores' => Fornecedor::orderBy('nome')->get(),
        ]);
    }

    public function update(Request $request, Produto $produto): RedirectResponse
    {
        $data = $request->validate([
            'nome' => ['required', 'string', 'max:50'],
            'imagen' => ['nullable', 'string', 'max:120'],
            'estado' => ['nullable', 'in:novo,usado,consignado'],
            'tamanho' => ['nullable', 'in:pp,p,m,g,gg,g1,g2,g3,g4'],
            'preco_compra' => ['required', 'numeric'],
            'preco_venda' => ['required', 'numeric'],
            'genero' => ['nullable', 'in:masculino,feminino,unissex'],
            'status' => ['nullable', 'in:disponivel,vendido'],
            'descricao' => ['nullable', 'string'],
            'id_categoria' => ['nullable', 'integer'],
            'id_cor' => ['nullable', 'integer'],
            'id_material' => ['nullable', 'integer'],
            'id_fornecedor' => ['nullable', 'integer'],
        ]);

        $produto->update($data);

        return redirect()->route('admin.produtos.index')->with('success', 'Produto atualizado com sucesso.');
    }

    public function destroy(Produto $produto): RedirectResponse
    {
        $produto->delete();

        return back()->with('success', 'Produto removido com sucesso.');
    }
}