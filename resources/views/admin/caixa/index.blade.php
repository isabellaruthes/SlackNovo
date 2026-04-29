@extends('layouts.app')
@section('title', 'Caixa')
@section('content')
<h1 class="h4 mb-3">Caixa</h1>
<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="card"><div class="card-body">Saldo: <strong>R$ {{ number_format($saldo,2,',','.') }}</strong></div></div></div>
    <div class="col-md-4"><div class="card"><div class="card-body">Entradas: <strong>R$ {{ number_format($entradas,2,',','.') }}</strong></div></div></div>
    <div class="col-md-4"><div class="card"><div class="card-body">Saídas: <strong>R$ {{ number_format($saidas,2,',','.') }}</strong></div></div></div>
</div>

<div class="row g-4">
    <div class="col-md-6">
        <h2 class="h6">Registrar saída</h2>
        <form method="POST" action="{{ route('admin.caixa.saida') }}">@csrf
            <input class="form-control mb-2" name="valor" type="number" step="0.01" placeholder="Valor" required>
            <input class="form-control mb-2" name="motivo" placeholder="Motivo" required>
            <button class="btn btn-warning">Salvar saída</button>
        </form>
    </div>
    <div class="col-md-6">
        <h2 class="h6">Registrar venda</h2>
        <form method="POST" action="{{ route('admin.caixa.venda') }}">@csrf
            <input class="form-control mb-2" name="nome" placeholder="Nome da venda" required>
            <input class="form-control mb-2" name="id_produto" type="number" placeholder="ID produto" required>
            <input class="form-control mb-2" name="comprador" placeholder="Comprador" required>
            <button class="btn btn-success">Salvar venda</button>
        </form>
    </div>
</div>
@endsection