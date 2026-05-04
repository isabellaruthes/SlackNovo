@extends('layouts.app')

@section('title', 'Dashboard Administrativa')

@section('content')
    <section class="app-hero">
        <h1 class="h4 mb-2">Painel Administrativo</h1>
        <p class="text-muted mb-4">Escolha uma área no menu superior para gerenciar o sistema.</p>

        <div class="row g-3">
            <div class="col-md-4">
                <a href="{{ route('admin.produtos.index') }}" class="btn btn-dark w-100">Gerenciar Produtos</a>
            </div>
            <div class="col-md-4">
                <a href="{{ route('admin.caixa.index') }}" class="btn btn-outline-dark w-100">Caixa</a>
            </div>
            <div class="col-md-4">
                <a href="{{ route('admin.relatorios.produtos') }}" class="btn btn-outline-secondary w-100">Relatório de Produtos</a>
            </div>
        </div>
    </section>
@endsection
