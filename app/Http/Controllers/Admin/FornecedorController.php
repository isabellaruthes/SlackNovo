<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Fornecedor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FornecedorController extends Controller
{
    public function index(): View
    {
        return view('admin.fornecedores.index', ['fornecedores' => Fornecedor::orderBy('nome')->paginate(15)]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['nome' => ['required', 'string', 'max:255']]);
        Fornecedor::create($data);

        return back()->with('success', 'Fornecedor criado com sucesso.');
    }

    public function update(Request $request, Fornecedor $fornecedore): RedirectResponse
    {
        $data = $request->validate(['nome' => ['required', 'string', 'max:255']]);
        $fornecedore->update($data);

        return back()->with('success', 'Fornecedor atualizado com sucesso.');
    }

    public function destroy(Fornecedor $fornecedore): RedirectResponse
    {
        $fornecedore->delete();

        return back()->with('success', 'Fornecedor removido com sucesso.');
    }
}
