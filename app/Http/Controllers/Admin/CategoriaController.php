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
        return view('admin.categorias.index', ['categorias' => Categoria::query()->orderBy('nome')->paginate(15)]);
    }

    public function store(Request $request): RedirectResponse
    {
        Categoria::create($this->validatedData($request));

        return $this->redirectWithSuccess('Categoria criada com sucesso.');
    }

    public function update(Request $request, Categoria $categoria): RedirectResponse
    {
        $categoria->update($this->validatedData($request));

        return $this->redirectWithSuccess('Categoria atualizada com sucesso.');
    }

    public function destroy(Categoria $categoria): RedirectResponse
    {
        $categoria->delete();

        return $this->redirectWithSuccess('Categoria removida com sucesso.');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate(['nome' => ['required', 'string', 'max:50']]);
    }

    private function redirectWithSuccess(string $message): RedirectResponse
    {
        return back()->with('success', $message);
    }
}