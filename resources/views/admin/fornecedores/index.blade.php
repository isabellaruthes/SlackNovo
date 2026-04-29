@extends('layouts.app')
@section('title', 'Fornecedores')
@section('content')
<h1 class="h4 mb-3">Fornecedores</h1>
<form method="POST" action="{{ route('admin.fornecedores.store') }}" class="mb-3 d-flex gap-2">
    @csrf
    <input class="form-control" name="nome" placeholder="Novo fornecedor" required>
    <button class="btn btn-primary">Adicionar</button>
</form>
<table class="table table-striped">
    @foreach($fornecedores as $fornecedor)
    <tr>
        <td>{{ $fornecedor->nome }}</td>
        <td class="text-end">
            <form method="POST" action="{{ route('admin.fornecedores.update', $fornecedor) }}" class="d-inline-flex gap-2">
                @csrf @method('PUT')
                <input name="nome" value="{{ $fornecedor->nome }}" class="form-control form-control-sm">
                <button class="btn btn-sm btn-success">Salvar</button>
            </form>
            <form method="POST" action="{{ route('admin.fornecedores.destroy', $fornecedor) }}" class="d-inline">
                @csrf @method('DELETE')
                <button class="btn btn-sm btn-danger">Excluir</button>
            </form>
        </td>
    </tr>
    @endforeach
</table>
{{ $fornecedores->links() }}
@endsection