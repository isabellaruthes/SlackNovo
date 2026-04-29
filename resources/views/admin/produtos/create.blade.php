@extends('layouts.app')
@section('title', 'Novo Produto')
@section('content')
<h1 class="h4 mb-3">Novo Produto</h1>
<form method="POST" action="{{ route('admin.produtos.store') }}">
    @csrf
    @include('admin.produtos._form')
    <button class="btn btn-primary mt-3">Salvar</button>
</form>
@endsection