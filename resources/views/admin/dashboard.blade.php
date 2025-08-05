<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard - Admin') }}
        </h2>
    </x-slot>  

    <div>
        <h2 class="text-bold text-xl">Relatório de vendas </h2>
        <table class="w-full text-left border-collapse mt-4 bg-white shadow rounded-lg">
            <thead>
    <tr class="border-b bg-gray-100">
        <th class="p-2">Gestor</th>
        <th class="p-2">Distribuidor</th>
        <th class="p-2">Data</th>
        <th class="p-2">Valor da Venda</th>
        <th class="p-2">Comissão Distribuidor (%)</th>
        <th class="p-2">Comissão Distribuidor (R$)</th>
        <th class="p-2">Comissão Gestor (%)</th>
        <th class="p-2">Comissão Gestor (R$)</th>
    </tr>
</thead>
<tbody>
    @forelse ($vendas as $venda)
        <tr class="border-b">
            <td class="p-2">{{ $venda->distribuidor->gestor->user->name ?? '-' }}</td>
            <td class="p-2">{{ $venda->distribuidor->user->name ?? '-' }}</td>
            <td class="p-2">{{ \Carbon\Carbon::parse($venda->data)->format('d/m/Y') }}</td>
            <td class="p-2">R$ {{ number_format($venda->valor_total, 2, ',', '.') }}</td>
            <td class="p-2">{{ number_format($venda->comissao_distribuidor, 2, ',', '.') }}%</td>
            <td class="p-2">R$ {{ number_format($venda->valor_comissao_distribuidor, 2, ',', '.') }}</td>
            <td class="p-2">{{ number_format($venda->comissao_gestor, 2, ',', '.') }}%</td>
            <td class="p-2">R$ {{ number_format($venda->valor_comissao_gestor, 2, ',', '.') }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="8" class="text-center p-2">Nenhuma venda encontrada</td>
        </tr>
    @endforelse
</tbody>

        </table>
    </div>
</x-app-layout>