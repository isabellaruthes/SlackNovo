@extends('layouts.app')

@section('title', 'Catálogo de Produtos')

@section('content')
    <header class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <form method="GET" class="d-flex gap-2">
            <input type="text" name="q" class="form-control" value="{{ $busca }}" placeholder="Pesquisar produto...">
            <select class="form-select" name="ordenar" onchange="this.form.submit()">
                <option value="default" @selected($ordenar === 'default')>Mais recentes</option>
                <option value="price_asc" @selected($ordenar === 'price_asc')>Preço menor</option>
                <option value="price_desc" @selected($ordenar === 'price_desc')>Preço maior</option>
                <option value="name_asc" @selected($ordenar === 'name_asc')>Nome A-Z</option>
                <option value="name_desc" @selected($ordenar === 'name_desc')>Nome Z-A</option>
            </select>
            <button class="btn btn-outline-success" type="submit">Buscar</button>
        </form>
    </header>

    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
        @forelse ($produtos as $produto)
            <div class="col">
                <div class="card shadow-sm h-100">
                    <img src="{{ asset('storage/' . $produto->imagen) }}" class="card-img-top"
                        alt="{{ $produto->nome }}" style="height:220px;object-fit:cover;">
                    <div class="card-body">
                        <h5 class="card-title">{{ $produto->nome }}</h5>
                        <p class="text-muted mb-1">R$ {{ number_format($produto->preco_venda, 2, ',', '.') }}</p>
                        <small class="d-block">{{ $produto->categoria?->nome }}</small>
                        <small class="d-block">{{ $produto->material?->nome }}</small>
                        <small class="d-block">{{ $produto->cor?->nome }}</small>
                    </div>
                </div>
            </div>
        @empty
            <p class="text-muted">Nenhum produto cadastrado.</p>
        @endforelse
    </div>
@endsection