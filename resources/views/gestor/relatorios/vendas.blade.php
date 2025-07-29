<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            Relatório de Vendas
        </h2>
    </x-slot>

    <div class="p-6 space-y-6">

        <form method="GET" action="{{ route('gestor.relatorios.vendas') }}" class="mb-6">
            <label for="user_id" class="block mb-1 font-semibold">Filtrar por Distribuidor:</label>
            <select name="user_id" id="user_id" class="w-full p-2 border rounded">
                <option value="">-- Todos --</option>
                @foreach($distribuidores as $distribuidor)
                    <option value="{{ $distribuidor->user_id }}" {{ request('user_id') == $distribuidor->user_id ? 'selected' : '' }}>
                        {{ $distribuidor->user->name }}
                    </option>
                @endforeach
            </select>
            <button type="submit" class="mt-2 bg-blue-600 text-white px-4 py-2 rounded">Filtrar</button>
        </form>


        <form method="GET" action="{{ route('gestor.relatorios.vendas') }}" class="flex flex-wrap gap-4 items-end">
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

            <a href="{{ route('gestor.relatorios.vendas.export.excel', request()->all()) }}"
                class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                Exportar Excel
            </a>

            <a href="{{ route('gestor.relatorios.vendas.export.pdf', request()->all()) }}"
                class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
                Exportar PDF
            </a>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse mt-4">
                <thead>
                    <tr class="bg-gray-100 border-b">
                        <th class="p-2">Distribuidor</th>
                        <th class="p-2">Data</th>
                        <th class="p-2">Valor</th>
                        <th class="p-2">Comissão (%)</th>
                        <th class="p-2">Comissão (R$)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($vendas as $venda)
                        @php
                    $userId = $venda->distribuidor->user_id;
                    $percentual = optional(optional($comissoes[$userId] ?? null)->last())->percentage ?? 0;
                    $valorComissao = ($percentual / 100) * $venda->valor_total;
                @endphp
                        <tr class="border-b">
                            <td class="p-2">{{ $venda->distribuidor->user->name }}</td>
                            <td class="p-2">{{ \Carbon\Carbon::parse($venda->data)->format('d/m/Y') }}</td>
                            <td class="p-2">R$ {{ number_format($venda->valor_total, 2, ',', '.') }}</td>
                            <td class="p-2">{{ number_format($percentual, 2, ',', '.') }}</td>
                            <td class="p-2">R$ {{ number_format($valorComissao, 2, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-4 text-center text-gray-500">Nenhuma venda encontrada.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
