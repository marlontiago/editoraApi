<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Relatório Financeiro</title>
    <style>
        @page { margin: 70px 30px 60px 30px; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color:#111; }
        header { position: fixed; top: -50px; left: 0; right: 0; height: 50px; text-align: center; }
        footer { position: fixed; bottom: -40px; left: 0; right: 0; height: 30px; font-size: 10px; color:#666; }
        .title { font-size: 18px; font-weight: 700; }
        .muted { color:#666; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #ddd; padding: 6px; }
        th { background: #f5f5f5; text-align: left; }
        .right { text-align: right; }
        .chip { display:inline-block; padding:2px 6px; border:1px solid #ccc; border-radius: 20px; font-size:10px; color:#333; }
        .mb-8{ margin-bottom: 16px; }
        .mb-4{ margin-bottom: 8px; }
        .mt-2{ margin-top: 6px; }
        .grid3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; }
        .card { border:1px solid #e5e5e5; padding:10px; border-radius:6px; }
        .h6 { font-size: 10px; color:#666; margin:0; }
        .h4 { font-size: 14px; font-weight: 700; margin:2px 0 0; }
    </style>
</head>
<body>
@php
    function moeda_br_pdf($v){ return 'R$ ' . number_format((float)$v, 2, ',', '.'); }
@endphp

<header>
    <div class="title">Relatório Financeiro</div>
    <div class="muted">
        @if($dataInicio && $dataFim)
            Período: {{ \Carbon\Carbon::parse($dataInicio)->format('d/m/Y') }}
            — {{ \Carbon\Carbon::parse($dataFim)->format('d/m/Y') }}
        @else
            Todos os períodos
        @endif
        @if($statusFiltro) &nbsp;|&nbsp; Status: {{ strtoupper($statusFiltro) }} @endif
        @if($filtroTipo && $filtroId) &nbsp;|&nbsp; Filtro: {{ ucfirst($filtroTipo) }} #{{ $filtroId }} @endif
    </div>
</header>

<footer>
    <div style="text-align:center;">Gerado em {{ now()->format('d/m/Y H:i') }}</div>
    <script type="text/php">
        if ( isset($pdf) ) {
            $font = $fontMetrics->get_font("DejaVu Sans", "normal");
            $pdf->page_text(520, 18, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 8, [0.4,0.4,0.4]);
        }
    </script>
</footer>

<main>
    @if($exportUsuario)
        <div class="grid3 mb-8">
            <div class="card">
                <p class="h6">Total líquido do período</p>
                <p class="h4">{{ moeda_br_pdf( (float) $pedidos->sum(fn($p) => (float) ($p->valor_liquido_pago_total ?? 0)) ) }}</p>
            </div>
            <div class="card">
                <p class="h6">Total de comissões</p>
                <p class="h4">{{ moeda_br_pdf( (float) $pedidos->sum(fn($p) => (float) ($p->comissao_do_filtro ?? 0)) ) }}</p>
            </div>
            <div class="card">
                <p class="h6">Pedidos no resultado</p>
                <p class="h4">{{ $pedidos->count() }}</p>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Pedido #</th>
                    <th>Cliente</th>
                    <th>Gestor</th>
                    <th>Distribuidor</th>
                    <th class="right">Valor Líquido Pago</th>
                    <th>Financeiro</th>
                    <th class="right">Comissão</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pedidos as $p)
                    <tr>
                        <td>#{{ $p->id }}</td>
                        <td>{{ $p->cliente->razao_social ?? '—' }}</td>
                        <td>{{ $p->gestor->razao_social ?? '—' }}</td>
                        <td>{{ $p->distribuidor->razao_social ?? '—' }}</td>
                        <td class="right">{{ moeda_br_pdf($p->valor_liquido_pago_total ?? 0) }}</td>
                        <td>{{ $p->notaFiscal->status_financeiro ?? '—' }}</td>
                        <td class="right">{{ moeda_br_pdf($p->comissao_do_filtro ?? 0) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="4" class="right">Totais:</th>
                    <th class="right">
                        {{ moeda_br_pdf( (float) $pedidos->sum(fn($p) => (float) ($p->valor_liquido_pago_total ?? 0)) ) }}
                    </th>
                    <th></th>
                    <th class="right">
                        {{ moeda_br_pdf( (float) $pedidos->sum(fn($p) => (float) ($p->comissao_do_filtro ?? 0)) ) }}
                    </th>
                </tr>
            </tfoot>
        </table>

    @elseif($exportStatus)
        <div class="grid3 mb-8">
            <div class="card">
                <p class="h6">Pedidos no resultado</p>
                <p class="h4">{{ $pedidoStatus->count() }}</p>
            </div>
            <div class="card">
                <p class="h6">Soma do valor da nota</p>
                <p class="h4">{{ moeda_br_pdf( (float) $pedidoStatus->sum(fn($p) => (float) optional($p->notaFiscal)->valor_total) ) }}</p>
            </div>
            <div class="card">
                <p class="h6">Status</p>
                <p class="h4">{{ strtoupper($statusFiltro ?? '—') }}</p>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Pedido #</th>
                    <th>Cliente</th>
                    <th>Gestor</th>
                    <th>Distribuidor</th>
                    <th class="right">Valor da Nota</th>
                    <th>Financeiro</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pedidoStatus as $p)
                    <tr>
                        <td>#{{ $p->id }}</td>
                        <td>{{ $p->cliente->razao_social ?? '—' }}</td>
                        <td>{{ $p->gestor->razao_social ?? '—' }}</td>
                        <td>{{ $p->distribuidor->razao_social ?? '—' }}</td>
                        <td class="right">{{ moeda_br_pdf(optional($p->notaFiscal)->valor_total ?? 0) }}</td>
                        <td>{{ $p->notaFiscal->status_financeiro ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="4" class="right">Total:</th>
                    <th class="right">
                        {{ moeda_br_pdf( (float) $pedidoStatus->sum(fn($p) => (float) optional($p->notaFiscal)->valor_total) ) }}
                    </th>
                    <th></th>
                </tr>
            </tfoot>
        </table>
    @endif
</main>
</body>
</html>
