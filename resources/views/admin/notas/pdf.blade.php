{{-- resources/views/admin/notas/pdf.blade.php --}}
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Nota Fiscal #{{ $nota->numero ?? $nota->id }}</title>
<link rel="stylesheet" href="{{ resource_path('css/pdf-nota.css') }}">
</head>
<body>
<div class="wrap">

    {{-- 1) Faixa de recibo --}}
    <div class="recibo tiny">
        Recebemos de {{ $nota->emitente_snapshot['razao_social'] ?? 'Minha Empresa LTDA' }}
        os produtos e/ou serviços constantes da Nota Fiscal indicada ao lado.
        Emitido em: {{ optional($nota->emitida_em)->format('d/m/Y') ?? now()->format('d/m/Y') }}
        &nbsp;&nbsp; Dest/Rem: {{ $nota->destinatario_snapshot['razao_social'] ?? $nota->pedido?->cliente?->razao_social ?? '-' }}
        &nbsp;&nbsp; Valor Total: R$ {{ number_format($nota->valor_total, 2, ',', '.') }}
        <div class="cells" style="margin-top:4px">
            <div class="cell">DATA DO RECEBIMENTO</div>
            <div class="cell">IDENTIFICAÇÃO E ASSINATURA DO RECEBEDOR</div>
        </div>
    </div>
    @php $E = config('empresa'); @endphp


    {{-- 2) Cabeçalho DANFE / NF-e --}}
    <div class="danfe">
        <div class="danfe-left">
            <div class="row">
                <div class="col" style="width:20%; text-align:center;">
                    @php $logo = public_path(config('empresa.logo')); @endphp
                    @if(file_exists($logo))
                        <img class="logo" src="{{ $logo }}" alt="Logo">
                    @else
                        <div class="logo" style="line-height:42px;">LOGO</div>
                    @endif
                </div>
                <div class="col" style="width:80%; padding-left:8px;">
                    <div class="b">EMPRESA EMITENTE</div>
                    {{ data_get($nota, 'emitente_snapshot.razao_social') ?: $E['razao_social'] }}<br>
                    CNPJ: {{ data_get($nota, 'emitente_snapshot.cnpj') ?: $E['cnpj'] }} &nbsp;&nbsp;
                    IE: {{ data_get($nota, 'emitente_snapshot.ie') ?: $E['ie'] }}<br>
                    {{ data_get($nota, 'emitente_snapshot.endereco') ?: $E['endereco'] }} —
                    {{ data_get($nota, 'emitente_snapshot.bairro')   ?: $E['bairro'] }} —
                    {{ data_get($nota, 'emitente_snapshot.municipio') ?: $E['municipio'] }}/{{ data_get($nota, 'emitente_snapshot.uf') ?: $E['uf'] }}<br>
                    CEP {{ data_get($nota, 'emitente_snapshot.cep') ?: $E['cep'] }}
                </div>
            </div>            
        </div>    
    </div>

    {{-- 4) Natureza da operação / datas --}}
    <div class="row" style="margin-bottom:6px;">
        <div class="col" style="width:60%;">
            <div class="box">
                <div class="tiny b">OPERAÇÃO</div>
                {{ $nota->pedido_snapshot['natureza_operacao'] ?? 'VENDA DE PRODUTOS' }}
            </div>
        </div>
        <div class="col" style="width:40%; padding-left:6px;">
            <div class="box">
                <div class="tiny b">DATA DE EMISSÃO</div>
                {{ optional($nota->emitida_em)->format('d/m/Y H:i') ?? now()->format('d/m/Y H:i') }}
            </div>
        </div>
    </div>

    {{-- 5) Destinatário/Remetente --}}
    <div class="box avoid-break">
        <h4>DESTINATÁRIO / REMETENTE</h4>
        <div class="row">
            <div class="col" style="width:60%;">
                <div class="mb4"><span class="b">Nome/Razão Social:</span>
                    {{ $nota->destinatario_snapshot['razao_social'] ?? $nota->pedido?->cliente?->razao_social ?? '-' }}</div>
                <div class="mb4"><span class="b">Endereço:</span>
                    {{ $nota->destinatario_snapshot['endereco'] ?? ($cliEndereco ?? '—') }}</div>
                <div class="mb4"><span class="b">Município/UF:</span>
                    {{ $nota->destinatario_snapshot['municipio'] ?? ($nota->pedido?->cliente?->cidade ?? '—') }}/
                    {{ $nota->destinatario_snapshot['uf'] ?? ($nota->pedido?->cliente?->uf ?? '—') }}</div>
            </div>
            <div class="col" style="width:40%; padding-left:8px;">
                <div class="mb4"><span class="b">CNPJ/CPF:</span>
                    {{ $nota->destinatario_snapshot['cnpj'] ?? $nota->destinatario_snapshot['cpf'] ?? $nota->pedido?->cliente?->cnpj ?? $nota->pedido?->cliente?->cpf ?? '-' }}</div>
                <div class="mb4"><span class="b">Inscrição Estadual:</span>
                    {{ $nota->destinatario_snapshot['inscr_estadual'] ?? $nota->pedido?->cliente?->inscr_estadual ?? '-' }}</div>
                <div class="mb4"><span class="b">Telefone/E-mail:</span>
                    {{ $nota->destinatario_snapshot['telefone'] ?? $nota->pedido?->cliente?->telefone ?? '-' }}
                    /
                    {{ $nota->destinatario_snapshot['email'] ?? $nota->pedido?->cliente?->email ?? '-' }}
                </div>
            </div>
        </div>
    </div>

    {{-- 6) Itens --}}
    <div class="avoid-break">
        <table class="items">
            <thead>
                <tr>
                    <th>Cód.</th>
                    <th>Descrição do Produto/Serviço</th>
                    <th class="right">Qtd</th>
                    <th class="right">Vlr Unit.</th>
                    <th class="right">Desc. %</th>
                    <th class="right">Subtotal R$</th>
                    <th class="right">Peso (kg)</th>
                    <th class="center">Caixas</th>
                </tr>
            </thead>
            <tbody>
                @foreach($nota->itens as $item)
                    <tr>
                        <td>{{ $item->produto_id }}</td>
                        <td>{{ $item->descricao_produto ?? $item->produto?->nome }} <br> ISBN: {{ $item->produto?->isbn }}</td>
                        <td class="right">{{ number_format($item->quantidade, 0, ',', '.') }}</td>
                        <td class="right">{{ number_format($item->preco_unitario, 2, ',', '.') }}</td>
                        <td class="right">{{ number_format($item->desconto_aplicado, 2, ',', '.') }}</td>
                        <td class="right">{{ number_format($item->subtotal, 2, ',', '.') }}</td>
                        <td class="right">{{ number_format($item->peso_total_produto, 3, ',', '.') }}</td>
                        <td class="center">{{ $item->caixas }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- 7) Totais / "Cálculo do imposto" (placeholders) --}}
    <div class="totais" style="margin-top:6px;">
        <div class="tcell" style="padding-right:6px;">
            <table class="grid" width="100%">
                <tr><th colspan="2">CÁLCULO DO IMPOSTO (Uso interno)</th></tr>
                <tr><td>Base de Cálculo ICMS</td><td class="right">R$ 0,00</td></tr>
                <tr><td>Valor do ICMS</td><td class="right">R$ 0,00</td></tr>
                <tr><td>Base de Cálculo ICMS ST</td><td class="right">R$ 0,00</td></tr>
                <tr><td>Valor do ICMS ST</td><td class="right">R$ 0,00</td></tr>
                <tr><td>Valor do IPI</td><td class="right">R$ 0,00</td></tr>
            </table>
        </div>
        <div class="tcell" style="padding-right:6px;">
            <table class="grid" width="100%">
                <tr><th colspan="2">TRANSPORTADOR / VOLUMES (opcional)</th></tr>
                <tr><td>Frete por conta</td><td>—</td></tr>
                <tr><td>Placa/UF</td><td>—</td></tr>
                <tr><td>Quantidade/Volumes</td><td>{{ $nota->total_caixas }}</td></tr>
                <tr><td>Peso Bruto</td><td>{{ number_format($nota->peso_total, 3, ',', '.') }} kg</td></tr>
            </table>
        </div>
        <div class="tcell">
            <table class="grid" width="100%">
                <tr><th colspan="2">VALORES TOTAIS DA NOTA</th></tr>
                <tr><td>Valor dos Produtos</td><td class="right">R$ {{ number_format($nota->valor_bruto, 2, ',', '.') }}</td></tr>
                <tr><td>Desconto</td><td class="right">R$ {{ number_format($nota->desconto_total, 2, ',', '.') }}</td></tr>
                <tr><td>Frete</td><td class="right">R$ 0,00</td></tr>
                <tr><td>Outras Despesas</td><td class="right">R$ 0,00</td></tr>
                <tr><td><b>Valor Total da Nota</b></td><td class="right"><b>R$ {{ number_format($nota->valor_total, 2, ',', '.') }}</b></td></tr>
            </table>
        </div>
    </div>

    {{-- 8) Observações --}}
    <div class="box" style="margin-top:6px;">
        <h4>DADOS ADICIONAIS / OBSERVAÇÕES</h4>
        <div class="tiny">
            @php
                $obs = $nota->observacoes
                    ?? ($nota->pedido_snapshot['observacoes'] ?? null)
                    ?? ($nota->pedido?->observacoes ?? null);
            @endphp
            {{ $obs ?: '—' }}
        </div>
    </div>

</div>

<div class="footer">
    Documento interno ({{ strtoupper($nota->ambiente ?? 'INTERNO') }}) — gerado em {{ now()->format('d/m/Y H:i') }} — Sistema Editora
</div>
</body>
</html>
