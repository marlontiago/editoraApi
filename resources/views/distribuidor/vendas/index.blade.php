<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">
            Minhas Vendas
        </h2>
    </x-slot>

    <form method="GET" class="flex flex-wrap gap-4 items-end mb-6">
    <div>
        <label for="periodo" class="block text-sm font-medium text-gray-700">Período</label>
        <select name="periodo" id="periodo" class="form-select mt-1 block w-full">
            <option value="">Selecione</option>
            <option value="semana" {{ request('periodo') === 'semana' ? 'selected' : '' }}>Semana Atual</option>
            <option value="mes" {{ request('periodo') === 'mes' ? 'selected' : '' }}>Mês Atual</option>
        </select>
    </div>

    <div>
        <label for="inicio" class="block text-sm font-medium text-gray-700">Data Início</label>
        <input type="date" name="inicio" value="{{ request('inicio') }}" class="form-input mt-1 block w-full">
    </div>

    <div>
        <label for="fim" class="block text-sm font-medium text-gray-700">Data Fim</label>
        <input type="date" name="fim" value="{{ request('fim') }}" class="form-input mt-1 block w-full">
    </div>

    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
        Filtrar
    </button>

    <a href="{{ route('distribuidor.vendas.export.excel', request()->all()) }}"
       class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
        Exportar Excel
    </a>

    <a href="{{ route('distribuidor.vendas.export.pdf', request()->all()) }}"
       class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
        Exportar PDF
    </a>
</form>


    <div class="p-6 space-y-6">
        @foreach ($vendas as $venda)
            <div class="border rounded-lg p-4 shadow bg-white">
                <div class="mb-2 font-semibold">
                    Venda #{{ $venda->id }} - {{ \Carbon\Carbon::parse($venda->data)->format('d/m/Y') }}
                </div>

                <div class="mb-2 text-sm">
                    <strong>Valor Total:</strong> R$ {{ number_format($venda->valor_total, 2, ',', '.') }}<br>
                    <strong>Comissão (%):</strong> {{ number_format($venda->commission_percentage_snapshot ?? 0, 2, ',', '.') }}%<br>
                    <strong>Comissão (R$):</strong> R$ {{ number_format($venda->commission_value_snapshot ?? 0, 2, ',', '.') }}
                </div>

                <table class="w-full mt-2 text-sm">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="p-2 text-left">Produto</th>
                            <th class="p-2 text-right">Qtd</th>
                            <th class="p-2 text-right">Preço Unitário</th>
                            <th class="p-2 text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($venda->produtos as $produto)
                            <tr class="border-t">
                                <td class="p-2">{{ $produto->nome }}</td>
                                <td class="p-2 text-right">{{ $produto->pivot->quantidade }}</td>
                                <td class="p-2 text-right">R$ {{ number_format($produto->pivot->preco_unitario, 2, ',', '.') }}</td>
                                <td class="p-2 text-right">
                                    R$ {{ number_format($produto->pivot->quantidade * $produto->pivot->preco_unitario, 2, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach

        <div>
            {{ $vendas->links() }}
        </div>
    </div>
</x-app-layout>
