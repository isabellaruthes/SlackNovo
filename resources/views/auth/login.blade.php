@extends('layouts.app')

@section('title', 'Login')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-sm">
                <div class="card-header text-center">
                    <h2 class="h4 mb-0">Login de Usuário</h2>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('login.attempt') }}">
                        @csrf

                        <div class="input-group mb-3">
                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                            <input type="text" class="form-control" name="nome" placeholder="Nome de usuário"
                                value="{{ old('nome') }}" required>
                        </div>

                        <div class="input-group mb-3">
                            <span class="input-group-text"><i class="bi bi-key"></i></span>
                            <input type="password" class="form-control" name="password" placeholder="Senha" required>
                        </div>

                        @error('nome')
                            <div class="alert alert-danger">{{ $message }}</div>
                        @enderror

                        <button type="submit" class="btn btn-primary w-100">Entrar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection