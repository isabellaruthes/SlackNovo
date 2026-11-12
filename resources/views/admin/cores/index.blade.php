@extends('layouts.app')
@section('title', 'Cores')
@section('content')
@include('admin.partials.cadastro-simples', [
    'titulo' => 'Cores',
    'rotulo' => 'cor',
    'novoRotulo' => 'Nova cor',
    'vazio' => 'Nenhuma cor cadastrada.',
    'rota' => 'admin.cores',
    'itens' => $cores,
    'maximo' => 50,
])
@endsection
