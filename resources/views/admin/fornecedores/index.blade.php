@extends('layouts.app')
@section('title', 'Fornecedores')
@section('content')
@include('admin.partials.cadastro-simples', [
    'titulo' => 'Fornecedores',
    'rotulo' => 'fornecedor',
    'novoRotulo' => 'Novo fornecedor',
    'vazio' => 'Nenhum fornecedor cadastrado.',
    'rota' => 'admin.fornecedores',
    'itens' => $fornecedores,
    'maximo' => 255,
])
@endsection
