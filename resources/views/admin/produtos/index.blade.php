@extends('layouts.app')
@section('title', 'Produtos')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4">Produtos</h1>
    <a href="{{ route('admin.produtos.create') }}" class="btn btn-primary">Novo Produto</a>
</div>
<div class="table-responsive">
<table class="table table-striped align-middle">
    <thead><tr><th scope="col">ID</th><th scope="col">Nome</th><th scope="col">Preço</th><th scope="col">Status</th><th scope="col">Consignado</th><th scope="col">Ações</th></tr></thead>
    <tbody>
    @forelse($produtos as $produto)
    <tr>
        <td>{{ $produto->id }}</td>
        <td>{{ $produto->nome }}</td>
        <td>R$ {{ number_format($produto->preco_venda, 2, ',', '.') }}</td>
        <td><x-status-produto :status="$produto->status" /></td>
        <td>
            @if ($produto->estado === 'consignado')
                <div><strong>Cliente:</strong> {{ $produto->cliente_consignado ?: '-' }}</div>
                <div><strong>Pago:</strong> {{ $produto->consignado_pago ? 'Sim' : 'Não' }}</div>
            @else
                -
            @endif
        </td>
        <td>
            <a href="{{ route('admin.produtos.edit', $produto) }}" class="btn btn-sm btn-secondary">Editar</a>
            <form method="POST" action="{{ route('admin.produtos.destroy', $produto) }}" class="d-inline js-confirm-delete" data-item-label="{{ $produto->nome }}">
                @csrf @method('DELETE')
                <button class="btn btn-sm btn-danger">Excluir</button>
            </form>
        </td>
    </tr>
    @empty
    <tr><td colspan="6" class="text-center text-muted">Nenhum produto cadastrado.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
@if ($produtos->hasPages())
    <nav aria-label="Paginação de produtos">
        {{ $produtos->links('pagination::bootstrap-5') }}
    </nav>
@endif
@endsection
