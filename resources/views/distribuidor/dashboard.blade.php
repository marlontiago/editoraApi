<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Painel do Distribuidor</h2>
    </x-slot>

    <div class="p-6 space-y-6">

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-blue-100 p-4 rounded shadow">
                <p class="text-sm text-gray-600">Total de Vendas</p>
                <p class="text-xl font-bold">R$ {{ number_format($totalVendas, 2, ',', '.') }}</p>
            </div>
            
            <div class="bg-green-100 p-4 rounded shadow">
                <p class="text-sm text-gray-600">Comissão Recebida</p>
                
            </div>
            <div class="bg-yellow-100 p-4 rounded shadow">
                <p class="text-sm text-gray-600">Percentual Atual</p>
               
            </div>

            <div class="flex justify-start mb-4">
    <a href="{{ route('distribuidor.vendas.index') }}" 
       class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded">
        Ver Vendas
    </a>
    <a href="{{ route('distribuidor.vendas.create') }}" 
   class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded ml-2">
    Nova Venda
</a>

</div>
        </div>

        <h3 class="text-lg font-semibold mt-8">Últimas Vendas</h3>

        <form method="GET" action="{{ route('distribuidor.dashboard') }}" class="flex flex-wrap items-end gap-4 mb-4">
            <div>
                <label for="periodo" class="block text-sm font-medium text-gray-700">Período</label>
                <select name="periodo" id="periodo" class="form-select mt-1 block w-full">
                    <option value="">Todos</option>
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

            <a href="{{ route('distribuidor.vendas.export.excel', request()->query()) }}"
               class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                Exportar Excel
            </a>

            <a href="{{ route('distribuidor.vendas.export.pdf', request()->query()) }}"
               class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
                Exportar PDF
            </a>

            <a href="{{ route('distribuidor.vendas.index', request()->query()) }}"
               class="px-4 py-2 bg-black text-white rounded hover:bg-gray-800">
                Ver Relatório Completo
            </a>
        </form>

        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border">
                <thead class="bg-gray-200">
                    <tr>
                        <th class="p-2 border">Data</th>
                        <th class="p-2 border">Produtos</th>
                        <th class="p-2 border">Valor</th>
                        <th class="p-2 border">Comissão</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($vendas as $venda)
                        <tr class="border-t">
                            <td class="p-2">{{ \Carbon\Carbon::parse($venda->data)->format('d/m/Y') }}</td>
                            <td class="p-2">
                                @foreach ($venda->produtos as $produto)
                                    {{ $produto->nome }} (x{{ $produto->pivot->quantidade }})<br>
                                @endforeach
                            </td>
                            <td class="p-2">R$ {{ number_format($venda->valor_total, 2, ',', '.') }}</td>
                            <td class="p-2">
                                R$ {{ number_format(($percentual / 100) * $venda->valor_total, 2, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="p-4 text-center text-gray-500">Nenhuma venda encontrada.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
