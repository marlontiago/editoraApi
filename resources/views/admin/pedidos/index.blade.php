<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold text-gray-800 leading-tight">Pedidos</h2>
                <p class="text-sm text-gray-500 mt-1">Acompanhe pedidos, status e totais rapidamente.</p>
            </div>

            <a
                href="{{ route('admin.pedidos.create') }}"
                class="inline-flex items-center gap-2 rounded-lg bg-gray-900 px-4 h-10 text-sm font-semibold text-white hover:bg-gray-800 transition"
            >
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14M5 12h14"/>
                </svg>
                Criar pedido
            </a>
        </div>
    </x-slot>

    <div class="max-w-full mx-auto p-6 space-y-4">

        {{-- Flash --}}
        @if(session('success'))
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-800">
                {{ session('success') }}
            </div>
        @endif

        {{-- Produtos com estoque baixo --}}
        @if($produtosComEstoqueBaixo->isNotEmpty())
            <div x-data="{ open: false }" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3">
                <button @click="open = !open" class="w-full text-left flex items-center gap-2">
                    <span class="text-sm font-semibold text-red-800">⚠️ Produtos com estoque baixo</span>
                    <span class="ml-auto text-xs font-medium text-red-700 rounded-full bg-red-100 px-2 py-0.5">
                        {{ $produtosComEstoqueBaixo->count() }} itens
                    </span>
                </button>
                <ul x-show="open" x-transition class="mt-2 list-disc pl-5 text-sm text-gray-700 space-y-1">
                    @foreach($produtosComEstoqueBaixo as $produto)
                        <li>
                            {{ $produto->titulo }}
                            <span class="text-red-700 font-semibold">
                                - {{ $produto->quantidade_estoque }} em estoque
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Estoque em pedidos em potencial --}}
        @if($estoqueParaPedidosEmPotencial->isNotEmpty())
            <div x-data="{ open: false }" class="rounded-xl border border-yellow-200 bg-yellow-50 px-4 py-3">
                <button @click="open = !open" class="w-full text-left flex items-center gap-2">
                    <span class="text-sm font-semibold text-yellow-900">⚠️ Estoque em risco para pedidos futuros</span>
                    <span class="ml-auto text-xs font-medium text-yellow-800 rounded-full bg-yellow-100 px-2 py-0.5">
                        {{ $estoqueParaPedidosEmPotencial->count() }} itens
                    </span>
                </button>
                <ul x-show="open" x-transition class="mt-2 list-disc pl-5 text-sm text-gray-700 space-y-2">
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

        {{-- Tabela --}}
        <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-100 overflow-x-auto">
            <table class="min-w-[1200px] w-full text-sm">
                <thead class="bg-gray-50 text-gray-600 text-xs uppercase tracking-wide">
                    <tr class="border-b border-gray-100">
                        <th class="px-4 py-3 whitespace-nowrap">ID</th>
                        <th class="px-4 py-3 whitespace-nowrap">Data</th>
                        <th class="px-4 py-3 whitespace-nowrap">Cliente</th>
                        <th class="px-4 py-3 whitespace-nowrap">Cidade</th>
                        <th class="px-4 py-3 whitespace-nowrap">Gestor</th>
                        <th class="px-4 py-3 whitespace-nowrap">Distribuidor</th>
                        <th class="px-4 py-3 whitespace-nowrap">Valor Bruto</th>
                        <th class="px-4 py-3 whitespace-nowrap">Descontos (R$)</th>
                        <th class="px-4 py-3 whitespace-nowrap">Desconto (%)</th>
                        <th class="px-4 py-3 whitespace-nowrap">Valor Total</th>
                        <th class="px-4 py-3 whitespace-nowrap">Caixas</th>
                        <th class="px-4 py-3 whitespace-nowrap">Peso (kg)</th>
                        <th class="px-4 py-3 whitespace-nowrap">Status</th>
                        <th class="px-4 py-3 whitespace-nowrap text-right">Ações</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100">
                    @forelse ($pedidos as $pedido)
                        @php
                            $bruto = (float)($pedido->valor_bruto ?? 0);
                            $total = (float)($pedido->valor_total ?? 0);
                            $descR = max($bruto - $total, 0);
                            $descP = $bruto > 0 ? (100 * $descR / $bruto) : 0;

                            $statusLabel = ucfirst(str_replace('_', ' ', (string)$pedido->status));
                            $statusKey   = (string)$pedido->status;

                            $statusClasses = match ($statusKey) {
                                'finalizado'     => 'bg-green-50 text-green-700 ring-1 ring-green-200',
                                'cancelado'      => 'bg-red-50 text-red-700 ring-1 ring-red-200',
                                'pre_aprovado'   => 'bg-blue-50 text-blue-700 ring-1 ring-blue-200',
                                'em_andamento'   => 'bg-yellow-50 text-yellow-800 ring-1 ring-yellow-200',
                                default          => 'bg-gray-50 text-gray-700 ring-1 ring-gray-200',
                            };
                        @endphp

                        <tr class="hover:bg-gray-50/60">
                            <td class="px-4 py-3 whitespace-nowrap font-medium text-gray-900">#{{ $pedido->id }}</td>

                            <td class="px-4 py-3 whitespace-nowrap text-gray-700">
                                {{ \Carbon\Carbon::parse($pedido->data)->format('d/m/Y') }}
                            </td>

                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900">
                                    {{ $pedido->cliente->razao_social ?? $pedido->cliente->nome ?? '-' }}
                                </div>
                            </td>

                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1.5">
                                    @forelse ($pedido->cidades as $cidade)
                                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-700 ring-1 ring-gray-200">
                                            {{ $cidade->name }}
                                        </span>
                                    @empty
                                        <span class="text-gray-400">—</span>
                                    @endforelse
                                </div>
                            </td>

                            <td class="px-4 py-3">
                                <span class="text-gray-700">
                                    {{ $pedido->gestor->razao_social ?? '-' }}
                                </span>
                            </td>

                            <td class="px-4 py-3">
                                <span class="text-gray-700">
                                    {{ $pedido->distribuidor->user->name ?? '-' }}
                                </span>
                            </td>

                            <td class="px-4 py-3 whitespace-nowrap font-semibold text-gray-900">
                                R$ {{ number_format($bruto, 2, ',', '.') }}
                            </td>

                            <td class="px-4 py-3 whitespace-nowrap text-gray-700">
                                R$ {{ number_format($descR, 2, ',', '.') }}
                            </td>

                            <td class="px-4 py-3 whitespace-nowrap text-gray-700">
                                {{ number_format($descP, 2, ',', '.') }}%
                            </td>

                            <td class="px-4 py-3 whitespace-nowrap font-semibold text-gray-900">
                                R$ {{ number_format($total, 2, ',', '.') }}
                            </td>

                            <td class="px-4 py-3 whitespace-nowrap text-gray-700">
                                {{ $pedido->total_caixas }}
                            </td>

                            <td class="px-4 py-3 whitespace-nowrap text-gray-700">
                                {{ number_format($pedido->peso_total, 2, ',', '.') }}
                            </td>

                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $statusClasses }}">
                                    {{ $statusLabel }}
                                </span>
                            </td>

                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="flex items-center justify-end">
                                    <a
                                        href="{{ route('admin.pedidos.show', $pedido) }}"
                                        class="inline-flex items-center justify-center rounded-md border border-blue-200 p-2 text-blue-700 hover:bg-blue-50 transition"
                                        title="Ver detalhes do pedido"
                                    >
                                        <x-heroicon-o-eye class="w-5 h-5" />
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="14" class="px-4 py-10 text-center text-sm text-gray-500">
                                Nenhum pedido encontrado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Paginação --}}
        <div class="flex justify-end">
            {{ $pedidos->withQueryString()->links() }}
        </div>
    </div>
</x-app-layout>
