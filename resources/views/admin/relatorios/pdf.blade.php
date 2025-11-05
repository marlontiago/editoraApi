<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    @php
        $tituloPdf = (isset($tipoRelatorio) && $tipoRelatorio === 'financeiro')
            ? 'Relatório Financeiro'
            : 'Relatório Geral';
    @endphp
    <title>{{ $tituloPdf }}</title>
    <style>
        /* Margens padrão (header está no fluxo) */
        @page { margin: 36px 36px 90px 36px; }

        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11.5px; line-height: 1.45; color:#0f172a; }

        /* Header compacto (apenas na 1ª página, pois está no fluxo normal) */
        .header { margin-bottom: 16px; }
        .h-top { background: #0b2545; color: #e2e8f0; padding: 8px 12px; border-radius: 6px; }
        .brand { width: 100%; }
        .brand td { border: 0; padding: 0 6px; vertical-align: middle; }
        .title { font-size: 16px; font-weight: 700; color: #fff; line-height: 1.2; }
        .muted { color: #cbd5e1; font-size: 10px; }

        /* Footer fixo (todas as páginas) */
        .footer { position: fixed; bottom: -70px; left: 0; right: 0; height: 60px; color: #475569; font-size: 10.5px; }
        .footer .pagenum:after { content: counter(page) " / " counter(pages); }
        .f-line { border-top: 1px solid #e2e8f0; margin: 8px 0; }
        .f-wrap td { border:0; padding:2px 0; }

        /* Sections / Cards */
        .section-title { font-size: 13px; font-weight: 700; margin: 14px 0 8px; color: #0b2545; }
        .stat { border:1px solid #e2e8f0; border-radius:10px; padding:10px 12px; background:#f8fafc; }
        .stat .label { color:#475569; font-size:11px; }
        .stat .value { font-size:14px; font-weight:700; color:#0f172a; }
        .emph { color:#0b2545; font-weight:700; }

        /* Table (Notas) */
        table { width:100%; border-collapse:collapse; }
        .tbl { border:1px solid #e2e8f0; border-radius:10px; overflow:hidden; table-layout: fixed; }
        .tbl thead th { background:#f1f5f9; color:#0f172a; font-weight:700; font-size:10.5px; padding:6px; border-bottom:1px solid #e2e8f0; text-align:left; }
        .tbl tbody td { padding:6px; border-bottom:1px solid #f1f5f9; vertical-align:top; font-size:10.5px; }
        .tbl tbody tr:nth-child(2n) td { background:#fafafa; }
        .tbl tfoot td { padding:6px; border-top:1px solid #e2e8f0; background:#f8fafc; font-weight:700; font-size:10.5px; }


        .badge { display:inline-block; padding:0 5px; border-radius:8px; font-weight:600; font-size:10px; line-height:1.2; border:1px solid transparent; text-transform:uppercase; letter-spacing:0.3px; }
        .badge-pago { background:#86efac; color:#065f46; border-color:#16a34a; }
        .badge-parcial { background:#fde047; color:#854d0e; border-color:#ca8a04; }
        .badge-aguardando { background:#fca5a5; color:#7f1d1d; border-color:#dc2626; }
        .badge-default { background:#e5e7eb; color:#374151; border-color:#9ca3af; }
        .badge-remessa { background:#e0e7ff; color:#3730a3; border-color:#6366f1; }  /* indigo */
        .badge-brinde  { background:#fae8ff; color:#701a75; border-color:#d946ef; } 


        thead { display: table-header-group; }
        tfoot { display: table-row-group; }
        tr { page-break-inside: avoid; }

        .right { text-align:right; }
        .nowrap { white-space: nowrap; }
        .wrap { white-space: normal; word-break: break-word; }

        /* Larguras (A4 landscape) */
        .w-nota { width: 5%; }
        .w-cliente { width: 12%; }
        .w-gestor { width: 12%; }
        .w-dist { width: 12%; }
        .w-cidades { width: 10%; }
        .w-data { width: 10%; }
        .w-valor { width: 10%; }
        .w-liquido { width: 13%; }

        /* Cards grid */
        .cards-grid { width:100%; border-collapse:separate; border-spacing:0 10px; }
        .cards-grid td { padding:0; }
        .pad-r { padding-right:6px; }
        .pad-l { padding-left:6px; }
        .pad-x { padding:0 3px; }

        /* Lists */
        .mini-list { margin:8px 0 0; padding:0; list-style:none; }
        .mini-list li { display:flex; justify-content:space-between; gap:8px; font-size:10.5px; padding:2px 0; border-bottom:1px dashed #e5e7eb; }
        .mini-list li:last-child { border-bottom:0; }

        /* Mini tabelas */
        .mini-tbl { width:100%; border-collapse:collapse; table-layout: fixed; }
        .mini-tbl thead th { background:#f1f5f9; font-weight:700; font-size:10px; padding:4px 6px; text-align:left; }
        .mini-tbl tbody td { font-size:10px; padding:4px 6px; border-top:1px solid #f1f5f9; }
        .mini-tbl .right { text-align:right; }
        .w-n { width:22%; }   /* #Nota */
        .w-b { width:26%; }   /* Base */
        .w-p { width:16%; }   /* %    */
        .w-c { width:36%; }   /* Comissão */

        /* Retenções horizontais compactas (lado a lado) */
        .mini-ret { width:100%; border:1px solid #e2e8f0; border-radius:10px; overflow:hidden; }
        .mini-ret th { background:#f1f5f9; font-weight:700; font-size:10px; padding:4px 6px; text-align:center; }
        .mini-ret td { font-size:10px; padding:6px; border-top:1px solid #f1f5f9; text-align:right; }
        .mini-ret th, .mini-ret td { width:14.285%; } /* 7 colunas iguais */
    </style>
</head>
<body>
@php
    function moeda_br_pdf($v){ return 'R$ ' . number_format((float)$v, 2, ',', '.'); }
@endphp

<!-- Header compacto -->
<div class="header">
    <div class="h-top">
        <table class="brand">
            <tr>
                <td style="width:55%;">
                    <span class="title">{{ $tituloPdf }}</span>
                </td>
                <td style="width:45%; text-align:right; font-size:10.5px; line-height:1.4;">
                    @if(!empty($dataInicio) && !empty($dataFim))
                        <div><strong>Período:</strong> {{ \Carbon\Carbon::parse($dataInicio)->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($dataFim)->format('d/m/Y') }}</div>
                    @else
                        <div><strong>Período:</strong> Todos</div>
                    @endif
                    @if(!empty($statusFiltro))<div><strong>Status:</strong> {{ strtoupper($statusFiltro) }}</div>@endif
                    @if(!empty($filtroTipo) && !empty($filtroId))<div><strong>Filtro:</strong> {{ ucfirst($filtroTipo) }} #{{ $filtroId }}</div>@endif
                    @if(!empty($advogadoId))<div><strong>Advogado:</strong> #{{ $advogadoId }}</div>@endif
                    @if(!empty($diretorId))<div><strong>Diretor:</strong> #{{ $diretorId }}</div>@endif
                    @if(!empty($cidadeId))<div><strong>Cidade:</strong> #{{ $cidadeId }}</div>@endif
                    <div class="muted">Gerado em {{ now()->format('d/m/Y H:i') }}</div>
                </td>
            </tr>
        </table>
    </div>
</div>

<!-- Footer (fixo em todas as páginas) -->
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

<main>
    {{-- 1) Tabela de Notas --}}
    <div class="section-title">Notas no resultado</div>
    <table class="tbl">
        <thead>
            <tr>
                <th class="w-nota nowrap">Pedido</th>
                <th class="w-nota nowrap">Nota</th>
                <th class="w-cliente wrap">Cliente</th>
                <th class="w-gestor wrap">Gestor</th>
                <th class="w-dist wrap">Distribuidor</th>
                <th class="w-cidades wrap">Cidade</th>
                <th class="w-data nowrap">Emitida</th>
                <th class="w-data nowrap">Faturada</th>
                <th class="w-data nowrap">Status</th>
                <th class="w-valor right nowrap">Valor</th>
                <th class="w-liquido right nowrap">Pago (Liq.)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($notas as $n)
                @php
                    $pedido = $n->pedido;
                    $pgts = $n->pagamentos ?? collect();

                    if (!empty($dataInicio) && !empty($dataFim)) {
                        $pgts = $pgts->filter(function ($pg) use ($dataInicio, $dataFim) {
                            $d = \Carbon\Carbon::parse($pg->data_pagamento)->toDateString();
                            return $d >= $dataInicio && $d <= $dataFim;
                        });
                    }

                    $liquido = (float) $pgts->sum('valor_liquido');
                    $cidadesStr = $pedido && $pedido->cidades ? $pedido->cidades->pluck('name')->join(', ') : '—';
                @endphp
                <tr>
                    <td class="nowrap">#{{ $pedido->id }}</td>
                    <td class="nowrap">#{{ $n->id }}</td>
                    <td class="wrap">{{ $pedido->cliente->razao_social ?? '—' }}</td>
                    <td class="wrap">{{ $pedido->gestor->razao_social ?? '—' }}</td>
                    <td class="wrap">{{ $pedido->distribuidor->razao_social ?? '—' }}</td>
                    <td class="wrap">{{ $cidadesStr }}</td>
                    <td class="nowrap">{{ $n->emitida_em ? \Carbon\Carbon::parse($n->emitida_em)->format('d/m/Y') : '—' }}</td>
                    <td class="nowrap">{{ $n->faturada_em ? \Carbon\Carbon::parse($n->faturada_em)->format('d/m/Y') : '—' }}</td>
                    <td class="nowrap">
                    @php
                        $statusRaw = trim((string) $n->status_financeiro);
                        $status = mb_strtolower($statusRaw, 'UTF-8');

                        // mapeia para a chave da classe CSS e rótulo do badge
                        [$key, $label] = match (true) {
                            $status === 'pago'                        => ['pago',     'Pago'],
                            str_starts_with($status, 'pago_parcial')  => ['parcial',  'Pago parc.'],
                            $status === 'simples_remessa'             => ['remessa',  'Simp. remessa'],
                            $status === 'brinde'                      => ['brinde',   'Brinde'],
                            str_starts_with($status, 'aguardando')    => ['aguardando','Aguard. Pag.'],
                            default                                   => ['default',  '—'],
                        };
                    @endphp
                    <span class="badge badge-{{ $key }}">{{ $label }}</span>
                    </td>

                    <td class="right nowrap">{{ moeda_br_pdf($n->valor_total ?? 0) }}</td>
                    <td class="right nowrap">{{ moeda_br_pdf($liquido) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                {{-- 9 colunas: somamos apenas "Valor" e "Pago (Liq.)" --}}
                <td colspan="7" class="right">Totais:</td>
                <td class="right nowrap">{{ moeda_br_pdf($totais['total_bruto'] ?? 0) }}</td>
                <td class="right nowrap">{{ moeda_br_pdf($totais['total_liquido_pago'] ?? 0) }}</td>
            </tr>
        </tfoot>
    </table>

    {{-- 2) Retenções detalhadas (logo abaixo da tabela, valores lado a lado) --}}
    <div class="section-title">Retenções detalhadas</div>
    <table class="mini-ret">
        <thead>
            <tr>
                <th>IRRF</th>
                <th>ISS</th>
                <th>INSS</th>
                <th>PIS</th>
                <th>COFINS</th>
                <th>CSLL</th>
                <th>Outros</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ moeda_br_pdf($totais['retencoes_por_tipo']['IRRF'] ?? 0) }}</td>
                <td>{{ moeda_br_pdf($totais['retencoes_por_tipo']['ISS'] ?? 0) }}</td>
                <td>{{ moeda_br_pdf($totais['retencoes_por_tipo']['INSS'] ?? 0) }}</td>
                <td>{{ moeda_br_pdf($totais['retencoes_por_tipo']['PIS'] ?? 0) }}</td>
                <td>{{ moeda_br_pdf($totais['retencoes_por_tipo']['COFINS'] ?? 0) }}</td>
                <td>{{ moeda_br_pdf($totais['retencoes_por_tipo']['CSLL'] ?? 0) }}</td>
                <td>{{ moeda_br_pdf($totais['retencoes_por_tipo']['OUTROS'] ?? 0) }}</td>
            </tr>
        </tbody>
    </table>

    {{-- 3) Comissões (totais + breakdown detalhado por item) --}}
    <div class="section-title">Comissões por categoria</div>

    <table class="cards-grid">
        <tr>
            {{-- Gestores --}}
            <td class="pad-r" style="width:50%;">
                <div class="stat">
                    <div class="label">Gestores — Total</div>
                    <div class="value emph">{{ moeda_br_pdf($totais['comissao_gestor'] ?? 0) }}</div>

                    @foreach(($gestoresBreak ?? []) as $gid => $g)
                        <div style="margin-top:10px;">
                            <div style="display:flex; justify-content:space-between; gap:8px; font-size:12px;">
                                <div class="font-medium" style="flex:1; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $g['nome'] }}</div>
                                <div style="color:#475569;">
                                    qtd: <strong>{{ $g['qtd'] }}</strong> — % <strong>{{ number_format($g['perc'],2,',','.') }}</strong> — <strong>{{ moeda_br_pdf($g['total']) }}</strong>
                                </div>
                            </div>
                            @php $itens = $gestoresDetalhe[$gid] ?? []; @endphp
                            @if($itens)
                                <table class="mini-tbl" style="margin-top:6px;">
                                    <thead>
                                        <tr>
                                            <th class="w-n">#Nota</th>
                                            <th class="w-b right">Base</th>
                                            <th class="w-p right">% </th>
                                            <th class="w-c right">Comissão</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($itens as $item)
                                            <tr>
                                                <td>#{{ $item['nota'] }}</td>
                                                <td class="right">{{ moeda_br_pdf($item['base']) }}</td>
                                                <td class="right">{{ number_format($item['perc'],2,',','.') }}%</td>
                                                <td class="right"><strong>{{ moeda_br_pdf($item['comissao']) }}</strong></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif
                        </div>
                    @endforeach
                </div>
            </td>

            {{-- Distribuidores --}}
            <td class="pad-l" style="width:50%;">
                <div class="stat">
                    <div class="label">Distribuidores — Total</div>
                    <div class="value emph">{{ moeda_br_pdf($totais['comissao_distribuidor'] ?? 0) }}</div>

                    @foreach(($distsBreak ?? []) as $did => $d)
                        <div style="margin-top:10px;">
                            <div style="display:flex; justify-content:space-between; gap:8px; font-size:12px;">
                                <div class="font-medium" style="flex:1; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $d['nome'] }}</div>
                                <div style="color:#475569;">
                                    qtd: <strong>{{ $d['qtd'] }}</strong> — % <strong>{{ number_format($d['perc'],2,',','.') }}</strong> — <strong>{{ moeda_br_pdf($d['total']) }}</strong>
                                </div>
                            </div>
                            @php $itens = $distsDetalhe[$did] ?? []; @endphp
                            @if($itens)
                                <table class="mini-tbl" style="margin-top:6px;">
                                    <thead>
                                        <tr>
                                            <th class="w-n">#Nota</th>
                                            <th class="w-b right">Base</th>
                                            <th class="w-p right">% </th>
                                            <th class="w-c right">Comissão</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($itens as $item)
                                            <tr>
                                                <td>#{{ $item['nota'] }}</td>
                                                <td class="right">{{ moeda_br_pdf($item['base']) }}</td>
                                                <td class="right">{{ number_format($item['perc'],2,',','.') }}%</td>
                                                <td class="right"><strong>{{ moeda_br_pdf($item['comissao']) }}</strong></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif
                        </div>
                    @endforeach
                </div>
            </td>
        </tr>

        <tr>
            {{-- Advogados --}}
            <td class="pad-r" style="width:50%;">
                <div class="stat">
                    <div class="label">Advogados — Total</div>
                    <div class="value emph">{{ moeda_br_pdf($totais['comissao_advogado'] ?? 0) }}</div>

                    @foreach(($advogadosBreak ?? []) as $aid => $a)
                        <div style="margin-top:10px;">
                            <div style="display:flex; justify-content:space-between; gap:8px; font-size:12px;">
                                <div class="font-medium" style="flex:1; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $a['nome'] }}</div>
                                <div style="color:#475569;">
                                    qtd: <strong>{{ $a['qtd'] }}</strong>
                                    @if(!is_null($a['perc'])) — % <strong>{{ number_format($a['perc'],2,',','.') }}</strong>@endif
                                    — <strong>{{ moeda_br_pdf($a['total']) }}</strong>
                                </div>
                            </div>
                            @php $itens = $advogadosDetalhe[$aid] ?? []; @endphp
                            @if($itens)
                                <table class="mini-tbl" style="margin-top:6px;">
                                    <thead>
                                        <tr>
                                            <th class="w-n">#Nota</th>
                                            <th class="w-b right">Base</th>
                                            <th class="w-p right">% </th>
                                            <th class="w-c right">Comissão</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($itens as $item)
                                            <tr>
                                                <td>#{{ $item['nota'] }}</td>
                                                <td class="right">{{ moeda_br_pdf($item['base']) }}</td>
                                                <td class="right">{{ number_format($item['perc'],2,',','.') }}%</td>
                                                <td class="right"><strong>{{ moeda_br_pdf($item['comissao']) }}</strong></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif
                        </div>
                    @endforeach
                </div>
            </td>

            {{-- Diretores --}}
            <td class="pad-l" style="width:50%;">
                <div class="stat">
                    <div class="label">Diretores — Total</div>
                    <div class="value emph">{{ moeda_br_pdf($totais['comissao_diretor'] ?? 0) }}</div>

                    @foreach(($diretoresBreak ?? []) as $did => $d)
                        <div style="margin-top:10px;">
                            <div style="display:flex; justify-content:space-between; gap:8px; font-size:12px;">
                                <div class="font-medium" style="flex:1; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $d['nome'] }}</div>
                                <div style="color:#475569;">
                                    qtd: <strong>{{ $d['qtd'] }}</strong>
                                    @if(!is_null($d['perc'])) — % <strong>{{ number_format($d['perc'],2,',','.') }}</strong>@endif
                                    — <strong>{{ moeda_br_pdf($d['total']) }}</strong>
                                </div>
                            </div>
                            @php $itens = $diretoresDetalhe[$did] ?? []; @endphp
                            @if($itens)
                                <table class="mini-tbl" style="margin-top:6px;">
                                    <thead>
                                        <tr>
                                            <th class="w-n">#Nota</th>
                                            <th class="w-b right">Base</th>
                                            <th class="w-p right">% </th>
                                            <th class="w-c right">Comissão</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($itens as $item)
                                            <tr>
                                                <td>#{{ $item['nota'] }}</td>
                                                <td class="right">{{ moeda_br_pdf($item['base']) }}</td>
                                                <td class="right">{{ number_format($item['perc'],2,',','.') }}%</td>
                                                <td class="right"><strong>{{ moeda_br_pdf($item['comissao']) }}</strong></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif
                        </div>
                    @endforeach
                </div>
            </td>
        </tr>
    </table>

    {{-- 4) Resumo (por último) --}}
    <div class="section-title">Resumo</div>
    <table class="cards-grid">
        <tr>
            <td class="pad-r" style="width:33.3%;">
                <div class="stat">
                    <div class="label">Total bruto (notas)</div>
                    <div class="value emph">{{ moeda_br_pdf($totais['total_bruto'] ?? 0) }}</div>
                </div>
            </td>
            <td class="pad-x" style="width:33.3%;">
                <div class="stat">
                    <div class="label">Total líquido pago</div>
                    <div class="value">{{ moeda_br_pdf($totais['total_liquido_pago'] ?? 0) }}</div>
                </div>
            </td>
            <td class="pad-l" style="width:33.3%;">
                <div class="stat">
                    <div class="label">Total descontado (retenções + comissões)</div>
                    <div class="value">{{ moeda_br_pdf($totais['total_descontado'] ?? 0) }}</div>
                </div>
            </td>
        </tr>
    </table>
</main>
</body>
</html>
