@extends('layouts.app')

@section('title', 'Catálogo Público')

@section('main_class', 'p-0')

@section('content')
    <nav class="navbar navbar-expand-lg navbar-dark mb-4"
        style="background: #0f172a;">
        <div class="container py-1">
            <a class="navbar-brand" href="{{ route('catalog.public') }}">SlackNovo</a>
            <a class="btn btn-outline-light btn-sm" href="{{ route('login') }}">
                <i class="bi bi-person-lock me-1"></i>Login do Administrador
            </a>
        </div>
    </nav>

    <div class="container py-4">
        <section class="bg-light border rounded-3 p-3 p-md-4 mb-4">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-12 col-md-4">
                <label class="form-label" for="catalogo_q">Pesquisar produto</label>
                <input type="text" name="q" id="catalogo_q" class="form-control" value="{{ $busca }}"
                    placeholder="Nome, código ou descrição...">
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label" for="catalogo_categoria">Categoria</label>
                <select class="form-select" name="categoria" id="catalogo_categoria">
                    <option value="">Todas</option>
                    @foreach ($categorias as $item)
                        <option value="{{ $item->id }}" @selected((string) $categoria === (string) $item->id)>{{ $item->nome }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label" for="catalogo_tamanho">Tamanho</label>
                <select class="form-select" name="tamanho" id="catalogo_tamanho">
                    <option value="">Todos</option>
                    @foreach ($tamanhos as $item)
                        <option value="{{ $item }}" @selected($tamanho === $item)>{{ $item }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label" for="catalogo_ordenar">Ordenar</label>
                <select class="form-select" name="ordenar" id="catalogo_ordenar">
                    <option value="default" @selected($ordenar === 'default')>Mais recentes</option>
                    <option value="price_asc" @selected($ordenar === 'price_asc')>Menor preço</option>
                    <option value="price_desc" @selected($ordenar === 'price_desc')>Maior preço</option>
                    <option value="name_asc" @selected($ordenar === 'name_asc')>Nome A-Z</option>
                    <option value="name_desc" @selected($ordenar === 'name_desc')>Nome Z-A</option>
                </select>
            </div>
            <div class="col-12 col-md-2 d-grid">
                <button class="btn btn-dark" type="submit">Filtrar</button>
            </div>
        </form>
        </section>

        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4 mb-5">
        @forelse ($produtos as $produto)
            <div class="col">
                <div class="card shadow-sm h-100">
                    @if ($produto->imagen)
                        <img src="{{ asset('storage/' . $produto->imagen) }}" class="card-img-top"
                            alt="{{ $produto->nome }}" style="height:220px;object-fit:cover;" loading="lazy">
                    @else
                        <div class="product-image-placeholder" role="img" aria-label="Produto sem imagem">Sem imagem</div>
                    @endif
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title mb-2">{{ $produto->nome }}</h5>
                        <p class="mb-1 text-muted">Código: #{{ $produto->id }}</p>
                        <p class="mb-2 text-muted">Tamanho: {{ $produto->tamanho ?? 'Não informado' }}</p>
                        <p class="fs-5 fw-bold text-success mb-3">R$ {{ number_format($produto->preco_venda, 2, ',', '.') }}</p>
                        <button class="btn btn-outline-primary mt-auto" data-bs-toggle="modal"
                            data-bs-target="#produtoModal{{ $produto->id }}">Ver detalhes</button>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="produtoModal{{ $produto->id }}" tabindex="-1" aria-hidden="true" aria-labelledby="produtoTitulo{{ $produto->id }}">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="produtoTitulo{{ $produto->id }}">{{ $produto->nome }} (Código #{{ $produto->id }})</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                        </div>
                        <div class="modal-body">
                            @if ($produto->imagen)
                                <img src="{{ asset('storage/' . $produto->imagen) }}" alt="{{ $produto->nome }}"
                                    class="img-fluid rounded mb-3" loading="lazy">
                            @else
                                <p class="text-muted">Imagem não disponível.</p>
                            @endif
                            <p class="mb-1"><strong>Preço:</strong> R$ {{ number_format($produto->preco_venda, 2, ',', '.') }}</p>
                            <p class="mb-1"><strong>Cor:</strong> {{ $produto->cor?->nome ?? 'Não informado' }}</p>
                            <p class="mb-1"><strong>Material:</strong> {{ $produto->material?->nome ?? 'Não informado' }}</p>
                            <p class="mb-1"><strong>Tamanho:</strong> {{ $produto->tamanho ?? 'Não informado' }}</p>
                            <p class="mb-0"><strong>Descrição:</strong> {{ $produto->descricao ?: 'Sem descrição.' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <p class="text-muted">Nenhum produto disponível no momento.</p>
        @endforelse
        </div>
        {{ $produtos->links('pagination::bootstrap-5') }}
    </div>

    <a class="whatsapp-float" target="_blank" href="https://wa.me/5511999999999" aria-label="Falar no WhatsApp">
        <i class="bi bi-whatsapp"></i>
    </a>
@endsection

@section('footer')
    <footer class="catalog-footer text-white py-4">
        <div class="container">
        <div class="row g-4">
            <div class="col-md-4">
                <h6 class="fw-bold">SlackNovo</h6>
                <p class="mb-0 small">Moda sustentável e produtos selecionados para você.</p>
            </div>
            <div class="col-md-4">
                <h6 class="fw-bold">Localização e Contato</h6>
                <p class="mb-0 small">Rua Exemplo, 100 - Centro<br>São Paulo - SP<br>(11) 99999-9999</p>
            </div>
            <div class="col-md-4">
                <h6 class="fw-bold">Redes Sociais</h6>
                <div class="d-flex gap-3">
                    <a href="#" class="text-white" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                    <a href="#" class="text-white" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                    <a href="#" class="text-white" aria-label="TikTok"><i class="bi bi-tiktok"></i></a>
                </div>
            </div>
        </div>
        </div>
    </footer>
@endsection

