<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-gray-800">
            Detalhes do Pedido #{{ $pedido->id }}
        </h2>
    </x-slot>

    <div class="p-6 space-y-8 max-w-7xl mx-auto">

        {{-- Mensagem de erro --}}
        @if (session('error'))
            <div class="bg-red-50 text-red-800 border border-red-200 px-4 py-3 rounded-md shadow-sm">
                {{ session('error') }}
            </div>
        @endif

        {{-- Informações principais --}}
        <div class="bg-white p-6 rounded-lg shadow border border-gray-100">
            <h3 class="text-lg font-semibold mb-4 text-gray-700">Informações Gerais</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-700">
                <div><strong>Data:</strong> {{ \Carbon\Carbon::parse($pedido->data)->format('d/m/Y') }}</div>
                <div><strong>Status:</strong> {{ ucfirst(str_replace('_', ' ', $pedido->status)) }}</div>
                <div>
                    <strong>Cidades:</strong>
                    @forelse($pedido->cidades as $cidade)
                        <span class="inline-block bg-gray-100 text-gray-700 text-xs px-2 py-1 rounded-full mr-1 border border-gray-200">
                            {{ $cidade->name }}
                        </span>
                    @empty
                        <span class="text-gray-500">-</span>
                    @endforelse
                </div>
                <div><strong>Gestor:</strong> {{ $pedido->gestor->razao_social ?? '-' }}</div>
                <div><strong>Distribuidor:</strong> {{ $pedido->distribuidor->user->name ?? '-' }}</div>
            </div>
        </div>

        {{-- Produtos --}}
        <div class="bg-white p-6 rounded-lg shadow border border-gray-100">
            <h3 class="text-lg font-semibold mb-4 text-gray-700">Produtos do Pedido</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full border border-gray-200 rounded-lg overflow-hidden text-sm">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="px-4 py-2 text-left">Produto</th>
                            <th class="px-4 py-2 text-center">Qtd</th>
                            <th class="px-4 py-2 text-right">Preço Unit.</th>
                            <th class="px-4 py-2 text-center">Desc. (%)</th>
                            <th class="px-4 py-2 text-right">Subtotal</th>
                            <th class="px-4 py-2 text-right">Peso Total (kg)</th>
                            <th class="px-4 py-2 text-center">Caixas</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pedido->produtos as $produto)
                            <tr class="border-t">
                                <td class="px-4 py-2">{{ $produto->nome }}</td>
                                <td class="px-4 py-2 text-center">{{ $produto->pivot->quantidade }}</td>
                                <td class="px-4 py-2 text-right">R$ {{ number_format($produto->pivot->preco_unitario, 2, ',', '.') }}</td>
                                <td class="px-4 py-2 text-center">{{ $produto->pivot->desconto_aplicado }}%</td>
                                <td class="px-4 py-2 text-right">R$ {{ number_format($produto->pivot->subtotal, 2, ',', '.') }}</td>
                                <td class="px-4 py-2 text-right">{{ number_format($produto->pivot->peso_total_produto, 2, ',', '.') }}</td>
                                <td class="px-4 py-2 text-center">{{ $produto->pivot->caixas }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50 font-medium">
                        <tr>
                            <td colspan="4" class="px-4 py-2 text-right">Valor Bruto:</td>
                            <td class="px-4 py-2 text-right">R$ {{ number_format($pedido->valor_bruto, 2, ',', '.') }}</td>
                            <td colspan="2"></td>
                        </tr>
                        <tr>
                            <td colspan="4" class="px-4 py-2 text-right">Desconto:</td>
                            <td class="px-4 py-2 text-right">{{ $pedido->desconto }}%</td>
                            <td colspan="2"></td>
                        </tr>
                        <tr>
                            <td colspan="4" class="px-4 py-2 text-right">Valor com Desconto:</td>
                            <td class="px-4 py-2 text-right">R$ {{ number_format($pedido->valor_total, 2, ',', '.') }}</td>
                            <td colspan="2"></td>
                        </tr>
                        <tr>
                            <td colspan="4" class="px-4 py-2 text-right">Peso Total:</td>
                            <td class="px-4 py-2 text-right">{{ number_format($pedido->peso_total, 2, ',', '.') }} kg</td>
                            <td colspan="2"></td>
                        </tr>
                        <tr>
                            <td colspan="4" class="px-4 py-2 text-right">Total de Caixas:</td>
                            <td class="px-4 py-2 text-right">{{ $pedido->total_caixas }}</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- Linha do tempo --}}
        <div class="bg-white p-6 rounded-lg shadow border border-gray-100">
            <h3 class="text-lg font-semibold mb-4 text-gray-700">Linha do Tempo</h3>
            <ul class="space-y-4">
                @forelse($pedido->logs as $log)
                    <li class="relative pl-6">
                        <span class="absolute left-0 top-2 h-4 w-4 bg-blue-500 rounded-full"></span>
                        <div class="text-xs text-gray-500">{{ $log->created_at->format('d/m/Y H:i') }}</div>
                        <div class="font-medium">{{ $log->acao }}</div>
                        @if($log->detalhes)
                            <div class="text-sm text-gray-700">{{ $log->detalhes }}</div>
                        @endif
                        @if($log->user)
                            <div class="text-xs text-gray-400">Por: {{ $log->user->name }}</div>
                        @endif
                    </li>
                @empty
                    <li class="text-gray-500">Nenhum registro até o momento.</li>
                @endforelse
            </ul>
        </div>

        {{-- Ações --}}
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('admin.pedidos.index') }}"
               class="inline-flex items-center px-4 py-2 rounded-md border border-gray-300 text-gray-700 hover:bg-black hover:text-white">
                ← Voltar
            </a>

            <a href="{{ route('admin.pedidos.edit', $pedido) }}"
               class="inline-flex items-center px-4 py-2 rounded-md bg-yellow-500 text-white hover:bg-yellow-600">
                Editar
            </a>

            <a href="{{ route('admin.pedidos.exportar', ['pedido' => $pedido->id, 'tipo' => 'relatorio']) }}"
               class="inline-flex items-center px-4 py-2 rounded-md bg-gray-700 text-white hover:bg-gray-800">
                Exportar Relatório
            </a>

            <a href="{{ route('admin.pedidos.exportar', ['pedido' => $pedido->id, 'tipo' => 'orcamento']) }}"
               class="inline-flex items-center px-4 py-2 rounded-md bg-green-600 text-white hover:bg-green-700">
                Exportar Orçamento
            </a>
        </div>

    </div>
</x-app-layout>
