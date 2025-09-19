<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Orçamento</title>
    <style>
        @page { margin: 110px 40px 90px 40px; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; line-height: 1.35; color:#111; }

        .header { position: fixed; top: -80px; left: 0; right: 0; height: 80px; }
        .footer { position: fixed; bottom: -65px; left: 0; right: 0; height: 60px; font-size: 11px; color: #666; }
        .footer .page-number:after { content: counter(page) " / " counter(pages); }

        .brand { display: flex; align-items: center; gap: 12px; }
        .brand img { height: 48px; }
        .brand h1 { margin: 0; font-size: 18px; }

        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #999; padding: 6px; text-align: left; vertical-align: top; }
        thead th { background: #f2f2f2; }
        .right { text-align: right; }
        .muted { color:#666; }
    </style>
</head>
<body>
@php
    // Logo (base64)
    $logoPath = public_path('images/logo.jpeg');
    $logoBase64 = file_exists($logoPath) ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($logoPath)) : '';

    // Helpers
    $fmtMoeda = fn($v) => 'R$ ' . number_format((float)$v, 2, ',', '.');

    // Totais do pedido (já salvos pelo controller)
    $valorBruto   = (float)$pedido->valor_bruto;
    $valorTotal   = (float)$pedido->valor_total;
    $totalDesc    = max($valorBruto - $valorTotal, 0);
    $percDescTot  = $valorBruto > 0 ? (100 * $totalDesc / $valorBruto) : 0;

    // Função p/ carregar imagem de produto
    function img_src_or_placeholder($pathRelative) {
        $full = null;
        $rel  = $pathRelative ? ltrim($pathRelative, '/') : '';

        // Locais candidatos
        $candidatos = [];
        if ($rel !== '') {
            $candidatos[] = storage_path('app/public/' . $rel);   // storage/app/public/images/...
            $candidatos[] = public_path($rel);                    // public/images/...
            $candidatos[] = public_path('storage/' . $rel);       // public/storage/images/...
        }
        $candidatos[] = public_path('images/placeholder.png');    // fallback

        // Pega o primeiro que existir
        foreach ($candidatos as $p) {
            if (file_exists($p)) { $full = $p; break; }
        }

        // Gera base64
        $ext  = strtolower(pathinfo($full, PATHINFO_EXTENSION) ?: 'png');
        $mime = $ext === 'jpg' ? 'jpeg' : $ext;
        $data = @file_get_contents($full);

        return $data ? ('data:image/'.$mime.';base64,'.base64_encode($data)) : '';
    }
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
                    <h1>Orçamento Comercial #{{ $pedido->id }}</h1>
                </div>
            </td>
            <td class="right" style="border:0;">
                <div style="font-size:12px;">
                    Data: {{ \Carbon\Carbon::parse($pedido->data)->format('d/m/Y') }}<br>
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
<div style="margin-top: 8px;">
    <br>
    <p><strong>Cliente:</strong> {{ $pedido->cliente->razao_social ?? $pedido->cliente->nome ?? '-' }}</p>
    <p><strong>Distribuidor:</strong> {{ $pedido->distribuidor->user->name ?? '-' }}</p>
    <p><strong>Cidades:</strong>
        @forelse ($pedido->cidades as $c)
            {{ $c->name }}@if(!$loop->last), @endif
        @empty
            -
        @endforelse
    </p>
</div>

{{-- Itens --}}
<table style="margin-top: 8px;">
    <thead>
        <tr>
            <th style="width:90px;" class="right">Imagem</th>
            <th stylr="width:110px" class="right">Produto</th>
            <th style="width:30px;" class="right">Qtd</th>
            <th style="width:60px;" class="right">Preço Unit.</th>
            <th style="width:60px;" class="right">Desc. (%)</th>
            <th style="width:60px;" class="right">Desc. (R$)</th>
            <th style="width:90px;" class="right">Subtotal</th>
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
                $imgSrc     = img_src_or_placeholder($produto->imagem ?? null);
            @endphp
            <tr>
                <td style="text-align:center;">
                    @if($imgSrc)
                        <img src="{{ $imgSrc }}" style="max-height:60px; max-width:60px;">
                    @endif
                </td>
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
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="6" class="right" style="font-weight:bold;">Valor Bruto</td>
            <td class="right" style="font-weight:bold;">{{ $fmtMoeda($valorBruto) }}</td>
        </tr>
        <tr>
            <td colspan="6" class="right">Total de Descontos</td>
            <td class="right">{{ $fmtMoeda($totalDesc) }}</td>
        </tr>
        <tr>
            <td colspan="6" class="right">Percentual de Desconto</td>
            <td class="right">{{ number_format($percDescTot, 2, ',', '.') }}%</td>
        </tr>
        <tr>
            <td colspan="6" class="right" style="font-weight:bold;">Total com Desconto</td>
            <td class="right" style="font-weight:bold;">{{ $fmtMoeda($valorTotal) }}</td>
        </tr>
    </tfoot>
</table>

<div style="margin-top: 20px; font-size: 11px;">
    * Este orçamento é válido por 15 dias. Valores sujeitos a alteração sem aviso prévio.
</div>

<div style="margin-top: 40px;">
    <p>__________________________________________</p>
    <p>Assinatura / Carimbo</p>
</div>
</body>
</html>
