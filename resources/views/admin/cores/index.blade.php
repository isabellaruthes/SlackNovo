@extends('layouts.app')
@section('title', 'Cores')
@section('content')
<h1 class="h4 mb-3">Cores</h1>
<form method="POST" action="{{ route('admin.cores.store') }}" class="mb-3 d-flex gap-2">
    @csrf
    <input class="form-control" name="nome" placeholder="Nova cor" required>
    <button class="btn btn-primary">Adicionar</button>
</form>
<table class="table table-striped">
    @foreach($cores as $cor)
    <tr>
        <td>{{ $cor->nome }}</td>
        <td class="text-end">
            <form method="POST" action="{{ route('admin.cores.update', $cor) }}" class="d-inline-flex gap-2">
                @csrf @method('PUT')
                <input name="nome" value="{{ $cor->nome }}" class="form-control form-control-sm">
                <button class="btn btn-sm btn-success">Salvar</button>
            </form>
            <form method="POST" action="{{ route('admin.cores.destroy', $cor) }}" class="d-inline js-confirm-delete" data-item-label="este registro">
                @csrf @method('DELETE')
                <button class="btn btn-sm btn-danger">Excluir</button>
            </form>
        </td>
    </tr>
    @endforeach
</table>
{{ $cores->links() }}
@endsection