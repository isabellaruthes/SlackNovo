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
            <div class="mb-2">
                <label class="form-label" for="saida_valor">Valor da saída (R$)</label>
                <input class="form-control @error('valor') is-invalid @enderror" id="saida_valor" name="valor" type="number" step="0.01" min="0.01" max="99999999.99" placeholder="Valor" value="{{ old('valor') }}" required @error('valor') aria-invalid="true" aria-describedby="erro_valor" @enderror>
                <x-erro-campo campo="valor" />
            </div>
            <div class="mb-2">
                <label class="form-label" for="saida_motivo">Motivo</label>
                <input class="form-control @error('motivo') is-invalid @enderror" id="saida_motivo" name="motivo" maxlength="50" placeholder="Motivo" value="{{ old('motivo') }}" required @error('motivo') aria-invalid="true" aria-describedby="erro_motivo" @enderror>
                <x-erro-campo campo="motivo" />
            </div>
            <button class="btn btn-warning">Salvar saída</button>
        </form>
    </div>
    <div class="col-md-6">
        <h2 class="h6">Registrar venda</h2>
        <form method="POST" action="{{ route('admin.caixa.venda') }}">@csrf
            <div class="mb-2">
                <label class="form-label" for="venda_nome">Nome da venda</label>
                <input class="form-control @error('nome') is-invalid @enderror" id="venda_nome" name="nome" maxlength="50" placeholder="Nome da venda" value="{{ old('nome') }}" required @error('nome') aria-invalid="true" aria-describedby="erro_nome" @enderror>
                <x-erro-campo campo="nome" />
            </div>
            <div class="mb-2">
                <label class="form-label" for="venda_produto">ID do produto</label>
                <input class="form-control @error('id_produto') is-invalid @enderror" id="venda_produto" name="id_produto" type="number" min="1" step="1" placeholder="ID produto" value="{{ old('id_produto') }}" required @error('id_produto') aria-invalid="true" aria-describedby="erro_id_produto" @enderror>
                <x-erro-campo campo="id_produto" />
            </div>
            <div class="mb-2">
                <label class="form-label" for="venda_valor">Valor da venda (R$)</label>
                <input class="form-control @error('valor_venda') is-invalid @enderror" id="venda_valor" name="valor_venda" type="number" step="0.01" min="0.01" max="99999999.99" value="{{ old('valor_venda') }}" required @error('valor_venda') aria-invalid="true" aria-describedby="erro_valor_venda" @enderror>
                <x-erro-campo campo="valor_venda" />
            </div>
            <div class="mb-2">
                <label class="form-label" for="venda_comprador">Comprador</label>
                <input class="form-control @error('comprador') is-invalid @enderror" id="venda_comprador" name="comprador" maxlength="50" placeholder="Comprador" value="{{ old('comprador') }}" required @error('comprador') aria-invalid="true" aria-describedby="erro_comprador" @enderror>
                <x-erro-campo campo="comprador" />
            </div>
            <button class="btn btn-success">Salvar venda</button>
        </form>
    </div>
</div>

<div class="mt-4">
    <h2 class="h6">Registros de vendas e saídas de caixa</h2>
    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>Tipo</th>
                    <th>ID</th>
                    <th>Data e hora</th>
                    <th>Valor</th>
                    <th>Lucro por peça</th>
                    <th>Quem comprou</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($registros as $registro)
                    <tr>
                        <td>{{ $registro['tipo'] === 'venda' ? 'Venda' : 'Saída' }}</td>
                        <td>{{ $registro['id'] }}</td>
                        <td>{{ \App\Support\DataHora::exibir($registro['data_hora']) ?? '-' }}</td>
                        <td>R$ {{ number_format($registro['valor'], 2, ',', '.') }}</td>
                        <td>
                            @if ($registro['lucro'] !== null)
                                R$ {{ number_format($registro['lucro'], 2, ',', '.') }}
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $registro['comprador'] }}</td>
                        <td>
                            @if ($registro['tipo'] === 'venda')
                                @if (!empty($registro['reembolsada']) && $registro['reembolsada'])
                                    <button class="btn btn-sm btn-secondary" type="button" disabled>Reembolsado</button>
                                @else
                                    <form method="POST" action="{{ route('admin.caixa.reembolso', $registro['venda']) }}">
                                        @csrf
                                        <button class="btn btn-sm btn-danger" type="submit">Reembolso</button>
                                    </form>
                                @endif
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center">Nenhum registro encontrado.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection