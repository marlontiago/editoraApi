<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Orçamento #{{ $pedido->id }}</title>
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
        .brand { width:100%; }
        .brand td { vertical-align: top; border: 0; padding: 0; }
        .brand .logo { height: 46px; }
        .brand .title { font-size: 18px; font-weight: 700; margin: 0; }
        .muted { color:#cbd5e1; font-size: 11px; }

        .f-line { border-top: 1px solid #e2e8f0; margin: 8px 0; }
        .f-wrap td { border:0; padding:2px 0; }

        /* ===== Sections ===== */
        .section-title { font-size: 13px; font-weight: 700; margin: 14px 0 8px; color: #0b2545; }
        .card { border:1px solid #e2e8f0; border-radius:10px; padding:12px; background:#fff; }

        .chip { display:inline-block; padding:3px 8px; border-radius:999px; background:#eef2ff; color:#3730a3; border:1px solid #c7d2fe; font-size:10.5px; margin:2px 4px 0 0; }

        /* ===== Tables ===== */
        table { width:100%; border-collapse:collapse; }
        .tbl { border:1px solid #e2e8f0; border-radius:10px; overflow:hidden; }
        .tbl thead th { background:#f1f5f9; color:#0f172a; font-weight:700; font-size:11.5px; padding:8px; border-bottom:1px solid #e2e8f0; text-align:left; }
        .tbl th.right, .tbl td.right { text-align:right; }
        .tbl tbody td { padding:8px; border-bottom:1px solid #f1f5f9; vertical-align:top; }
        .tbl tbody tr:nth-child(2n) td { background:#fafafa; }
        .tbl tfoot td { padding:8px; border-top:1px solid #e2e8f0; background:#f8fafc; }
        .product-img { display:block; max-height:80px; max-width:80px; margin:0 auto; border-radius:6px; border:1px solid #e5e7eb; }
        .p-meta { color:#64748b; font-size:10.5px; margin-top:2px; }

        /* Totals */
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
    $logoPath   = public_path('images/logo.jpeg');
    $logoBase64 = file_exists($logoPath) ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($logoPath)) : '';
    $fmtMoeda   = fn($v) => 'R$ ' . number_format((float)$v, 2, ',', '.');

    $valorBruto  = (float)($pedido->valor_bruto ?? 0);
    $valorTotal  = (float)($pedido->valor_total ?? 0);
    $totalDesc   = max($valorBruto - $valorTotal, 0);
    $percDescTot = $valorBruto > 0 ? (100 * $totalDesc / $valorBruto) : 0;

    function img_src_or_placeholder($pathRelative) {
        $full = null; $rel = $pathRelative ? ltrim($pathRelative, '/') : '';
        $cands=[]; if($rel!==''){ $cands[] = storage_path('app/public/'.$rel); $cands[] = public_path($rel); $cands[] = public_path('storage/'.$rel); }
        $cands[] = public_path('images/placeholder.png');
        foreach($cands as $p){ if(file_exists($p)){ $full=$p; break; } }
        $ext=strtolower(pathinfo($full,PATHINFO_EXTENSION)?:'png'); $mime=$ext==='jpg'?'jpeg':$ext;
        $data=@file_get_contents($full); return $data?('data:image/'.$mime.';base64,'.base64_encode($data)):'';
    }

    // 🟢 Define título conforme status do pedido
    $tituloDocumento = match($pedido->status) {
        'em_andamento' => 'Estudo de Preço',
        'cancelado'    => 'Orçamento Cancelado',
        default        => 'Orçamento Comercial',
    };
@endphp

{{-- Header com Informações do Pedido --}}
<div class="header">
    <div class="h-top">
        <table class="brand" style="width:100%; height:100%;">
            <tr style="vertical-align: middle;">
                {{-- Logo + título lado a lado centralizados --}}
                <td style="width:60%; vertical-align: middle;">
                    <div style="display:flex; align-items:center; gap:14px; height:100%;">
                        @if($logoBase64)
                            <img src="{{ $logoBase64 }}" class="logo"
                                 style="height:46px; display:block; object-fit:contain; margin-top:30px">
                        @endif
                        <span style="font-size:20px; font-weight:700; color:#fff; white-space:nowrap; line-height:1;">
                            {{ $tituloDocumento }}
                        </span>
                    </div>
                </td>

                {{-- Informações do pedido --}}
                <td style="width:40%; text-align:right; vertical-align: middle;">
                    <div style="font-size:11px; line-height:1.5;">
                        <div><strong>Data:</strong> {{ \Carbon\Carbon::parse($pedido->data)->format('d/m/Y') }}</div>
                        <div><strong>Cliente:</strong> {{ $pedido->cliente->razao_social ?? $pedido->cliente->nome ?? '—' }}</div>
                        @if($pedido->cliente)
                            <div>
                                <strong>Endereço:</strong>
                                {{ $pedido->cliente->endereco ?? '' }}
                                {{ $pedido->cliente->numero ? ', '.$pedido->cliente->numero : '' }}
                                @if($pedido->cliente->cidade || $pedido->cliente->uf)
                                    {{ ' • ' }}
                                    {{ $pedido->cliente->cidade ?? '' }}{{ $pedido->cliente->uf ? '/'.$pedido->cliente->uf : '' }}
                                @endif
                            </div>
                        @endif
                    </div>
                </td>
            </tr>
        </table>
    </div>
</div>

{{-- Footer --}}
<div class="footer">
    <div class="f-line"></div>
    <table class="f-wrap">
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

{{-- Itens --}}
<div class="mt-6">
    <div class="section-title">Itens do Orçamento</div>
    <table class="tbl">
        <thead>
            <tr>
                <th style="width:20%; text-align:center;">Imagem</th>
                <th style="width:35%">Produto</th>
                <th class="right" style="width:10%;">Qtd</th>
                <th class="right" style="width:10%;">Preço Unit.</th>
                <th class="right" style="width:10%;">Desc.</th>
                <th class="right" style="width:15%">Subtotal</th>
            </tr>
        </thead>
        <tbody>
        @foreach($pedido->produtos as $produto)
            @php
                $qtd       = (int)($produto->pivot->quantidade ?? 0);
                $precoUnit = (float)($produto->pivot->preco_unitario ?? 0);
                $percItem  = (float)($produto->pivot->desconto_item ?? 0);
                $subBruto  = $precoUnit * $qtd;
                $subDesc   = (float)($produto->pivot->subtotal ?? ($precoUnit * (1 - $percItem/100) * $qtd));
                $descValor = max($subBruto - $subDesc, 0);
                $imgSrc    = img_src_or_placeholder($produto->imagem ?? null);
            @endphp
            <tr>
                <td style="text-align:center;">@if($imgSrc)<img src="{{ $imgSrc }}" class="product-img">@endif</td>
                <td>
                    <strong>{{ $produto->titulo ?? $produto->nome }}</strong>
                    @if($produto->isbn || $produto->autor)
                        <div class="p-meta">
                            @if($produto->isbn) ISBN: {{ $produto->isbn }} @endif
                            @if($produto->autor) @if($produto->isbn) • @endif Autor: {{ $produto->autor }} @endif
                        </div>
                    @endif
                </td>
                <td class="right">{{ $qtd }}</td>
                <td class="right">{{ $fmtMoeda($precoUnit) }}</td>
                <td class="right">{{ number_format($percItem, 2, ',', '.') }}%</td>
                <td class="right">{{ $fmtMoeda($subDesc) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

{{-- Resumo Financeiro --}}
<div class="mt-6">
    <div class="section-title">Resumo Financeiro</div>
    <table style="width:100%; border-collapse:separate; border-spacing:0 10px;">
        <tr>
            <td style="width:33.3%; padding-right:6px;">
                <div class="stat"><div class="label">Valor Bruto</div><div class="value">{{ $fmtMoeda($valorBruto) }}</div></div>
            </td>
            <td style="width:33.3%; padding:0 3px;">
                <div class="stat"><div class="label">Descontos ({{ number_format($percDescTot,2,',','.') }}%)</div><div class="value">{{ $fmtMoeda($totalDesc) }}</div></div>
            </td>
            <td style="width:33.3%; padding-left:6px;">
                <div class="stat"><div class="label">Total com Desconto</div><div class="value emph">{{ $fmtMoeda($valorTotal) }}</div></div>
            </td>
        </tr>
    </table>
</div>

{{-- Termos --}}
<div class="terms">
    <h3>Observações:</h3>
    <p>- Validade: 60 dias.</p>
    <p>- Prazo de entrega: 30 dias após o envio da autorização de fornecimento.</p>
    <p>- A editora LT terá a responsabilidade de promover cursos de qualificação para todos os professores e multiplicadores, com data, local e tempo determinados por este órgão, sem custo adicional.</p>
</div>
<div class="sign">
    <p>__________________________________________</p>
    <p class="muted">Assinatura / Carimbo</p>
</div>

</body>
</html>
