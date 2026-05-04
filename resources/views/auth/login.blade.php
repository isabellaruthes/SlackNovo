@extends('layouts.app')

@section('title', 'Login')

@section('content')
    <div class="row justify-content-center align-items-center min-vh-75"> 
        <div class="col-md-7 col-lg-6"> 
            <div class="card border-0 shadow-lg"> 
                <div class="card-header bg-primary text-white text-center py-4"> 
                    <h2 class="h4 mb-1">Bem-vindo ao SlackNovo</h2>
                    <p class="mb-0 small opacity-75">Acesse sua conta para gerenciar o sistema</p>
                </div>

                <div class="card-body p-4 p-md-5"> 
                    <form method="POST" action="{{ route('login.attempt') }}"> 
                        @csrf

                        <div class="mb-3"> 
                            <label for="nome" class="form-label fw-semibold">Usuário</label>
                            <div class="input-group"> 
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input id="nome" type="text" class="form-control" name="nome"
                                    placeholder="Digite seu nome de usuário" value="{{ old('nome') }}" required autofocus>
                            </div>
                        </div>

                        <div class="mb-3"> 
                            <label for="password" class="form-label fw-semibold">Senha</label>
                            <div class="input-group"> 
                                <span class="input-group-text"><i class="bi bi-key"></i></span>
                                <input id="password" type="password" class="form-control" name="password"
                                    placeholder="Digite sua senha" required>
                            </div>
                        </div>

                        @error('nome')
                            <div class="alert alert-danger py-2">{{ $message }}</div>
                        @enderror

                        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">Entrar no sistema</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
