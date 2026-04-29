@extends('layouts.app')
@section('title', 'Editar Produto')
@section('content')
<h1 class="h4 mb-3">Editar Produto #{{ $produto->id }}</h1>
<form method="POST" action="{{ route('admin.produtos.update', $produto) }}">
    @csrf @method('PUT')
    @include('admin.produtos._form')
    <button class="btn btn-success mt-3">Atualizar</button>
</form>
@endsection