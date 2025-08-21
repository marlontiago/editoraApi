<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Pedidos</h2>
    </x-slot>

    <div class="p-6 space-y-6">
        @if(session('success'))
            <div class="bg-green-100 text-green-800 p-4 rounded">{{ session('success') }}</div>
        @endif

        <div class="mt-4">
            <a href="{{ route('admin.pedidos.create') }}"
               class="inline-block bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                + Novo Pedido
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full bg-white shadow rounded">
                <thead class="bg-gray-100 text-left">
                    <tr>
                        <th class="px-4 py-2">ID</th>
                        <th class="px-4 py-2">Data</th>
                        <th class="px-4 py-2">Cliente</th>
                        <th class="px-4 py-2">Cidade</th>
                        <th class="px-4 py-2">Gestor</th>
                        <th class="px-4 py-2">Distribuidor</th>
                        <th class="px-4 py-2">Valor Bruto</th>
                        <th class="px-4 py-2">Descontos (R$)</th>
                        <th class="px-4 py-2">Desconto (%)</th>
                        <th class="px-4 py-2">Valor Total</th>
                        <th class="px-4 py-2">Caixas</th>
                        <th class="px-4 py-2">Peso (kg)</th>
                        <th class="px-4 py-2">Status</th>
                        <th class="px-4 py-2">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pedidos as $pedido)
                        @php
                            $bruto = (float)($pedido->valor_bruto ?? 0);
                            $total = (float)($pedido->valor_total ?? 0);
                            $descR = max($bruto - $total, 0);
                            $descP = $bruto > 0 ? (100 * $descR / $bruto) : 0;
                        @endphp
                        <tr class="border-b">
                            <td class="px-4 py-2">#{{ $pedido->id }}</td>
                            <td class="px-4 py-2">{{ \Carbon\Carbon::parse($pedido->data)->format('d/m/Y') }}</td>
                            <td class="px-4 py-2">
                                {{ $pedido->cliente->razao_social ?? $pedido->cliente->nome ?? '-' }}
                            </td>
                            <td class="px-4 py-2">
                                @forelse ($pedido->cidades as $cidade)
                                    <span class="inline-block bg-gray-200 text-sm text-gray-700 px-2 py-1 rounded mr-1">
                                        {{ $cidade->name }}
                                    </span>
                                @empty
                                    <span class="text-gray-500">-</span>
                                @endforelse
                            </td>
                            <td class="px-4 py-2">{{ $pedido->gestor->razao_social ?? '-' }}</td>
                            <td class="px-4 py-2">{{ $pedido->distribuidor->user->name ?? '-' }}</td>
                            <td class="px-4 py-2">
                                R$ {{ number_format($bruto, 2, ',', '.') }}
                            </td>
                            <td class="px-4 py-2">
                                R$ {{ number_format($descR, 2, ',', '.') }}
                            </td>
                            <td class="px-4 py-2">
                                {{ number_format($descP, 2, ',', '.') }}%
                            </td>
                            <td class="px-4 py-2">
                                R$ {{ number_format($total, 2, ',', '.') }}
                            </td>
                            <td class="px-4 py-2">{{ $pedido->total_caixas }}</td>
                            <td class="px-4 py-2">{{ number_format($pedido->peso_total, 2, ',', '.') }}</td>
                            <td class="px-4 py-2 capitalize">{{ str_replace('_', ' ', $pedido->status) }}</td>
                            <td class="px-4 py-2">
                                <a href="{{ route('admin.pedidos.show', $pedido) }}" class="text-blue-600 hover:underline">Ver</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="14" class="px-4 py-4 text-center text-gray-500">Nenhum pedido encontrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
