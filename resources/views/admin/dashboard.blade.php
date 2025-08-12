<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard - Admin</h2>
    </x-slot>

    {{-- Filtros --}}
    <form method="GET" action="{{ route('admin.dashboard') }}"
          class="bg-white shadow rounded p-4 grid grid-cols-1 md:grid-cols-6 gap-3 items-end">
        <div> 
            <h1 class="text-bold text-xl">Filtros</h1>
            <label for="data_inicio" class="block text-sm text-gray-700">Data início</label>
            <input type="date" id="data_inicio" name="data_inicio" value="{{ request('data_inicio') }}"
                   class="border rounded px-3 py-2 w-full">
        </div>
        <div>
            <label for="data_fim" class="block text-sm text-gray-700">Data fim</label>
            <input type="date" id="data_fim" name="data_fim" value="{{ request('data_fim') }}"
                   class="border rounded px-3 py-2 w-full">
        </div>
        <div>
            <label for="gestor_id" class="block text-sm text-gray-700">Gestor</label>
            <select id="gestor_id" name="gestor_id" class="border rounded px-3 py-2 w-full">
                <option value="">Todos</option>
                @foreach($gestoresList as $gestor)
                    <option value="{{ $gestor->id }}" {{ (string)request('gestor_id') === (string)$gestor->id ? 'selected' : '' }}>
                        {{ $gestor->user->name ?? $gestor->razao_social }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="distribuidor_id" class="block text-sm text-gray-700">Distribuidor</label>
            <select id="distribuidor_id" name="distribuidor_id" class="border rounded px-3 py-2 w-full">
                <option value="">Todos</option>
                @foreach($distribuidoresList as $distribuidor)
                    <option value="{{ $distribuidor->id }}" {{ (string)request('distribuidor_id') === (string)$distribuidor->id ? 'selected' : '' }}>
                        {{ $distribuidor->user->name ?? $distribuidor->razao_social }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Aplicar</button>
            @if(request()->hasAny(['data_inicio','data_fim','gestor_id','distribuidor_id']))
                <a href="{{ route('admin.dashboard') }}" class="px-4 py-2 rounded border">Limpar</a>
            @endif
        </div>
        @if(request('data_inicio') || request('data_fim') || request('gestor_id') || request('distribuidor_id'))
            <div class="text-sm text-gray-600 md:col-span-2 md:justify-self-end">
                Filtro:
                <strong>
                    {{ request('data_inicio') ? \Carbon\Carbon::parse(request('data_inicio'))->format('d/m/Y') : '—' }}
                    —
                    {{ request('data_fim') ? \Carbon\Carbon::parse(request('data_fim'))->format('d/m/Y') : '—' }}
                </strong>
                @if(request('gestor_id'))
                    • Gestor: <strong>
                        {{ optional($gestoresList->firstWhere('id', request('gestor_id'))?->user)->name
                           ?? $gestoresList->firstWhere('id', request('gestor_id'))?->razao_social }}
                    </strong>
                @endif
                @if(request('distribuidor_id'))
                    • Distribuidor: <strong>
                        {{ optional($distribuidoresList->firstWhere('id', request('distribuidor_id'))?->user)->name
                           ?? $distribuidoresList->firstWhere('id', request('distribuidor_id'))?->razao_social }}
                    </strong>
                @endif
            </div>
        @endif
    </form>

    <div class="max-w-7xl mx-auto p-6 space-y-6">

        {{-- Indicadores de valores --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white shadow rounded p-4">
                <div class="text-sm text-gray-500">Soma no PERÍODO (todas as páginas)</div>
                <div class="text-2xl font-semibold">
                    R$ {{ number_format((float)$somaPeriodo, 2, ',', '.') }}
                </div>
            </div>
            <div class="bg-white shadow rounded p-4">
                <div class="text-sm text-gray-500">Soma desta PÁGINA</div>
                <div class="text-2xl font-semibold">
                    R$ {{ number_format((float)$somaPagina, 2, ',', '.') }}
                </div>
            </div>
            <div class="bg-white shadow rounded p-4">
                <div class="text-sm text-gray-500">Soma GERAL (todos os pedidos)</div>
                <div class="text-2xl font-semibold">
                    R$ {{ number_format((float)$somaGeralTodosPedidos, 2, ',', '.') }}
                </div>
            </div>
        </div>

        {{-- Tabela --}}
        <h2 class="text-xl font-semibold">Pedidos</h2>

        <div class="overflow-x-auto bg-white shadow rounded-lg">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b bg-gray-100 text-sm">
                        <th class="p-3">#</th>
                        <th class="p-3">Data</th>
                        <th class="p-3">Gestor</th>
                        <th class="p-3">Distribuidores Vinculados</th>
                        <th class="p-3">Cidade(s)</th>
                        <th class="p-3">Valor</th>
                        <th class="p-3">Status</th>
                    </tr>
                </thead>
                <tbody class="text-sm">
                    @forelse ($pedidos as $pedido)
                        <tr class="border-b">
                            <td class="p-3">{{ $pedido->id }}</td>
                            <td class="p-3">
                                {{ $pedido->data ? \Carbon\Carbon::parse($pedido->data)->format('d/m/Y') : '-' }}
                            </td>
                            <td class="p-3">
                                {{ optional($pedido->gestor->user)->name ?? ($pedido->gestor->razao_social ?? '-') }}
                            </td>
                            <td class="p-3">
                                @php
                                $dists = optional($pedido->gestor)->distribuidores ?? collect();
                                @endphp
                                @if($dists->isEmpty())
                                <span class="text-gray-500">—</span>
                                @else
                                {{ $dists->map(fn($d) => $d->user->name ?? $d->razao_social)->filter()->join(', ') }}
                                @endif
                            </td>
                            <td class="p-3">
                                {{ $pedido->cidades->pluck('name')->join(', ') ?: '-' }}
                            </td>
                            <td class="p-3 font-semibold">
                                R$ {{ number_format((float)$pedido->valor_total, 2, ',', '.') }}
                            </td>
                            <td class="p-3">
                                {{ ucfirst(str_replace('_',' ', $pedido->status ?? '-')) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-4 text-center text-gray-500">Nenhum pedido encontrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{--Rodapé--}}
        <div class="flex items-center justify-between">
            <div class="text-sm text-gray-600">
                Soma dos valores desta página:
                <strong>R$ {{ number_format((float)($somaPagina ?? 0), 2, ',', '.') }}</strong>
            </div>
            <div>
                {{ $pedidos->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
