<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CorController extends Controller
{
    public function index(): View
    {
        return view('admin.cores.index', ['cores' => Cor::orderBy('nome')->paginate(15)]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['nome' => ['required', 'string', 'max:50']]);
        Cor::create($data);

        return back()->with('success', 'Cor criada com sucesso.');
    }

    public function update(Request $request, Cor $core): RedirectResponse
    {
        $data = $request->validate(['nome' => ['required', 'string', 'max:50']]);
        $core->update($data);

        return back()->with('success', 'Cor atualizada com sucesso.');
    }

    public function destroy(Cor $core): RedirectResponse
    {
        $core->delete();

        return back()->with('success', 'Cor removida com sucesso.');
    }
}