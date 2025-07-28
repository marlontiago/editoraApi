<table border="1" cellpadding="6" cellspacing="0" width="100%">
    <thead>
        <tr>
            <th>Distribuidor</th>
            <th>Data</th>
            <th>Valor</th>
            <th>Comissão (%)</th>
            <th>Comissão (R$)</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($vendas as $venda)
            @php
                $userId = $venda->distribuidor->user_id;
                $percentual = optional(optional($comissoes[$userId] ?? null)->last())->percentage ?? 0;
                $valorComissao = ($percentual / 100) * $venda->valor_total;
            @endphp
            <tr>
                <td>{{ $venda->distribuidor->user->name }}</td>
                <td>{{ \Carbon\Carbon::parse($venda->data)->format('d/m/Y') }}</td>
                <td>{{ number_format($venda->valor_total, 2, ',', '.') }}</td>
                <td>{{ number_format($percentual, 2, ',', '.') }}</td>
                <td>{{ number_format($valorComissao, 2, ',', '.') }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
