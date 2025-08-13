<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard - Admin</h2>
    </x-slot>

    <div class="max-w-full mx-auto p-6">

        
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-start">

            
            <form method="GET" action="{{ route('admin.dashboard') }}"
                  class="bg-white shadow rounded p-4 grid grid-cols-12 gap-3">

                <div class="col-span-12">
                    <h1 class="text-xl font-semibold">Filtros</h1>
                </div>

                
                <div class="col-span-12 md:col-span-3">
                    <label for="data_inicio" class="block text-sm text-gray-700">Data início</label>
                    <input type="date" id="data_inicio" name="data_inicio" value="{{ request('data_inicio') }}"
                           class="border rounded px-3 py-2 w-full">
                </div>

                <div class="col-span-12 md:col-span-3">
                    <label for="data_fim" class="block text-sm text-gray-700">Data fim</label>
                    <input type="date" id="data_fim" name="data_fim" value="{{ request('data_fim') }}"
                           class="border rounded px-3 py-2 w-full">
                </div>

                <div class="col-span-12 md:col-span-3">
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

                <div class="col-span-12 md:col-span-3">
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
                {{-- Status --}}
                <div class="col-span-12 md:col-span-3">
                    <label for="status" class="block text-sm text-gray-700">Status</label>
                    <select id="status" name="status" class="border rounded px-3 py-2 w-full">
                        <option value="">Todos</option>
                        <option value="em_andamento" {{ request('status') === 'em_andamento' ? 'selected' : '' }}>Em andamento</option>
                        <option value="finalizado"   {{ request('status') === 'finalizado'   ? 'selected' : '' }}>Finalizado</option>
                        <option value="cancelado"    {{ request('status') === 'cancelado'    ? 'selected' : '' }}>Cancelado</option>
                    </select>
                </div>

                

                
                <div class="col-span-12 flex flex-wrap items-center gap-6">
                    <button type="submit"
                            class="inline-flex h-9 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded whitespace-nowrap">
                        Aplicar
                    </button>

                    @if(request()->hasAny(['data_inicio','data_fim','gestor_id','distribuidor_id']))
                        <a href="{{ route('admin.dashboard') }}"
                           class="inline-flex h-9 px-4 py-2 text-sm rounded border hover:bg-gray-50 whitespace-nowrap">
                            Limpar
                        </a>
                    @endif

                    <a href="{{ route('admin.admin.dashboard.export.excel', request()->only('data_inicio','data_fim','gestor_id','distribuidor_id','status')) }}"
                       class="inline-flex h-9 px-4 text-sm py-2 rounded bg-green-600 hover:bg-green-700 text-white whitespace-nowrap">
                        Exportar Excel
                    </a>

                    <a href="{{ route('admin.admin.dashboard.export.pdf', request()->only('data_inicio','data_fim','gestor_id','distribuidor_id',)) }}"
                       class="inline-flex h-9 px-4 py-2 text-sm rounded bg-gray-900 hover:bg-gray-800 text-white whitespace-nowrap">
                        Exportar PDF
                    </a>
                </div>

                
                @if(request('data_inicio') || request('data_fim') || request('gestor_id') || request('distribuidor_id'))
                    <div class="col-span-12 text-sm text-gray-600">
                        Filtro:
                        <strong>
                            {{ request('data_inicio') ? \Carbon\Carbon::parse(request('data_inicio'))->format('d/m/Y') : '—' }}
                            —
                            {{ request('data_fim') ? \Carbon\Carbon::parse(request('data_fim'))->format('d/m/Y') : '—' }}
                        </strong>
                        @if(request('gestor_id'))
                            • Gestor:
                            <strong>
                                {{ optional($gestoresList->firstWhere('id', request('gestor_id'))?->user)->name
                                   ?? $gestoresList->firstWhere('id', request('gestor_id'))?->razao_social }}
                            </strong>
                        @endif
                        @if(request('distribuidor_id'))
                            • Distribuidor:
                            <strong>
                                {{ optional($distribuidoresList->firstWhere('id', request('distribuidor_id'))?->user)->name
                                   ?? $distribuidoresList->firstWhere('id', request('distribuidor_id'))?->razao_social }}
                            </strong>
                        @endif
                    </div>
                @endif
            </form>

            
            <div class="grid grid-cols-1 gap-4">
                <div class="bg-white shadow rounded p-4 h-full">
                    <div class="text-sm text-gray-500">Soma no PERÍODO</div>
                    <div class="text-2xl font-semibold">
                        R$ {{ number_format((float)$somaPeriodo, 2, ',', '.') }}
                    </div>
                </div>
                
                <div class="bg-white shadow rounded p-4 h-full">
                    <div class="text-sm text-gray-500">Soma GERAL (todos os pedidos)</div>
                    <div class="text-2xl font-semibold">
                        R$ {{ number_format((float)$somaGeralTodosPedidos, 2, ',', '.') }}
                    </div>
                </div>
            </div>

        </div>

        {{-- TABELA --}}
        <h2 class="mt-6 text-xl font-semibold">Pedidos</h2>

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
                                {{ $pedido->gestor?->user?->name ?? $pedido->gestor?->razao_social ?? '-' }}
                            </td>
                            <td class="p-3">
                                @php $dists = optional($pedido->gestor)->distribuidores ?? collect(); @endphp
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

        {{-- Rodapé --}}
        <div class="flex items-center justify-between mt-4">
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
