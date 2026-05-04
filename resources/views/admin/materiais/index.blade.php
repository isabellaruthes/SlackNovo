@extends('layouts.app')
@section('title', 'Materiais')
@section('content')
<h1 class="h4 mb-3">Materiais</h1>
<form method="POST" action="{{ route('admin.materiais.store') }}" class="mb-3 d-flex gap-2">
    @csrf
    <input class="form-control" name="nome" placeholder="Novo material" required>
    <button class="btn btn-primary">Adicionar</button>
</form>
<table class="table table-striped">
    @foreach($materiais as $material)
    <tr>
        <td>{{ $material->nome }}</td>
        <td class="text-end">
            <form method="POST" action="{{ route('admin.materiais.update', $material) }}" class="d-inline-flex gap-2">
                @csrf @method('PUT')
                <input name="nome" value="{{ $material->nome }}" class="form-control form-control-sm">
                <button class="btn btn-sm btn-success">Salvar</button>
            </form>
            <form method="POST" action="{{ route('admin.materiais.destroy', $material) }}" class="d-inline js-confirm-delete" data-item-label="este registro">
                @csrf @method('DELETE')
                <button class="btn btn-sm btn-danger">Excluir</button>
            </form>
        </td>
    </tr>
    @endforeach
</table>
{{ $materiais->links() }}
@endsection