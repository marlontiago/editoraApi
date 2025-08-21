<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Relatório Interno</title>
    <style>
        @page { margin: 110px 40px 90px 40px; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; line-height: 1.35; color: #111; }

        .header { position: fixed; top: -80px; left: 0; right: 0; height: 80px; }
        .footer { position: fixed; bottom: -65px; left: 0; right: 0; height: 60px; font-size: 11px; color: #666; }
        .footer .page-number:after { content: counter(page) " / " counter(pages); }

        .brand { display: flex; align-items: center; gap: 12px; }
        .brand img { height: 48px; }
        .brand h1 { margin: 0; font-size: 18px; }

        .chip { display: inline-block; background: #f2f2f2; border: 1px solid #ddd; padding: 4px 8px; border-radius: 4px; margin-right: 6px; font-size: 11px; }

        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #999; padding: 6px; text-align: left; vertical-align: top; }
        thead th { background: #f2f2f2; }
        tfoot td { font-weight: bold; }

        .right { text-align: right; }
        .muted { color: #666; }
    </style>
</head>
<body>
@php
    // Logo
    $logoPath = public_path('images/logo.jpeg');
    $logoBase64 = file_exists($logoPath) ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($logoPath)) : '';

    // Helpers
    $fmtMoeda = fn($v) => 'R$ ' . number_format((float)$v, 2, ',', '.');
    $fmtNum   = fn($v) => number_format((float)$v, 2, ',', '.');

    // Totais (já salvos)
    $valorBruto   = (float)$pedido->valor_bruto;
    $valorTotal   = (float)$pedido->valor_total;
    $totalDesc    = max($valorBruto - $valorTotal, 0);
    $percDescTot  = $valorBruto > 0 ? (100 * $totalDesc / $valorBruto) : 0;
@endphp

{{-- Cabeçalho --}}
<div class="header">
    <table style="width:100%; border:0; border-collapse:separate;">
        <tr>
            <td style="border:0;">
                <div class="brand">
                    @if($logoBase64)
                        <img src="{{ $logoBase64 }}" alt="Logo">
                    @endif
                    <h1>Relatório Interno do Pedido #{{ $pedido->id }}</h1>
                </div>
            </td>
            <td class="right" style="border:0;">
                <div style="font-size:12px;">
                    Data do Pedido: {{ \Carbon\Carbon::parse($pedido->data)->format('d/m/Y') }}<br>
                    Status: {{ ucfirst(str_replace('_',' ',$pedido->status)) }}
                </div>
            </td>
        </tr>
    </table>
    <hr>
</div>

{{-- Rodapé --}}
<div class="footer">
    <hr>
    <table style="width:100%; border:0; border-collapse:separate;">
        <tr>
            <td style="border:0;">Empresa • CNPJ 00.000.000/0000-00 • (00) 0000-0000 • contato@empresa.com</td>
            <td class="right" style="border:0;">Página <span class="page-number"></span></td>
        </tr>
    </table>
</div>

{{-- Meta --}}
<br>
<div>
    <span class="chip"><strong>Cliente:</strong> {{ $pedido->cliente->razao_social ?? $pedido->cliente->nome ?? '-' }}</span>
    <span class="chip"><strong>Gestor:</strong> {{ $pedido->gestor->razao_social ?? '-' }}</span>
    <span class="chip"><strong>Distribuidor:</strong> {{ $pedido->distribuidor->user->name ?? '-' }}</span>
    <span class="chip"><strong>Cidades:</strong>
        @forelse ($pedido->cidades as $c)
            {{ $c->name }}@if(!$loop->last), @endif
        @empty
            -
        @endforelse
    </span>
</div>

{{-- Itens --}}
<div class="section-title" style="margin: 12px 0 6px; font-weight: bold;">Itens do Pedido</div>
<table>
    <thead>
        <tr>
            <th>Produto</th>
            <th class="right" style="width:60px;">Qtd</th>
            <th class="right" style="width:90px;">Preço Unit.</th>
            <th class="right" style="width:80px;">Desc. (%)</th>
            <th class="right" style="width:100px;">Desc. (R$)</th>
            <th class="right" style="width:100px;">Subtotal</th>
            <th class="right" style="width:90px;">Peso (kg)</th>
            <th class="right" style="width:60px;">Caixas</th>
        </tr>
    </thead>
    <tbody>
        @foreach($pedido->produtos as $produto)
            @php
                $qtd        = (int)($produto->pivot->quantidade ?? 0);
                $precoUnit  = (float)($produto->pivot->preco_unitario ?? 0);
                $percItem   = (float)($produto->pivot->desconto_item ?? 0);
                $subBruto   = $precoUnit * $qtd;
                $subDesc    = (float)($produto->pivot->subtotal ?? ($precoUnit * (1 - $percItem/100) * $qtd));
                $descValor  = max($subBruto - $subDesc, 0);
                $pesoTotal  = (float)($produto->pivot->peso_total_produto ?? (($produto->peso ?? 0) * $qtd));
                $caixas     = (int)($produto->pivot->caixas ?? 0);
            @endphp
            <tr>
                <td>
                    <strong>{{ $produto->nome }}</strong>
                    @if(!empty($produto->isbn) || !empty($produto->autor))
                        <div class="muted" style="font-size:11px;">
                            @if(!empty($produto->isbn)) ISBN: {{ $produto->isbn }} @endif
                            @if(!empty($produto->autor)) • Autor: {{ $produto->autor }} @endif
                        </div>
                    @endif
                </td>
                <td class="right">{{ $qtd }}</td>
                <td class="right">{{ $fmtMoeda($precoUnit) }}</td>
                <td class="right">{{ number_format($percItem, 2, ',', '.') }}%</td>
                <td class="right">{{ $fmtMoeda($descValor) }}</td>
                <td class="right">{{ $fmtMoeda($subDesc) }}</td>
                <td class="right">{{ $fmtNum($pesoTotal) }}</td>
                <td class="right">{{ $caixas }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

{{-- Totais --}}
<table style="margin-top: 12px;">
    <tr>
        <td class="right" style="width:80%;"><strong>Valor Bruto</strong></td>
        <td class="right">{{ $fmtMoeda($valorBruto) }}</td>
    </tr>
    <tr>
        <td class="right"><strong>Total de Descontos</strong></td>
        <td class="right">{{ $fmtMoeda($totalDesc) }}</td>
    </tr>
    <tr>
        <td class="right"><strong>Percentual de Desconto</strong></td>
        <td class="right">{{ number_format($percDescTot, 2, ',', '.') }}%</td>
    </tr>
    <tr>
        <td class="right"><strong>Valor com Desconto</strong></td>
        <td class="right">{{ $fmtMoeda($valorTotal) }}</td>
    </tr>
    <tr>
        <td class="right"><strong>Peso Total</strong></td>
        <td class="right">{{ $fmtNum($pedido->peso_total) }} kg</td>
    </tr>
    <tr>
        <td class="right"><strong>Total de Caixas</strong></td>
        <td class="right">{{ $pedido->total_caixas }}</td>
    </tr>
</table>

{{-- Assinatura opcional --}}
<div style="margin-top: 40px;">
    <p>__________________________________________</p>
    <p class="muted">Responsável Interno</p>
</div>

</body>
</html>
