<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Relatório de Pedidos (Dashboard)</title>
    <style>
        @page { margin: 120px 36px 90px 36px; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11.5px; line-height: 1.45; color:#0f172a; }

        .header { position: fixed; top: -100px; left: 0; right: 0; height: 100px; }
        .footer { position: fixed; bottom: -70px; left: 0; right: 0; height: 60px; color:#475569; font-size:10.5px; }
        .footer .pagenum:after { content: counter(page) " / " counter(pages); }

        .h-top { background:#0b2545; color:#e2e8f0; padding:14px 18px; border-radius:10px; }
        .brand { width:100%; height:100%; }
        .brand td { border:0; padding:0; }
        .title { font-size:20px; font-weight:700; color:#fff; white-space:nowrap; line-height:1; }
        .muted { color:#cbd5e1; font-size:11px; }

        .f-line { border-top:1px solid #e2e8f0; margin:8px 0; }
        .f-wrap td { border:0; padding:2px 0; }

        .section-title { font-size:13px; font-weight:700; margin:14px 0 8px; color:#0b2545; }
        .stat { border:1px solid #e2e8f0; border-radius:10px; padding:10px 12px; background:#f8fafc; }
        .stat .label { color:#475569; font-size:11px; }
        .stat .value { font-size:14px; font-weight:700; color:#0f172a; }
        .emph { color:#0b2545; font-weight:700; }
        .cards-grid { width:100%; border-collapse:separate; border-spacing:0 10px; }
        .cards-grid td { padding:0; }
        .pad-r { padding-right:6px; }
        .pad-x { padding:0 3px; }
        .pad-l { padding-left:6px; }

        table { width:100%; border-collapse:collapse; }
        .tbl { border:1px solid #e2e8f0; border-radius:10px; overflow:hidden; }
        .tbl thead th { background:#f1f5f9; color:#0f172a; font-weight:700; font-size:11.5px; padding:8px; border-bottom:1px solid #e2e8f0; text-align:left; }
        .tbl th.right, .tbl td.right { text-align:right; }
        .tbl tbody td { padding:8px; border-bottom:1px solid #f1f5f9; vertical-align:top; }
        .tbl tbody tr:nth-child(2n) td { background:#fafafa; }
        .tbl tfoot td { padding:8px; border-top:1px solid #e2e8f0; background:#f8fafc; font-weight:700; }

        thead { display: table-header-group; }
        tfoot { display: table-row-group; }
        tr { page-break-inside: avoid; }
    </style>
</head>
<body>
@php
    $fmt = fn($v) => number_format((float)$v, 2, ',', '.');

    $periodo = null;
    if ($dataInicio || $dataFim) {
        $ini = $dataInicio ? \Carbon\Carbon::parse($dataInicio)->format('d/m/Y') : 'início';
        $fim = $dataFim ? \Carbon\Carbon::parse($dataFim)->format('d/m/Y') : 'hoje';
        $periodo = "$ini — $fim";
    }
    $somaTotal = (float) ($pedidos?->sum(fn($p) => (float) ($p->valor_total ?? 0)) ?? 0);
@endphp

<div class="header">
    <div class="h-top">
        <table class="brand">
            <tr>
                <td style="width:60%; vertical-align:middle;">
                    <div style="display:flex; align-items:center; gap:14px; height:100%;">
                        <span class="title">Relatório de Pedidos (Dashboard)</span>
                    </div>
                </td>
                <td style="width:40%; text-align:right; vertical-align:middle;">
                    <div style="font-size:11px; line-height:1.5;">
                        @if($periodo)<div><strong>Período:</strong> {{ $periodo }}</div>@endif
                        @if($gestorId)<div><strong>Gestor:</strong> {{ $nomeGestor ?? $gestorId }}</div>@endif
                        @if($distribuidorId)<div><strong>Distribuidor:</strong> {{ $nomeDistribuidor ?? $distribuidorId }}</div>@endif
                        @if($status)<div><strong>Status:</strong> {{ strtoupper($status) }}</div>@endif
                        <div class="muted" style="margin-top:4px;">Gerado em {{ now()->format('d/m/Y H:i') }}</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>
</div>

<div class="footer">
    <div class="f-line"></div>
    <table class="f-wrap" style="width:100%;">
        <tr>
            <td style="text-align:left;">
                {{ config('empresa.razao_social', env('EMPRESA_RAZAO', 'Sua Empresa LTDA')) }}
                • CNPJ {{ config('empresa.cnpj', env('EMPRESA_CNPJ', '00.000.000/0000-00')) }}
                • {{ config('empresa.telefone', env('EMPRESA_FONE', '(00) 0000-0000')) }}
                • {{ config('empresa.email', env('EMPRESA_EMAIL', 'contato@empresa.com')) }}
            </td>
            <td style="text-align:right;">Página <span class="pagenum"></span></td>
        </tr>
    </table>
</div>

<br><br>

<main>
    <div class="section-title">Resumo</div>
    <table class="cards-grid">
        <tr>
            <td class="pad-r" style="width:33.3%;">
                <div class="stat">
                    <div class="label">Pedidos no período</div>
                    <div class="value">{{ $pedidos?->count() ?? 0 }}</div>
                </div>
            </td>
            <td class="pad-x" style="width:33.3%;">
                <div class="stat">
                    <div class="label">Valor total</div>
                    <div class="value emph">R$ {{ $fmt($somaTotal) }}</div>
                </div>
            </td>
            <td class="pad-l" style="width:33.3%;">
                <div class="stat">
                    <div class="label">Gestor / Distribuidor</div>
                    <div class="value">
                        {{ $nomeGestor ?? ($gestorId ?: '—') }} /
                        {{ $nomeDistribuidor ?? ($distribuidorId ?: '—') }}
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <div class="section-title">Pedidos</div>
    <table class="tbl">
        <thead>
            <tr>
                <th style="width:70px;">#</th>
                <th style="width:90px;">Data</th>
                <th>Gestor</th>
                <th>Distribuidor</th>
                <th>Cidades</th>
                <th style="width:110px;">Status</th>
                <th class="right" style="width:130px;">Valor Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($pedidos as $p)
                <tr>
                    <td>#{{ $p->id }}</td>
                    <td>{{ optional($p->data)->format('d/m/Y') }}</td>
                    <td>{{ optional($p->gestor?->user)->name ?? '-' }}</td>
                    <td>{{ optional($p->distribuidor?->user)->name ?? '-' }}</td>
                    <td>{{ $p->cidades?->pluck('name')->join(', ') ?: '-' }}</td>
                    <td>{{ $p->status }}</td>
                    <td class="right">R$ {{ $fmt($p->valor_total ?? 0) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="6" class="right">Total do relatório</td>
                <td class="right">R$ {{ $fmt($somaTotal) }}</td>
            </tr>
        </tfoot>
    </table>
</main>
</body>
</html>
