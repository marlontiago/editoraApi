<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Pedidos</h2>
    </x-slot>

    <div class="p-6 space-y-6">
        @if(session('success'))
            <div class="bg-green-100 text-green-800 p-4 rounded">{{ session('success') }}</div>
        @endif

       {{-- Produtos com estoque baixo --}}
        @if($produtosComEstoqueBaixo->isNotEmpty())
            <div x-data="{ open: false }" class="bg-red-100 p-3 rounded shadow mb-4">
                <button @click="open = !open" class="font-semibold flex items-center gap-2 w-full text-left">
                    ⚠️ Produtos com estoque baixo 
                    <span class="ml-auto text-sm text-red-600">({{ $produtosComEstoqueBaixo->count() }} itens)</span>
                </button>
                <ul x-show="open" x-transition 
                    class="mt-2 list-disc pl-5 text-sm text-gray-700 space-y-1">
                    @foreach($produtosComEstoqueBaixo as $produto)
                        <li>
                            {{ $produto->titulo }} 
                            <span class="text-red-600 font-semibold">
                                - {{ $produto->quantidade_estoque }} em estoque
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Estoque em pedidos em potencial --}}
        @if($estoqueParaPedidosEmPotencial->isNotEmpty())
            <div x-data="{ open: false }" class="bg-yellow-100 p-3 rounded shadow">
                <button @click="open = !open" class="font-semibold flex items-center gap-2 w-full text-left">
                    ⚠️ Estoque em risco para pedidos futuros
                    <span class="ml-auto text-sm text-yellow-700">({{ $estoqueParaPedidosEmPotencial->count() }} itens)</span>
                </button>
                <ul x-show="open" x-transition 
                    class="mt-2 list-disc pl-5 text-sm text-gray-700 space-y-2">
                    @foreach($estoqueParaPedidosEmPotencial as $produto)
                        <li>
                            Produto: <strong>{{ $produto->titulo }}</strong> <br>
                            Em pedidos: <span class="font-medium text-green-700">{{ $produto->qtd_em_pedidos }}</span> <br>
                            Disponível: <span class="font-medium text-red-600">{{ $produto->quantidade_estoque }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
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
                                <a href="{{ route('admin.pedidos.show', $pedido) }}"
                                class="inline-flex items-center justify-center gap-1 rounded-md px-3 py-1.5 text-black hover:bg-black hover:text-white  border-gray-300"
                                title="Ver detalhes">
                                    <x-heroicon-o-magnifying-glass class="w-5 h-5" />
                                    <span class="hidden sm:inline text-sm font-medium"></span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="14" class="px-4 py-4 text-center text-gray-500">Nenhum pedido encontrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="mt-4">
                {{ $pedidos->withQueryString()->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
