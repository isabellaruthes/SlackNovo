@extends('layouts.app')
@section('title', 'Categorias')
@section('content')
@include('admin.partials.cadastro-simples', [
    'titulo' => 'Categorias',
    'rotulo' => 'categoria',
    'novoRotulo' => 'Nova categoria',
    'vazio' => 'Nenhuma categoria cadastrada.',
    'rota' => 'admin.categorias',
    'itens' => $categorias,
    'maximo' => 50,
])
@endsection
