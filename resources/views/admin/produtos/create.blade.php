@extends('layouts.app')
@section('title', 'Novo Produto')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h4 mb-0">Novo Produto</h1>
    <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">Voltar ao painel administrativo</a>
</div>
<form method="POST" action="{{ route('admin.produtos.store') }}" enctype="multipart/form-data">
    @csrf
    @include('admin.produtos._form')
    <button class="btn btn-primary mt-3">Salvar</button>
</form>
@endsection
