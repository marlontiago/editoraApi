<div class="mt-10">
    <h3 class="text-xl font-bold mb-4">Vendas Realizadas pelos Distribuidores</h3>

   

<a href="{{ route('gestor.relatorios.vendas') }}" class="text-blue-600 hover:underline">
    Relatório de Vendas
</a>

    <table class="w-full text-left border-collapse">
        <thead>
            <tr class="border-b bg-gray-100">
                <th class="p-2">Distribuidor</th>
                <th class="p-2">Data</th>
                <th class="p-2">Valor da Venda</th>
                <th class="p-2">Comissão (%)</th>
                <th class="p-2">Comissão (R$)</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($vendas as $venda)
                @php
                    $userId = $venda->distribuidor->user_id;
                    $percentual = $venda->distribuidor->user->commissions->last()->percentage ?? 0;
                    $valorComissao = ($percentual / 100) * $venda->valor_total;
                @endphp
                <tr class="border-b">
                    <td class="p-2">{{ $venda->distribuidor->user->name }}</td>
                    <td class="p-2">{{ \Carbon\Carbon::parse($venda->data)->format('d/m/Y') }}</td>
                    <td class="p-2">R$ {{ number_format($venda->valor_total, 2, ',', '.') }}</td>
                    <td class="p-2">{{ number_format($percentual, 2, ',', '.') }}%</td>
                    <td class="p-2">R$ {{ number_format($valorComissao, 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="p-4 text-center text-gray-500">Nenhuma venda registrada.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
