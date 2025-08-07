<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Detalhes do Pedido #{{ $pedido->id }}</h2>
    </x-slot>

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
                        <td colspan="4" class="px-4 py-2 text-right">Totais:</td>
                        <td class="px-4 py-2">R$ {{ number_format($pedido->valor_total, 2, ',', '.') }}</td>
                        <td class="px-4 py-2">{{ number_format($pedido->peso_total, 2, ',', '.') }} kg</td>
                        <td class="px-4 py-2">{{ $pedido->total_caixas }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="mt-6">
            <a href="{{ route('admin.pedidos.index') }}" class="text-blue-600 hover:underline">← Voltar para a lista</a>
        </div>
    </div>
</x-app-layout>
