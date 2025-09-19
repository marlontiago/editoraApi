<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Relatório Financeiro</title>
    <style>
        /* Margens da página devem comportar header e footer fixos */
        @page { margin: 120px 36px 90px 36px; }

        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11.5px; line-height: 1.45; color:#0f172a; }

        /* ===== Header / Footer ===== */
        .header { position: fixed; top: -100px; left: 0; right: 0; height: 100px; }
        .footer { position: fixed; bottom: -70px; left: 0; right: 0; height: 60px; color: #475569; font-size: 10.5px; }
        .footer .pagenum:after { content: counter(page) " / " counter(pages); }

        .h-top {
            background: #0b2545;
            color: #e2e8f0;
            padding: 14px 18px;
            border-radius: 10px;
        }
        .brand { width:100%; height:100%; }
        .brand td { border:0; padding:0; }
        .brand .logo { height: 46px; }
        .title { font-size:20px; font-weight:700; color:#fff; white-space:nowrap; line-height:1; }
        .muted { color:#cbd5e1; font-size: 11px; }

        .f-line { border-top: 1px solid #e2e8f0; margin: 8px 0; }
        .f-wrap td { border:0; padding:2px 0; }

        /* ===== Sections / Cards ===== */
        .section-title { font-size: 13px; font-weight: 700; margin: 14px 0 8px; color: #0b2545; }

        .stat { border:1px solid #e2e8f0; border-radius:10px; padding:10px 12px; background:#f8fafc; }
        .stat .label { color:#475569; font-size:11px; }
        .stat .value { font-size:14px; font-weight:700; color:#0f172a; }
        .emph { color:#0b2545; font-weight:700; }

        /* ===== Table ===== */
        table { width:100%; border-collapse:collapse; }
        .tbl { border:1px solid #e2e8f0; border-radius:10px; overflow:hidden; }
        .tbl thead th { background:#f1f5f9; color:#0f172a; font-weight:700; font-size:11.5px; padding:8px; border-bottom:1px solid #e2e8f0; text-align:left; }
        .tbl th.right, .tbl td.right { text-align:right; }
        .tbl tbody td { padding:8px; border-bottom:1px solid #f1f5f9; vertical-align:top; }
        .tbl tbody tr:nth-child(2n) td { background:#fafafa; }
        .tbl tfoot td { padding:8px; border-top:1px solid #e2e8f0; background:#f8fafc; font-weight:700; }

        /* Repetir cabeçalho da tabela em novas páginas */
        thead { display: table-header-group; }
        tfoot { display: table-row-group; }
        tr { page-break-inside: avoid; }

        /* Utilidades de grid “cards” */
        .cards-grid { width:100%; border-collapse:separate; border-spacing:0 10px; }
        .cards-grid td { padding:0; }
        .pad-r { padding-right:6px; }
        .pad-l { padding-left:6px; }
        .pad-x { padding:0 3px; }
    </style>
</head>
<body>
@php
    function moeda_br_pdf($v){ return 'R$ ' . number_format((float)$v, 2, ',', '.'); }
@endphp

{{-- ===== Header ===== --}}
<div class="header">
    <div class="h-top">
        <table class="brand" style="width:100%; height:100%;">
            <tr>
                <!-- Título centralizado verticalmente ao lado esquerdo (pode colocar logo se tiver) -->
                <td style="width:60%; vertical-align: middle;">
                    <div style="display:flex; align-items:center; gap:14px; height:100%;">
                        {{-- Se quiser logo, descomente:
                        @if(isset($logoBase64) && $logoBase64)
                            <img src="{{ $logoBase64 }}" class="logo" style="display:block; object-fit:contain;">
                        @endif
                        --}}
                        <span class="title">Relatório Financeiro</span>
                    </div>
                </td>

                <!-- Filtros/contexto à direita -->
                <td style="width:40%; text-align:right; vertical-align: middle;">
                    <div style="font-size:11px; line-height:1.5;">
                        @if($dataInicio && $dataFim)
                            <div><strong>Período:</strong> {{ \Carbon\Carbon::parse($dataInicio)->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($dataFim)->format('d/m/Y') }}</div>
                        @else
                            <div><strong>Período:</strong> Todos</div>
                        @endif
                        @if($statusFiltro)<div><strong>Status:</strong> {{ strtoupper($statusFiltro) }}</div>@endif
                        @if($filtroTipo && $filtroId)<div><strong>Filtro:</strong> {{ ucfirst($filtroTipo) }} #{{ $filtroId }}</div>@endif
                        <div class="muted" style="margin-top:4px;">Gerado em {{ now()->format('d/m/Y H:i') }}</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>
</div>

{{-- ===== Footer ===== --}}
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
    @if($exportUsuario)
        {{-- ===== Cards de Resumo ===== --}}
        <div class="section-title">Resumo</div>
        <table class="cards-grid">
            <tr>
                <td class="pad-r" style="width:33.3%;">
                    <div class="stat">
                        <div class="label">Total líquido do período</div>
                        <div class="value emph">
                            {{ moeda_br_pdf( (float) $pedidos->sum(fn($p) => (float) ($p->valor_liquido_pago_total ?? 0)) ) }}
                        </div>
                    </div>
                </td>
                <td class="pad-x" style="width:33.3%;">
                    <div class="stat">
                        <div class="label">Total de comissões</div>
                        <div class="value">
                            {{ moeda_br_pdf( (float) $pedidos->sum(fn($p) => (float) ($p->comissao_do_filtro ?? 0)) ) }}
                        </div>
                    </div>
                </td>
                <td class="pad-l" style="width:33.3%;">
                    <div class="stat">
                        <div class="label">Pedidos no resultado</div>
                        <div class="value">{{ $pedidos->count() }}</div>
                    </div>
                </td>
            </tr>
        </table>

        {{-- ===== Tabela ===== --}}
        <div class="section-title">Detalhamento</div>
        <table class="tbl">
            <thead>
                <tr>
                    <th style="width:70px;">Pedido</th>
                    <th>Cliente</th>
                    <th>Gestor</th>
                    <th>Distribuidor</th>
                    <th class="right" style="width:120px;">Valor Líquido Pago</th>
                    <th style="width:100px;">Financeiro</th>
                    <th class="right" style="width:110px;">Comissão</th>
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
                    <td colspan="4" class="right">Totais:</td>
                    <td class="right">
                        {{ moeda_br_pdf( (float) $pedidos->sum(fn($p) => (float) ($p->valor_liquido_pago_total ?? 0)) ) }}
                    </td>
                    <td></td>
                    <td class="right">
                        {{ moeda_br_pdf( (float) $pedidos->sum(fn($p) => (float) ($p->comissao_do_filtro ?? 0)) ) }}
                    </td>
                </tr>
            </tfoot>
        </table>

    @elseif($exportStatus)
        {{-- ===== Cards de Resumo ===== --}}
        <div class="section-title">Resumo</div>
        <table class="cards-grid">
            <tr>
                <td class="pad-r" style="width:33.3%;">
                    <div class="stat">
                        <div class="label">Pedidos no resultado</div>
                        <div class="value">{{ $pedidoStatus->count() }}</div>
                    </div>
                </td>
                <td class="pad-x" style="width:33.3%;">
                    <div class="stat">
                        <div class="label">Soma do valor da nota</div>
                        <div class="value emph">
                            {{ moeda_br_pdf( (float) $pedidoStatus->sum(fn($p) => (float) optional($p->notaFiscal)->valor_total) ) }}
                        </div>
                    </div>
                </td>
                <td class="pad-l" style="width:33.3%;">
                    <div class="stat">
                        <div class="label">Status</div>
                        <div class="value">{{ strtoupper($statusFiltro ?? '—') }}</div>
                    </div>
                </td>
            </tr>
        </table>

        {{-- ===== Tabela ===== --}}
        <div class="section-title">Detalhamento</div>
        <table class="tbl">
            <thead>
                <tr>
                    <th style="width:70px;">Pedido</th>
                    <th>Cliente</th>
                    <th>Gestor</th>
                    <th>Distribuidor</th>
                    <th class="right" style="width:120px;">Valor da Nota</th>
                    <th style="width:120px;">Financeiro</th>
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
                    <td colspan="4" class="right">Total:</td>
                    <td class="right">
                        {{ moeda_br_pdf( (float) $pedidoStatus->sum(fn($p) => (float) optional($p->notaFiscal)->valor_total) ) }}
                    </td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    @endif
</main>

</body>
</html>
