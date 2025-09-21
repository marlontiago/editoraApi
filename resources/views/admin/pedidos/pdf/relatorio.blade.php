<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Relatório Interno #{{ $pedido->id }}</title>
    <style>
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
        .brand .logo { height: 80px; border-radius:10px; display:block; object-fit:contain; }
        .muted { color:#cbd5e1; font-size: 11px; }

        .f-line { border-top: 1px solid #e2e8f0; margin: 8px 0; }
        .f-wrap td { border:0; padding:2px 0; }

        /* ===== Sections ===== */
        .section-title { font-size: 13px; font-weight: 700; margin: 14px 0 8px; color: #0b2545; }
        .card { border:1px solid #e2e8f0; border-radius:10px; padding:12px; background:#fff; }

        /* ===== Tables ===== */
        table { width:100%; border-collapse:collapse; }
        .tbl {
            width:100%;
            table-layout: fixed;         /* evita ultrapassar a área útil */
            border:1px solid #e2e8f0;
            border-radius:10px;
            overflow:hidden;
        }
        .tbl *, .tbl th, .tbl td { box-sizing: border-box; }
        .tbl thead th {
            background:#f1f5f9; color:#0f172a; font-weight:700; font-size:11.5px;
            padding:8px; border-bottom:1px solid #e2e8f0; text-align:left;
        }
        .tbl th.right, .tbl td.right { text-align:right; }
        .tbl tbody td {
            padding:8px; border-bottom:1px solid #f1f5f9; vertical-align:top;
            word-wrap: break-word; white-space: normal;  /* evita estourar na direita */
        }
        .tbl tbody tr:nth-child(2n) td { background:#fafafa; }
        .tbl tfoot td { padding:8px; border-top:1px solid #e2e8f0; background:#f8fafc; }

        .p-meta { color:#64748b; font-size:10.5px; margin-top:2px; }

        /* Totals (cards) */
        .stat { border:1px solid #e2e8f0; border-radius:10px; padding:10px 12px; background:#f8fafc; }
        .stat .label { color:#475569; font-size:11px; }
        .stat .value { font-size:14px; font-weight:700; color:#0f172a; }
        .emph { color:#0b2545; font-weight:700; }

        .terms { border:1px dashed #cbd5e1; border-radius:10px; padding:10px 12px; background:#f8fafc; color:#334155; font-size:10.5px; margin-top:26px; }
        .sign { margin-top:32px; }
    </style>
</head>
<body>
@php
    // Logo
    $logoPath   = public_path('images/logo.jpeg');
    $logoBase64 = file_exists($logoPath) ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($logoPath)) : '';

    // Helpers
    $fmtMoeda = fn($v) => 'R$ ' . number_format((float)$v, 2, ',', '.');
    $fmtNum   = fn($v) => number_format((float)$v, 2, ',', '.');

    // Totais do pedido
    $valorBruto  = (float)($pedido->valor_bruto ?? 0);
    $valorTotal  = (float)($pedido->valor_total ?? 0);
    $totalDesc   = max($valorBruto - $valorTotal, 0);
    $percDescTot = $valorBruto > 0 ? (100 * $totalDesc / $valorBruto) : 0;
@endphp

{{-- ===== Header ===== --}}
<div class="header">
    <div class="h-top">
        <table class="brand" style="width:100%; height:100%;">
            <tr style="vertical-align: middle;">
                <!-- Logo + Título alinhados e a meia altura -->
                <td style="width:60%; vertical-align: middle;">
                    <div style="display:flex; align-items:center; gap:20px; height:100%;">
                        @if($logoBase64)
                            <img src="{{ $logoBase64 }}" class="logo">
                        @endif
                        <span style="font-size:20px; font-weight:600; color:#fff; white-space:nowrap; line-height:1;">
                            Relatório Interno do Pedido #{{ $pedido->id }}
                        </span>
                    </div>
                </td>

                <!-- Informações do pedido à direita -->
                <td style="width:40%; text-align:right; vertical-align: middle;">
                    <div style="font-size:11px; line-height:1.5;">
                        <div><strong>Data:</strong> {{ \Carbon\Carbon::parse($pedido->data)->format('d/m/Y') }}</div>
                        <div><strong>Status:</strong> {{ ucfirst(str_replace('_',' ',$pedido->status)) }}</div>
                        <div><strong>Cliente:</strong> {{ $pedido->cliente->razao_social ?? $pedido->cliente->nome ?? '—' }}</div>
                        <div><strong>Gestor:</strong> {{ $pedido->gestor->razao_social ?? '—' }}</div>
                        <div><strong>Distribuidor:</strong> {{ $pedido->distribuidor->user->name ?? '—' }}</div>
                        @if($pedido->cidades && $pedido->cidades->count())
                            <div>
                                <strong>Cidades:</strong>
                                @foreach ($pedido->cidades as $c)
                                    <span>{{ $c->name }}@if(!$loop->last), @endif</span>
                                @endforeach
                            </div>
                        @endif
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

{{-- ===== Itens do Pedido ===== --}}
<div class="mt-6">
    <div class="section-title">Itens do Pedido</div>
    <table class="tbl">
        <thead>
        <tr>
            <th style="width:40%">Produto</th>
            <th class="right" style="width:15%;">Valor Unitário</th>
            <th class="right" style="width:10%;">Qtd</th>
            <th class="right" style="width:15%;">Bruto</th>
            <th class="right" style="width:10%;">Desc.</th>
            <th class="right" style="width:15%;">Líquido</th>
            <th class="right" style="width:10%;">Caixas</th>
        </tr>
        </thead>
        <tbody>
        @foreach($pedido->produtos as $produto)
            @php
                $qtd        = (int)($produto->pivot->quantidade ?? 0);
                $precoUnit  = (float)($produto->pivot->preco_unitario ?? 0);
                $percItem   = (float)($produto->pivot->desconto_item ?? 0);

                $totalBruto   = $precoUnit * $qtd;
                $totalLiquido = (float)($produto->pivot->subtotal ?? ($precoUnit * (1 - $percItem/100) * $qtd));

                $caixas = (int)($produto->pivot->caixas ?? 0);
            @endphp
            <tr>
                <td>
                    <strong>{{ $produto->titulo ?? $produto->nome }}</strong>
                    @if(!empty($produto->isbn) || !empty($produto->autor))
                        <div class="p-meta">
                            @if(!empty($produto->isbn)) ISBN: {{ $produto->isbn }} @endif
                            @if(!empty($produto->autor)) 
                                @if(!empty($produto->isbn)) • @endif Autor: {{ $produto->autor }} 
                            @endif
                        </div>
                    @endif
                </td>
                <td class="right">{{ $fmtMoeda($precoUnit) }}</td>
                <td class="right">{{ $qtd }}</td>
                <td class="right">{{ $fmtMoeda($totalBruto) }}</td>
                <td class="right">{{ number_format($percItem, 2, ',', '.') }}%</td>
                <td class="right">{{ $fmtMoeda($totalLiquido) }}</td>
                <td class="right">{{ $caixas }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>



{{-- ===== Resumo (cards) ===== --}}
<div class="mt-6">
    <div class="section-title">Resumo do Pedido</div>
    <table style="width:100%; border-collapse:separate; border-spacing:0 10px;">
        <tr>
            <td style="width:33.3%; padding-right:6px;">
                <div class="stat">
                    <div class="label">Valor Bruto</div>
                    <div class="value">{{ $fmtMoeda($valorBruto) }}</div>
                </div>
            </td>
            <td style="width:33.3%; padding:0 3px;">
                <div class="stat">
                    <div class="label">Descontos ({{ number_format($percDescTot,2,',','.') }}%)</div>
                    <div class="value">{{ $fmtMoeda($totalDesc) }}</div>
                </div>
            </td>
            <td style="width:33.3%; padding-left:6px;">
                <div class="stat">
                    <div class="label">Total com Desconto</div>
                    <div class="value emph">{{ $fmtMoeda($valorTotal) }}</div>
                </div>
            </td>
        </tr>
        <tr>
            <td style="width:33.3%; padding-right:6px;">
                <div class="stat">
                    <div class="label">Peso Total</div>
                    <div class="value">{{ $fmtNum($pedido->peso_total) }} kg</div>
                </div>
            </td>
            <td style="width:33.3%; padding:0 3px;">
                <div class="stat">
                    <div class="label">Total de Caixas</div>
                    <div class="value">{{ (int)($pedido->total_caixas ?? 0) }}</div>
                </div>
            </td>
            <td style="width:33.3%; padding-left:6px;">
                <div class="stat">
                    <div class="label">Status do Pedido</div>
                    <div class="value">{{ ucfirst(str_replace('_',' ',$pedido->status)) }}</div>
                </div>
            </td>
        </tr>
    </table>
</div>

{{-- Observações (se houver) --}}
@php $obs = trim((string) ($pedido->observacoes ?? '')); @endphp
@if($obs !== '')
    <div class="card" style="margin-top:16px;">
        <div class="section-title" style="margin:0 0 8px;">Observações</div>
        <div style="white-space:pre-line; color:#334155; font-size:11.5px;">{{ $obs }}</div>
    </div>
@endif

{{-- Assinatura opcional --}}
<div class="sign">
    <p>__________________________________________</p>
    <p class="muted">Responsável Interno</p>
</div>

</body>
</html>
