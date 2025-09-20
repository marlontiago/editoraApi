<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Vincular Distribuidores ⇄ Gestores</h2>
            <a href="{{ route('admin.gestores.index') }}"
               class="inline-flex h-9 items-center rounded-md border px-3 text-sm hover:bg-gray-50">
                Voltar
            </a>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto p-6 space-y-6">
        {{-- Alerts --}}
        @foreach (['success'=>'green','info'=>'blue','error'=>'red'] as $key=>$color)
            @if (session($key))
                <div class="rounded-md border border-{{ $color }}-300 bg-{{ $color }}-50 p-3 text-{{ $color }}-800">
                    {{ session($key) }}
                </div>
            @endif
        @endforeach

        {{-- Filtros --}}
        <form method="GET" action="{{ route('admin.gestores.vincular') }}"
              class="bg-white rounded-lg border p-4 grid grid-cols-12 gap-3">
            <div class="col-span-12 md:col-span-6">
                <label class="text-sm text-gray-600">Buscar distribuidor</label>
                <input type="text" name="busca" value="{{ $busca ?? '' }}"
                       placeholder="Razão social, CNPJ, representante..."
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div class="col-span-12 md:col-span-4">
                <label class="text-sm text-gray-600">Filtrar por Gestor atual</label>
                <select name="gestor"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">— Todos —</option>
                    @foreach ($gestores as $g)
                        <option value="{{ $g->id }}" @selected(($gestorFiltro ?? null) == $g->id)>{{ $g->razao_social }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-span-12 md:col-span-2 flex items-end">
                <button class="inline-flex h-10 items-center rounded-md border px-4 text-sm hover:bg-gray-50 w-full justify-center">
                    Filtrar
                </button>
            </div>
        </form>

        {{-- Tabela + formulário de mapeamento --}}
        <form method="POST" action="{{ route('admin.gestores.vincular.salvar') }}" class="bg-white rounded-lg border">
            @csrf

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left font-medium text-gray-700">Distribuidor</th>
                            <th class="px-3 py-2 text-left font-medium text-gray-700">CNPJ</th>
                            <th class="px-3 py-2 text-left font-medium text-gray-700">Gestor atual</th>
                            <th class="px-3 py-2 text-left font-medium text-gray-700">Novo vínculo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($distribuidores as $d)
                            <tr>
                                <td class="px-3 py-2">
                                    <div class="font-medium text-gray-900">{{ $d->razao_social }}</div>
                                    @if($d->representante_legal)
                                        <div class="text-xs text-gray-500">Rep.: {{ $d->representante_legal }}</div>
                                    @endif
                                </td>
                                <td class="px-3 py-2">{{ $d->cnpj }}</td>
                                <td class="px-3 py-2">
                                    @if($d->gestor)
                                        <span class="inline-flex items-center rounded bg-blue-50 px-2 py-0.5 text-xs text-blue-700 border border-blue-200">
                                            {{ $d->gestor->razao_social }}
                                        </span>
                                    @else
                                        <span class="text-xs text-gray-500">Sem vínculo</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2">
                                    <select name="vinculos[{{ $d->id }}]"
                                            class="block w-full rounded-md border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">— Sem vínculo —</option>
                                        @foreach ($gestores as $g)
                                            <option value="{{ $g->id }}" @selected($d->gestor_id === $g->id)>{{ $g->razao_social }}</option>
                                        @endforeach
                                    </select>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-3 py-6 text-center text-gray-500">Nenhum distribuidor encontrado.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="flex items-center justify-between px-4 py-3">
                <div>{{ $distribuidores->links() }}</div>
                <button type="submit"
                        class="inline-flex h-10 items-center rounded-md bg-green-600 px-5 text-sm font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                    Salvar alterações
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
