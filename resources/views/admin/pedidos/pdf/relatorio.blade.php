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

        .meta { margin-top: 8px; }
        .chip { display: inline-block; background: #f2f2f2; border: 1px solid #ddd; padding: 4px 8px; border-radius: 4px; margin-right: 6px; font-size: 11px; }

        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #999; padding: 6px; text-align: left; vertical-align: top; }
        thead th { background: #f2f2f2; }
        tfoot td { font-weight: bold; }

        .right { text-align: right; }
        .muted { color: #666; }
        .section-title { font-weight: bold; margin: 12px 0 6px; }
    </style>
</head>
<body>
@php
    // Logo em Base64
    $logoPath = public_path('images/logo.jpeg'); // ajuste se necessário
    $logoBase64 = '';
    if (file_exists($logoPath)) {
        $logoBase64 = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($logoPath));
    }

    // Helpers de formatação
    $fmtMoeda = fn($v) => 'R$ ' . number_format((float)$v, 2, ',', '.');
    $fmtNum    = fn($v) => number_format((float)$v, 2, ',', '.');
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
            <td style="text-align:right; border:0;">
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

{{-- Conteúdo --}}
<br>
<div class="meta">
    <div class="section-title">Informações</div>
    <div class="chip"><strong>Gestor:</strong> {{ $pedido->gestor->razao_social ?? '-' }}</div>
    <div class="chip"><strong>Distribuidor:</strong> {{ $pedido->distribuidor->user->name ?? '-' }}</div>
    <div class="chip"><strong>Cidades:</strong>
        @forelse ($pedido->cidades as $c)
            {{ $c->name }}@if(!$loop->last), @endif
        @empty
            -
        @endforelse
    </div>
</div>

<div class="section-title">Itens do Pedido</div>
<table>
    <thead>
        <tr>
            <th>Produto</th>
            <th class="right" style="width:60px;">Qtd</th>
            <th class="right" style="width:90px;">Preço Unit.</th>
            <th class="right" style="width:80px;">Desc. (%)</th>
            <th class="right" style="width:100px;">Subtotal</th>
            <th class="right" style="width:90px;">Peso (kg)</th>
            <th class="right" style="width:60px;">Caixas</th>
        </tr>
    </thead>
    <tbody>
        @foreach($pedido->produtos as $produto)
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
                <td class="right">{{ $produto->pivot->quantidade }}</td>
                <td class="right">{{ $fmtMoeda($produto->pivot->preco_unitario) }}</td>
                <td class="right">{{ number_format((float)$produto->pivot->desconto_aplicado, 0, ',', '.') }}%</td>
                <td class="right">{{ $fmtMoeda($produto->pivot->subtotal) }}</td>
                <td class="right">{{ $fmtNum($produto->pivot->peso_total_produto) }}</td>
                <td class="right">{{ $produto->pivot->caixas }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<table style="margin-top: 12px;">
    <tr>
        <td class="right" style="width:80%;"><strong>Valor Bruto</strong></td>
        <td class="right">{{ $fmtMoeda($pedido->valor_bruto) }}</td>
    </tr>
    <tr>
        <td class="right"><strong>Desconto</strong></td>
        <td class="right">{{ number_format((float)$pedido->desconto, 0, ',', '.') }}%</td>
    </tr>
    <tr>
        <td class="right"><strong>Valor com Desconto</strong></td>
        <td class="right">{{ $fmtMoeda($pedido->valor_total) }}</td>
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

{{-- Assinatura opcional (uso interno) --}}
<div style="margin-top: 48px;">
    <p>__________________________________________</p>
    <p class="muted">Responsável Interno</p>
</div>

</body>
</html>
