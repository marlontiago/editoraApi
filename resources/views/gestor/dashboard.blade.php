<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight">
            Relatório de Vendas dos Distribuidores
        </h2>
    </x-slot>

    <div class="mt-10 px-6">
        <h3 class="text-xl font-bold mb-4">Vendas Realizadas pelos Distribuidores</h3>

        <a href="{{ route('gestor.relatorios.vendas') }}" class="text-blue-600 hover:underline">
            Relatório de Vendas
        </a>

        <table class="w-full text-left border-collapse mt-4 bg-white shadow rounded-lg">
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
                        <td colspan="5" class="text-center p-2">Nenhuma venda encontrada</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-app-layout>
