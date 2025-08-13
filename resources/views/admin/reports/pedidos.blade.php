<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border:1px solid #ddd; padding:6px; }
        th { background:#f3f4f6; text-align:left; }
        h1 { font-size:18px; margin-bottom:10px; }
        .total { margin-top:10px; font-weight:bold; }
    </style>
</head>
<body>
    <h1>Relatório de Pedidos (Dashboard)</h1>
    <p>
        @if($dataInicio || $dataFim)
            Período:
            {{ $dataInicio ? \Carbon\Carbon::parse($dataInicio)->format('d/m/Y') : 'início' }}
            —
            {{ $dataFim ? \Carbon\Carbon::parse($dataFim)->format('d/m/Y') : 'hoje' }}
            <br>
        @endif
        @if($gestorId) Gestor: {{ $nomeGestor ?? $gestorId }}<br>@endif
        @if($distribuidorId) Distribuidor: {{ $nomeDistribuidor ?? $distribuidorId }}<br>@endif
    </p>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Data</th>
                <th>Gestor</th>
                <th>Distribuidor</th>
                <th>Cidades</th>
                <th>Status</th>
                <th>Valor Total (R$)</th>
            </tr>
        </thead>
        <tbody>
            @php $soma = 0; @endphp
            @foreach($pedidos as $p)
                @php
                    $soma += (float)$p->valor_total;
                @endphp
                <tr>
                    <td>{{ $p->id }}</td>
                    <td>{{ optional($p->data)->format('d/m/Y') }}</td>
                    <td>{{ optional($p->gestor?->user)->name ?? '-' }}</td>
                    <td>{{ optional($p->distribuidor?->user)->name ?? '-' }}</td>
                    <td>{{ $p->cidades?->pluck('name')->join(', ') ?: '-' }}</td>
                    <td>{{ $p->status }}</td>
                    <td>{{ number_format((float)$p->valor_total, 2, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p class="total">Total do relatório: R$ {{ number_format($soma, 2, ',', '.') }}</p>
</body>
</html>