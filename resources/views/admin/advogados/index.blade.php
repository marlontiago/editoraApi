<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold text-gray-800 leading-tight">Advogados</h2>
                <p class="text-sm text-gray-500 mt-1">Cadastro, contato e atuação.</p>
            </div>

            <a href="{{ route('admin.advogados.create') }}"
               class="inline-flex items-center gap-2 rounded-lg bg-gray-900 px-4 h-10 text-sm font-semibold text-white hover:bg-gray-800 transition">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14M5 12h14"/>
                </svg>
                Novo advogado
            </a>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto p-6 space-y-4">
        {{-- Flash --}}
        @if(session('success'))
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-800">
                {{ session('success') }}
            </div>
        @endif

        {{-- Tabela --}}
        <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-[900px] w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600 text-xs uppercase tracking-wide">
                        <tr class="border-b border-gray-100 text-left">
                            <th class="px-4 py-3 whitespace-nowrap">Nome</th>
                            <th class="px-4 py-3 whitespace-nowrap">Email</th>
                            <th class="px-4 py-3 whitespace-nowrap">Telefone</th>
                            <th class="px-4 py-3 whitespace-nowrap">OAB</th>
                            <th class="px-4 py-3 whitespace-nowrap">Cidade / UF</th>
                            <th class="px-4 py-3 whitespace-nowrap text-right w-56">Ações</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100">
                        @forelse($advogados as $a)
                            <tr class="hover:bg-gray-50/60">
                                <td class="px-4 py-3 font-medium text-gray-900">{{ $a->nome }}</td>
                                <td class="px-4 py-3">
                                    @if(!empty($a->email))
                                        <a href="mailto:{{ $a->email }}" class="text-blue-700 hover:underline">
                                            {{ $a->email }}
                                        </a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-4 py-3">{{ $a->telefone ?: '—' }}</td>
                                <td class="px-4 py-3">{{ $a->oab ?: '—' }}</td>
                                <td class="px-4 py-3">
                                    {{ $a->cidade ?: '—' }} / {{ $a->estado ? strtoupper($a->estado) : '—' }}
                                </td>

                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('admin.advogados.show', $a) }}"
                                           class="inline-flex items-center justify-center rounded-md border border-gray-200 p-2 text-gray-700 hover:bg-gray-50 transition"
                                           title="Ver">
                                            Ver
                                        </a>

                                        <a href="{{ route('admin.advogados.edit', $a) }}"
                                           class="inline-flex items-center justify-center rounded-md border border-blue-200 p-2 text-blue-700 hover:bg-blue-50 transition"
                                           title="Editar">
                                            Editar
                                        </a>

                                        <form action="{{ route('admin.advogados.destroy', $a) }}" method="POST"
                                              class="inline"
                                              onsubmit="return confirm('Remover?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="inline-flex items-center justify-center rounded-md border border-red-200 p-2 text-red-700 hover:bg-red-50 transition"
                                                    title="Excluir">
                                                Excluir
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-4 py-10 text-center text-sm text-gray-500" colspan="6">
                                    Nenhum registro.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Paginação --}}
        <div class="flex justify-end">
            {{ $advogados->links() }}
        </div>
    </div>
</x-app-layout>
