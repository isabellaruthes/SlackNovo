@extends('layouts.app')
@section('title', 'Materiais')
@section('content')
@include('admin.partials.cadastro-simples', [
    'titulo' => 'Materiais',
    'rotulo' => 'material',
    'novoRotulo' => 'Novo material',
    'vazio' => 'Nenhum material cadastrado.',
    'rota' => 'admin.materiais',
    'itens' => $materiais,
    'maximo' => 50,
])
@endsection
