@extends('layouts.app')

@section('title', 'Dashboard Administrativa')

@section('content')
    <section class="app-hero">
        <h1 class="h4 mb-2">Painel Administrativo</h1>
        <p class="text-muted mb-4">Resumo da operação da loja e catálogo administrativo.</p>

        <div class="row g-3 mb-4">
            <div class="col-lg-8">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h2 class="h6 mb-3">Produtos vendidos por mês (últimos 6 meses)</h2>
                        <div class="d-flex align-items-end gap-2" style="height: 220px;">
                            @php($maiorTotal = max(1, $totaisMeses->max()))
                            @foreach ($totaisMeses as $index => $total)
                                @php($altura = $total > 0 ? max(12, ($total / $maiorTotal) * 180) : 12)
                                <div class="text-center flex-fill">
                                    <div class="bg-dark rounded-top" style="height: {{ $altura }}px;"></div>
                                    <small class="d-block mt-2">{{ $labelsMeses[$index] }}</small>
                                    <strong>{{ $total }}</strong>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h2 class="h6 mb-3">Visão financeira</h2>
                        <p class="mb-2"><strong>Entradas:</strong> R$ {{ number_format($entradas, 2, ',', '.') }}</p>
                        <p class="mb-2"><strong>Saídas:</strong> R$ {{ number_format($saidas, 2, ',', '.') }}</p>
                        <p class="mb-4"><strong>Saldo:</strong> R$ {{ number_format($saldo, 2, ',', '.') }}</p>
                        <a href="{{ route('admin.caixa.index') }}" class="btn btn-outline-dark w-100 mb-2">Ir para Caixa</a>
                        <button type="button" class="btn btn-dark w-100" data-bs-toggle="modal" data-bs-target="#modalExportacao">Exportar Relatório</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h5 mb-0">Catálogo administrativo</h2>
                    <a href="{{ route('admin.produtos.index') }}" class="btn btn-sm btn-outline-secondary">Gerenciar produtos</a>
                </div>
                <div class="row g-3">
                    @forelse ($produtos as $produto)
                        <div class="col-md-6 col-xl-4">
                            <div class="border rounded p-3 h-100 bg-light">
                                <h3 class="h6 mb-1">{{ $produto->nome }}</h3>
                                <p class="text-muted mb-2">{{ $produto->categoria?->nome ?? 'Sem categoria' }} • {{ $produto->tamanho }}</p>
                                <p class="mb-1"><strong>Status:</strong> {{ ucfirst($produto->status) }}</p>
                                <p class="mb-1"><strong>Estoque:</strong> {{ $produto->status === 'vendido' ? 'Vendido' : 'Disponível' }}</p>
                                <p class="mb-1"><strong>Preço venda:</strong> R$ {{ number_format((float) $produto->preco_venda, 2, ',', '.') }}</p>
                                @if ($produto->status === 'vendido')
                                    <p class="mb-1"><strong>Comprador:</strong> {{ $produto->comprador ?? 'Não identificado' }}</p>
                                    <p class="mb-0"><strong>Data da venda:</strong> {{ $produto->data_venda ? \Illuminate\Support\Carbon::parse($produto->data_venda)->format('d/m/Y H:i') : '-' }}</p>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-muted">Nenhum produto cadastrado.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="modalExportacao" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="GET" action="{{ route('admin.relatorios.exportar') }}">
                    <div class="modal-header">
                        <h2 class="modal-title fs-5">Exportar relatório</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <label class="form-label">Tipo de exportação</label>
                        <select class="form-select mb-3" name="tipo" required>
                            <option value="vendas">Tudo que foi vendido</option>
                            <option value="entradas">Tudo que entrou no caixa</option>
                            <option value="saidas">Tudo que saiu do caixa</option>
                        </select>

                        <div class="row">
                            <div class="col">
                                <label class="form-label">Data inicial</label>
                                <input type="date" name="inicio" class="form-control">
                            </div>
                            <div class="col">
                                <label class="form-label">Data final</label>
                                <input type="date" name="fim" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-dark">Exportar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
