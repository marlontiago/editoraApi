<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Detalhes do Pedido #{{ $pedido->id }}</h2>
    </x-slot>

    @if (session('error'))
        <div class="bg-red-100 text-red-800 p-3 rounded mb-4">
            {{ session('error') }}
        </div>
    @endif

    <div class="p-6 space-y-6">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-white p-4 rounded shadow">
            <div><strong>Data:</strong> {{ \Carbon\Carbon::parse($pedido->data)->format('d/m/Y') }}</div>
            <div><strong>Status:</strong> {{ ucfirst(str_replace('_', ' ', $pedido->status)) }}</div>
            <div>
    <strong>Cidades:</strong>
    @forelse($pedido->cidades as $cidade)
        <span class="inline-block bg-gray-200 text-sm text-gray-700 px-2 py-1 rounded mr-1">
            {{ $cidade->name }}
        </span>
    @empty
        <span class="text-gray-500">-</span>
    @endforelse
</div>
            <div><strong>Gestor:</strong> {{ $pedido->gestor->razao_social ?? '-' }}</div>
            <div><strong>Distribuidor:</strong> {{ $pedido->distribuidor->user->name ?? '-' }}</div>
        </div>

        <div class="bg-white p-4 rounded shadow overflow-x-auto">
            <h3 class="text-lg font-semibold mb-4">Produtos do Pedido</h3>

            <table class="min-w-full text-left border-collapse">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-2">Produto</th>
                        <th class="px-4 py-2">Qtd</th>
                        <th class="px-4 py-2">Preço Unit.</th>
                        <th class="px-4 py-2">Desc. (%)</th>
                        <th class="px-4 py-2">Subtotal</th>
                        <th class="px-4 py-2">Peso Total (kg)</th>
                        <th class="px-4 py-2">Caixas</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($pedido->produtos as $produto)
                        <tr class="border-b">
                            <td class="px-4 py-2">{{ $produto->nome }}</td>
                            <td class="px-4 py-2">{{ $produto->pivot->quantidade }}</td>
                            <td class="px-4 py-2">R$ {{ number_format($produto->pivot->preco_unitario, 2, ',', '.') }}</td>
                            <td class="px-4 py-2">{{ $produto->pivot->desconto_aplicado }}%</td>
                            <td class="px-4 py-2">R$ {{ number_format($produto->pivot->subtotal, 2, ',', '.') }}</td>
                            <td class="px-4 py-2">{{ number_format($produto->pivot->peso_total_produto, 2, ',', '.') }}</td>
                            <td class="px-4 py-2">{{ $produto->pivot->caixas }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50 font-semibold">
                    <tr>
                        <td colspan="4" class="px-4 py-2 text-right">Valor Bruto:</td>
                        <td class="px-4 py-2">R$ {{ number_format($pedido->valor_bruto, 2, ',', '.') }}</td>
                        <td colspan="2"></td>
                    </tr>
                    <tr>
                        <td colspan="4" class="px-4 py-2 text-right">Desconto:</td>
                        <td class="px-4 py-2">{{ $pedido->desconto }}%</td>
                        <td colspan="2"></td>
                    </tr>
                    <tr>
                        <td colspan="4" class="px-4 py-2 text-right">Valor com Desconto:</td>
                        <td class="px-4 py-2">R$ {{ number_format($pedido->valor_total, 2, ',', '.') }}</td>
                        <td colspan="2"></td>
                    </tr>
                    <tr>
                        <td colspan="4" class="px-4 py-2 text-right">Peso Total:</td>
                        <td class="px-4 py-2">{{ number_format($pedido->peso_total, 2, ',', '.') }} kg</td>
                        <td colspan="2"></td>
                    </tr>
                    <tr>
                        <td colspan="4" class="px-4 py-2 text-right">Total de Caixas:</td>
                        <td class="px-4 py-2">{{ $pedido->total_caixas }}</td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="bg-white p-4 rounded shadow mt-8">
    <h3 class="text-lg font-semibold mb-4">Linha do Tempo</h3>
    <ul class="space-y-4">
        @forelse($pedido->logs as $log)
            <li class="border-l-4 border-blue-600 pl-4">
                <div class="text-sm text-gray-600">{{ $log->created_at->format('d/m/Y H:i') }}</div>
                <div class="font-semibold">{{ $log->acao }}</div>
                @if($log->detalhes)
                    <div class="text-sm text-gray-700">{{ $log->detalhes }}</div>
                @endif
                @if($log->user)
                    <div class="text-xs text-gray-500">Por: {{ $log->user->name }}</div>
                @endif
            </li>
        @empty
            <li class="text-gray-500">Nenhum registro até o momento.</li>
        @endforelse
    </ul>
</div>

        <div class="mt-6">
            <a href="{{ route('admin.pedidos.index') }}" class="text-blue-600 hover:underline">← Voltar para a lista</a>
        </div>

        <div class="mt-6 flex gap-4">
            <a href="{{ route('admin.pedidos.edit', $pedido) }}"
                class="bg-black text-white px-4 py-2 rounded hover:bg-yellow-600">
                Editar
            </a>

            <a href="{{ route('admin.pedidos.exportar', ['pedido' => $pedido->id, 'tipo' => 'relatorio']) }}"
            class="bg-black text-white px-4 py-2 rounded hover:bg-gray-200">Exportar Relatório</a>

            <a href="{{ route('admin.pedidos.exportar', ['pedido' => $pedido->id, 'tipo' => 'orcamento']) }}"
            class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Exportar Orçamento</a>
        </div>
    </div>
</x-app-layout>
