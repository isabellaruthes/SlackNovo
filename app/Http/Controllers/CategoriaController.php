<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoriaController extends Controller
{
    public function index(): View
    {
        return view('admin.categorias.index', ['categorias' => Categoria::orderBy('nome')->paginate(15)]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['nome' => ['required', 'string', 'max:50']]);
        Categoria::create($data);

        return back()->with('success', 'Categoria criada com sucesso.');
    }

    public function update(Request $request, Categoria $categoria): RedirectResponse
    {
        $data = $request->validate(['nome' => ['required', 'string', 'max:50']]);
        $categoria->update($data);

        return back()->with('success', 'Categoria atualizada com sucesso.');
    }

    public function destroy(Categoria $categoria): RedirectResponse
    {
        $categoria->delete();

        return back()->with('success', 'Categoria removida com sucesso.');
    }
}