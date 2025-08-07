<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Orçamento</title>
    <style>
        @page { margin: 110px 40px 90px 40px; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; line-height: 1.35; }

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
    </style>
</head>
<body>
    {{-- Cabeçalho --}}
    <div class="header">
        <table style="width:100%; border:0; border-collapse:separate;">
            <tr>
                <td style="border:0;">
                    <div class="brand">
                        @php
                            $logoPath = public_path('images/logo.jpeg');
                            $logoBase64 = '';
                            if (file_exists($logoPath)) {
                                $logoBase64 = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($logoPath));
                            }
                        @endphp
                        @if($logoBase64)
                            <img src="{{ $logoBase64 }}" alt="Logo" style="height:60px;">
                        @endif
                        <h1>Orçamento Comercial #{{ $pedido->id }}</h1>
                    </div>
                </td>
                <td style="text-align:right; border:0;">
                    <div style="font-size:12px;">
                        Data: {{ \Carbon\Carbon::parse($pedido->data)->format('d/m/Y') }}<br>
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
                <td style="text-align:right; border:0;">Página <span class="page-number"></span></td>
            </tr>
        </table>
    </div>

    @php
        /**
         * Gera um src base64 para a imagem (evita problema de caminho no Dompdf).
         */
        function img_src_or_placeholder($pathRelative) {
            $full = $pathRelative
                ? (str_starts_with($pathRelative, 'storage/')
                    ? public_path($pathRelative)                 
                    : public_path('storage/' . ltrim($pathRelative, '/'))) 
                : public_path('images/placeholder.png');

            if (!file_exists($full)) {
                $full = public_path('images/placeholder.png');
            }

            $ext = pathinfo($full, PATHINFO_EXTENSION) ?: 'png';
            $data = @file_get_contents($full);
            return $data ? ('data:image/'.$ext.';base64,'.base64_encode($data)) : '';
        }

        // Helpers e totais
        $fmtMoeda = fn($v) => 'R$ ' . number_format((float)$v, 2, ',', '.');

        $valorBruto    = (float) $pedido->valor_bruto;    // salvo no banco
        $valorComDesc  = (float) $pedido->valor_total;    // salvo no banco
        $percDesc      = (float) $pedido->desconto;       // %
        $valorDescReal = max($valorBruto - $valorComDesc, 0); // R$ de desconto
    @endphp

    {{-- Conteúdo --}}
    <div style="margin-top: 10px;">
        <p><strong>Distribuidor:</strong> {{ $pedido->distribuidor->user->name ?? '-' }}</p>
        <p><strong>Cidades:</strong>
            @foreach ($pedido->cidades as $c)
                {{ $c->name }}@if(!$loop->last), @endif
            @endforeach
        </p>
    </div>

    <table style="margin-top: 8px;">
        <thead>
            <tr>
                <th style="width:70px;">Imagem</th>
                <th>Produto</th>
                <th style="width:60px;">Qtd</th>
                <th style="width:90px;" class="right">Preço Unit.</th>
                <th style="width:100px;" class="right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
        @foreach($pedido->produtos as $produto)
            @php
                // Campo imagem
                $campoImagem = $produto->imagem ?? null;

                $src = img_src_or_placeholder($campoImagem);

                // Preço unitário com desconto do pedido
                $precoUnitComDesc = (float) $produto->pivot->preco_unitario * (1 - ($percDesc / 100));
            @endphp
            <tr>
                <td style="text-align:center;">
                    @if($src)
                        <img src="{{ $src }}" style="max-height:60px; max-width:60px;">
                    @endif
                </td>
                <td>
                    <strong>{{ $produto->nome }}</strong><br>
                    @if(!empty($produto->isbn) || !empty($produto->autor))
                        <span style="font-size:11px; color:#666;">
                            @if(!empty($produto->isbn)) ISBN: {{ $produto->isbn }} @endif
                            @if(!empty($produto->autor)) • Autor: {{ $produto->autor }} @endif
                        </span>
                    @endif
                </td>
                <td class="right">{{ $produto->pivot->quantidade }}</td>
                <td class="right">{{ $fmtMoeda($precoUnitComDesc) }}</td>
                <td class="right">{{ $fmtMoeda($produto->pivot->subtotal) }}</td>
            </tr>
        @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" class="right" style="font-weight:bold;">Valor Bruto</td>
                <td class="right" style="font-weight:bold;">{{ $fmtMoeda($valorBruto) }}</td>
            </tr>
            <tr>
                <td colspan="4" class="right">Desconto ({{ number_format($percDesc, 0, ',', '.') }}%)</td>
                <td class="right">{{ $fmtMoeda($valorDescReal) }}</td>
            </tr>
            <tr>
                <td colspan="4" class="right" style="font-weight:bold;">Total com Desconto</td>
                <td class="right" style="font-weight:bold;">{{ $fmtMoeda($valorComDesc) }}</td>
            </tr>
        </tfoot>
    </table>

    <div style="margin-top: 28px; font-size: 11px;">
        * Este orçamento é válido por 15 dias. Valores sujeitos a alteração sem aviso prévio.
    </div>

    <div style="margin-top: 48px;">
        <p>__________________________________________</p>
        <p>Assinatura / Carimbo</p>
    </div>
</body>
</html>
