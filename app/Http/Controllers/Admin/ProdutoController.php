<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProdutoRequest;
use App\Models\Categoria;
use App\Models\Cor;
use App\Models\Fornecedor;
use App\Models\Material;
use App\Models\Produto;
use App\Models\SaidaCaixa;
use App\Models\Venda;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

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

    public function store(ProdutoRequest $request): RedirectResponse
    {
        $this->salvarProduto($request);

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

    public function update(ProdutoRequest $request, Produto $produto): RedirectResponse
    {
        $this->salvarProduto($request, $produto);

        return redirect()->route('admin.produtos.index')->with('success', 'Produto atualizado com sucesso.');
    }

    private function salvarProduto(ProdutoRequest $request, ?Produto $produto = null): void
    {
        $data = $request->safe()->except(['imagen']);
        $suportaConsignado = $this->suportaCamposConsignado();

        if ($suportaConsignado) {
            $consignado = ($data['estado'] ?? null) === 'consignado';
            $data['cliente_consignado'] = $consignado ? ($data['cliente_consignado'] ?? null) : null;
            $data['consignado_pago'] = $consignado && (bool) ($data['consignado_pago'] ?? false);
        } else {
            unset($data['cliente_consignado'], $data['consignado_pago']);
        }

        $novaImagem = null;
        $imagemAnterior = null;

        try {
            DB::transaction(function () use ($request, $data, $produto, $suportaConsignado, &$novaImagem, &$imagemAnterior): void {
                if ($produto !== null) {
                    $produto = Produto::query()->lockForUpdate()->findOrFail($produto->id);

                    if (array_key_exists('status', $data) && $data['status'] !== 'vendido'
                        && Venda::where('id_produto', $produto->id)->where('reembolsada', false)->exists()) {
                        throw ValidationException::withMessages([
                            'status' => 'Reembolse a venda antes de disponibilizar o produto novamente.',
                        ]);
                    }
                }

                if ($request->hasFile('imagen')) {
                    $path = $request->file('imagen')->store('produtos', 'public');
                    if ($path === false) {
                        throw new RuntimeException('Não foi possível salvar a imagem do produto.');
                    }

                    $novaImagem = $path;
                    $imagemAnterior = $produto?->imagen;
                    $data['imagen'] = $path;
                }

                $produto ??= new Produto;
                $produto->fill($data);
                if (! $produto->save()) {
                    throw new RuntimeException('Não foi possível salvar o produto.');
                }

                if ($suportaConsignado) {
                    $this->registrarSaidaConsignadoSeNecessario($produto);
                }
            });
        } catch (Throwable $exception) {
            $this->removerImagem($novaImagem);

            throw $exception;
        }

        if ($novaImagem !== null && DB::transactionLevel() > 0) {
            // A surrounding transaction may still roll back after this save succeeded.
            DB::afterRollBack(fn () => $this->removerImagem($novaImagem));
        }

        if ($novaImagem !== null && $imagemAnterior !== null && $imagemAnterior !== $novaImagem) {
            DB::afterCommit(fn () => $this->removerImagem($imagemAnterior));
        }
    }

    private function removerImagem(?string $path): void
    {
        // Only files directly owned by the product upload directory may be removed.
        if ($path === null || ! preg_match('~\Aprodutos/[A-Za-z0-9][A-Za-z0-9._-]*\z~', $path)) {
            return;
        }

        try {
            if (! Storage::disk('public')->delete($path)) {
                report(new RuntimeException('Não foi possível remover uma imagem do produto.'));
            }
        } catch (Throwable $exception) {
            report($exception);
        }
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
        $imagem = DB::transaction(function () use ($produto): ?string {
            $produto = Produto::query()->lockForUpdate()->findOrFail($produto->id);
            $imagem = $produto->imagen;

            if (! $produto->delete()) {
                throw new RuntimeException('Não foi possível remover o produto.');
            }

            return $imagem;
        });

        DB::afterCommit(fn () => $this->removerImagem($imagem));

        return back()->with('success', 'Produto removido com sucesso.');
    }
}
