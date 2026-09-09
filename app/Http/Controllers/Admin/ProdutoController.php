<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Models\Cor;
use App\Models\Fornecedor;
use App\Models\Material;
use App\Models\Produto;
use App\Models\SaidaCaixa;
use App\Models\Venda;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
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
            'imagen' => ['nullable', 'image', 'max:5120'],
            'estado' => ['nullable', 'in:novo,usado,consignado'],
            'tipo_produto' => ['required', 'in:roupa,calcado'],
            'tamanho' => ['nullable', 'in:pp,p,m,g,gg,g1,g2,g3,g4,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37,38,39,40,41,42,43,44,45,46'],
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

        if ($this->suportaCamposConsignado()) {
            $data['cliente_consignado'] = $request->input('cliente_consignado');
            $data['consignado_pago'] = $request->boolean('consignado_pago');
        }

        if ($request->hasFile('imagen')) {
            $data['imagen'] = $request->file('imagen')->store('produtos', 'public');
        }

        if ($this->suportaCamposConsignado() && ($data['estado'] ?? null) !== 'consignado') {
            $data['cliente_consignado'] = null;
            $data['consignado_pago'] = false;
        }

        DB::transaction(function () use ($data): void {
            $produto = Produto::create($data);

            if ($this->suportaCamposConsignado()) {
                $this->registrarSaidaConsignadoSeNecessario($produto);
            }
        });

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
            'imagen' => ['nullable', 'image', 'max:5120'],
            'estado' => ['nullable', 'in:novo,usado,consignado'],
            'tipo_produto' => ['required', 'in:roupa,calcado'],
            'tamanho' => ['nullable', 'in:pp,p,m,g,gg,g1,g2,g3,g4,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37,38,39,40,41,42,43,44,45,46'],
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

        if ($this->suportaCamposConsignado()) {
            $data['cliente_consignado'] = $request->input('cliente_consignado');
            $data['consignado_pago'] = $request->boolean('consignado_pago');
        }

        if ($request->hasFile('imagen')) {
            $data['imagen'] = $request->file('imagen')->store('produtos', 'public');
        }

        if ($this->suportaCamposConsignado() && ($data['estado'] ?? null) !== 'consignado') {
            $data['cliente_consignado'] = null;
            $data['consignado_pago'] = false;
        }

        DB::transaction(function () use ($data, $produto): void {
            $produto = Produto::query()->lockForUpdate()->findOrFail($produto->id);

            if (array_key_exists('status', $data) && $data['status'] !== 'vendido'
                && Venda::where('id_produto', $produto->id)->where('reembolsada', false)->exists()) {
                throw ValidationException::withMessages([
                    'status' => 'Reembolse a venda antes de disponibilizar o produto novamente.',
                ]);
            }

            $produto->update($data);

            if ($this->suportaCamposConsignado()) {
                $this->registrarSaidaConsignadoSeNecessario($produto);
            }
        });

        return redirect()->route('admin.produtos.index')->with('success', 'Produto atualizado com sucesso.');
    }

    private function suportaCamposConsignado(): bool
    {
        return Schema::hasColumn('produtos', 'cliente_consignado')
            && Schema::hasColumn('produtos', 'consignado_pago');
    }

    private function registrarSaidaConsignadoSeNecessario(Produto $produto): void
    {
        if ($produto->estado !== 'consignado' || ! $produto->consignado_pago) {
            return;
        }

        $motivo = 'Pagamento consignado produto #'.$produto->id;

        $jaExisteSaida = SaidaCaixa::query()->where('motivo', $motivo)->exists();

        if ($jaExisteSaida) {
            return;
        }

        SaidaCaixa::create([
            'valor' => $produto->preco_compra,
            'motivo' => $motivo,
            'data_saidacaixa' => Carbon::now(),
        ]);
    }

    public function destroy(Produto $produto): RedirectResponse
    {
        $produto->delete();

        return back()->with('success', 'Produto removido com sucesso.');
    }
}
