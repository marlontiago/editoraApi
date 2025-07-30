<style>
    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12px;
    }

    th, td {
        border: 1px solid #000;
        padding: 8px;
        text-align: left;
    }

    thead {
        background-color: #f3f3f3;
    }

    h3 {
        margin-top: 20px;
    }
</style>


<table>
    <thead>
        <tr>
            <th>Data</th>
            <th>Produto</th>
            <th>Quantidade</th>
            <th>Preço Unitário</th>
            <th>Subtotal</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($vendas as $venda)
            @foreach ($venda->produtos as $produto)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($venda->data)->format('d/m/Y') }}</td>
                    <td>{{ $produto->nome }}</td>
                    <td>{{ $produto->pivot->quantidade }}</td>
                    <td>{{ number_format($produto->pivot->preco_unitario, 2, ',', '.') }}</td>
                    <td>{{ number_format($produto->pivot->quantidade * $produto->pivot->preco_unitario, 2, ',', '.') }}</td>
                </tr>
            @endforeach
        @endforeach
    </tbody>
</table>
