@extends('layouts.app')
@section('title', 'Produtos')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4">Produtos</h1>
    <a href="{{ route('admin.produtos.create') }}" class="btn btn-primary">Novo Produto</a>
</div>
<table class="table table-striped">
    <thead><tr><th>ID</th><th>Nome</th><th>Preço</th><th>Status</th><th>Ações</th></tr></thead>
    <tbody>
    @foreach($produtos as $produto)
    <tr>
        <td>{{ $produto->id }}</td>
        <td>{{ $produto->nome }}</td>
        <td>R$ {{ number_format($produto->preco_venda, 2, ',', '.') }}</td>
        <td>{{ $produto->status }}</td>
        <td>
            <a href="{{ route('admin.produtos.edit', $produto) }}" class="btn btn-sm btn-secondary">Editar</a>
            <form method="POST" action="{{ route('admin.produtos.destroy', $produto) }}" class="d-inline">
                @csrf @method('DELETE')
                <button class="btn btn-sm btn-danger">Excluir</button>
            </form>
        </td>
    </tr>
    @endforeach
    </tbody>
</table>
{{ $produtos->links() }}
@endsection