<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Relatório de Vendas</title>
    <style>
        @page { margin: 28px; }
        body { font-family: "DejaVu Sans", sans-serif; font-size: 9px; color: #222; }
        h1 { font-size: 18px; margin-bottom: 8px; }
        .contexto { margin: 4px 0; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; margin-top: 16px; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
        th, td { border: 1px solid #ccc; padding: 6px 4px; text-align: left; vertical-align: top; word-wrap: break-word; }
        th { background: #eee; }
        .id { width: 6%; }
        .nome { width: 22%; }
        .comprador { width: 22%; }
        .valor { width: 14%; }
        .situacao { width: 16%; }
        .data { width: 20%; }
    </style>
</head>
<body>
    <h1>Relatório de Vendas</h1>
    <p class="contexto">Histórico de vendas: inclui vendas ativas e reembolsadas.</p>
    <p class="contexto">Período:
        {{ $inicio ? \Illuminate\Support\Carbon::parse($inicio)->format('d/m/Y') : 'Sem limite inicial' }}
        até {{ $fim ? \Illuminate\Support\Carbon::parse($fim)->format('d/m/Y') : 'Sem limite final' }}.
    </p>
    <p class="contexto">Datas no fuso {{ config('app.timezone') }}.</p>
    <table>
        <thead>
            <tr>
                <th class="id">ID</th>
                <th class="nome">Produto</th>
                <th class="comprador">Comprador</th>
                <th class="valor">Valor</th>
                <th class="situacao">Situação</th>
                <th class="data">Data</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($registros as $venda)
                <tr>
                    <td>#{{ $venda->id }}</td>
                    <td>{{ $venda->nome ?? ('ID ' . $venda->id_produto) }}</td>
                    <td>{{ $venda->comprador ?? 'Não informado' }}</td>
                    <td>R$ {{ number_format((float) ($venda->valor_venda_total ?? 0), 2, ',', '.') }}</td>
                    <td>{{ $venda->reembolsada ? 'Reembolsada' : 'Ativa' }}</td>
                    <td>{{ $venda->{$colunaDataVenda} ? \Illuminate\Support\Carbon::parse($venda->{$colunaDataVenda})->timezone(config('app.timezone'))->format('d/m/Y H:i') : 'Não informada' }}</td>
                </tr>
            @empty
                <tr><td colspan="6">Nenhuma venda encontrada no período informado.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
