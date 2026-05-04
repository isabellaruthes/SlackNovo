<nav class="navbar navbar-expand-lg app-navbar navbar-dark mb-4">
    <div class="container">
        <a class="navbar-brand" href="{{ route('dashboard') }}">Slacknovo</a>
        <div class="navbar-collapse">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 d-flex flex-row flex-wrap gap-1 gap-lg-2">
                <li class="nav-item"><a class="nav-link" href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('admin.produtos.index') }}">Produtos</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('admin.categorias.index') }}">Categorias</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('admin.cores.index') }}">Cores</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('admin.materiais.index') }}">Materiais</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('admin.fornecedores.index') }}">Fornecedores</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('admin.caixa.index') }}">Caixa</a></li>
            </ul>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-outline-danger btn-sm">Sair</button>
        </form>
    </div>
</nav>
