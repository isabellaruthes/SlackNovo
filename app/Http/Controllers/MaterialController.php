<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Material;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MaterialController extends Controller
{
    public function index(): View
    {
        return view('admin.materiais.index', ['materiais' => Material::orderBy('nome')->paginate(15)]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['nome' => ['required', 'string', 'max:50']]);
        Material::create($data);

        return back()->with('success', 'Material criado com sucesso.');
    }

    public function update(Request $request, Material $materiai): RedirectResponse
    {
        $data = $request->validate(['nome' => ['required', 'string', 'max:50']]);
        $materiai->update($data);

        return back()->with('success', 'Material atualizado com sucesso.');
    }

    public function destroy(Material $materiai): RedirectResponse
    {
        $materiai->delete();

        return back()->with('success', 'Material removido com sucesso.');
    }
}