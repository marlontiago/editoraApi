<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Comissões por Distribuidor</h2>
    </x-slot>

    <div class="p-6">
        <div class="mb-6">
            <h3 class="text-lg font-bold text-green-700">
                Total acumulado: R$ {{ number_format($totalComissao, 2, ',', '.') }}
            </h3>
        </div>

        @forelse ($distribuidores as $distribuidor)
            <div class="mb-8 border border-gray-300 rounded-lg p-4 bg-white shadow-sm">
                <h4 class="text-lg font-semibold text-gray-800 mb-2">
                    {{ $distribuidor->nome_completo }} ({{ $distribuidor->user->email ?? 'sem e-mail' }})
                </h4>

                @if ($distribuidor->vendas->isEmpty())
                    <p class="text-sm text-gray-500">Nenhuma venda registrada.</p>
                @else
                    <table class="w-full text-left border mt-2">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="p-2 border">Produto</th>
                                <th class="p-2 border">Quantidade</th>
                                <th class="p-2 border">Valor Total</th>
                                <th class="p-2 border">Comissão</th>
                                <th class="p-2 border">Data</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($distribuidor->vendas as $venda)
                                <tr class="border-t">
                                    <td class="p-2 border">{{ $venda->produto->nome ?? 'Produto apagado' }}</td>
                                    <td class="p-2 border">{{ $venda->quantidade }}</td>
                                    <td class="p-2 border">R$ {{ number_format($venda->valor_total, 2, ',', '.') }}</td>
                                    <td class="p-2 border text-green-700 font-semibold">
                                        R$ {{ number_format($venda->comissao, 2, ',', '.') }}
                                    </td>
                                    <td class="p-2 border text-sm text-gray-500">{{ $venda->created_at->format('d/m/Y H:i') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        @empty
            <p class="text-gray-600">Nenhum distribuidor encontrado.</p>
        @endforelse
    </div>
</x-app-layout>
