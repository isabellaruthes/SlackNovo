<nav class="navbar navbar-expand-lg app-navbar navbar-dark mb-4">
    <div class="container">
        <a class="navbar-brand" href="{{ route('dashboard') }}">Slacknovo</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavigation" aria-controls="adminNavigation" aria-expanded="false" aria-label="Abrir menu administrativo"><span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse" id="adminNavigation">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" @if(request()->routeIs('dashboard')) aria-current="page" @endif href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.produtos.*') ? 'active' : '' }}" @if(request()->routeIs('admin.produtos.*')) aria-current="page" @endif href="{{ route('admin.produtos.index') }}">Produtos</a></li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.categorias.*') ? 'active' : '' }}" @if(request()->routeIs('admin.categorias.*')) aria-current="page" @endif href="{{ route('admin.categorias.index') }}">Categorias</a></li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.cores.*') ? 'active' : '' }}" @if(request()->routeIs('admin.cores.*')) aria-current="page" @endif href="{{ route('admin.cores.index') }}">Cores</a></li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.materiais.*') ? 'active' : '' }}" @if(request()->routeIs('admin.materiais.*')) aria-current="page" @endif href="{{ route('admin.materiais.index') }}">Materiais</a></li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.fornecedores.*') ? 'active' : '' }}" @if(request()->routeIs('admin.fornecedores.*')) aria-current="page" @endif href="{{ route('admin.fornecedores.index') }}">Fornecedores</a></li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.caixa.*') ? 'active' : '' }}" @if(request()->routeIs('admin.caixa.*')) aria-current="page" @endif href="{{ route('admin.caixa.index') }}">Caixa</a></li>
            </ul>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-outline-danger btn-sm">Sair</button>
        </form>
        </div>
    </div>
</nav>
