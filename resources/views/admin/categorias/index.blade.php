@extends('layouts.app')
@section('title', 'Categorias')
@section('content')
<h1 class="h4 mb-3">Categorias</h1>
<form method="POST" action="{{ route('admin.categorias.store') }}" class="mb-3 d-flex gap-2">
    @csrf
    <input class="form-control" name="nome" placeholder="Nova categoria" required>
    <button class="btn btn-primary">Adicionar</button>
</form>
<table class="table table-striped">
    @foreach($categorias as $categoria)
    <tr>
        <td>{{ $categoria->nome }}</td>
        <td class="text-end">
            <form method="POST" action="{{ route('admin.categorias.update', $categoria) }}" class="d-inline-flex gap-2">
                @csrf @method('PUT')
                <input name="nome" value="{{ $categoria->nome }}" class="form-control form-control-sm">
                <button class="btn btn-sm btn-success">Salvar</button>
            </form>
            <form method="POST" action="{{ route('admin.categorias.destroy', $categoria) }}" class="d-inline js-confirm-delete" data-item-label="este registro">
                @csrf @method('DELETE')
                <button class="btn btn-sm btn-danger">Excluir</button>
            </form>
        </td>
    </tr>
    @endforeach
</table>
{{ $categorias->links() }}
@endsection