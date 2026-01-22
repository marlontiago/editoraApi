<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4"
             x-data="{
                openImport: false,
                showImportErrors: false,
                hasImportErrors: {{ session('import_erros') ? 'true' : 'false' }},
                init() {
                    if (this.hasImportErrors) {
                        this.openImport = true;
                        this.showImportErrors = true;
                    }
                }
             }"
        >
            <div>
                <h2 class="text-xl font-semibold text-gray-800 leading-tight">Distribuidores</h2>
                <p class="text-sm text-gray-500 mt-1">Cadastro, vínculo e importação.</p>
            </div>

            <div class="flex items-center gap-2">
                <button type="button"
                        class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 h-10 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition"
                        @click="openImport = true">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v12m0 0l4-4m-4 4l-4-4M5 21h14"/>
                    </svg>
                    Importar
                </button>

                <a href="{{ route('admin.distribuidores.create') }}"
                   class="inline-flex items-center gap-2 rounded-lg bg-gray-900 px-4 h-10 text-sm font-semibold text-white hover:bg-gray-800 transition">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14M5 12h14"/>
                    </svg>
                    Novo distribuidor
                </a>

                {{-- MODAL IMPORTAR --}}
                <div x-show="openImport" x-cloak class="fixed inset-0 z-[9999] flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-black/40" @click="openImport = false"></div>

                    <div class="relative w-full max-w-lg rounded-xl bg-white shadow-xl ring-1 ring-gray-200">
                        <div class="flex items-center justify-between border-b px-5 py-3">
                            <h3 class="text-base font-semibold text-gray-800">Importar distribuidores</h3>
                            <button class="text-gray-500 hover:text-gray-700" @click="openImport = false">✕</button>
                        </div>

                        <div class="px-5 py-4 space-y-4">
                            <p class="text-sm text-gray-600">
                                Envie <b>.xlsx</b>, <b>.xls</b> ou <b>.csv</b>.
                            </p>

                            <form action="{{ route('admin.distribuidores.importar') }}"
                                  method="POST"
                                  enctype="multipart/form-data"
                                  class="space-y-3">
                                @csrf

                                <div class="space-y-2">
                                    <label class="block text-sm font-medium text-gray-700">Arquivo</label>

                                    <input type="file"
                                           name="arquivo"
                                           accept=".xlsx,.xls,.csv,.txt"
                                           required
                                           class="block w-full text-sm text-gray-700
                                                  file:mr-3 file:rounded-md file:border-0
                                                  file:bg-gray-100 file:px-3 file:py-2
                                                  file:text-sm file:font-semibold file:text-gray-700
                                                  hover:file:bg-gray-200" />

                                    <label class="flex items-center gap-2 text-sm text-gray-600">
                                        <input type="checkbox" name="atualizar_existentes" value="1" checked class="rounded border-gray-300">
                                        Atualizar existentes (por CNPJ)
                                    </label>
                                </div>

                                <div class="flex items-center justify-end gap-2 pt-2">
                                    <button type="button"
                                            class="h-10 rounded-lg border border-gray-200 bg-white px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition"
                                            @click="openImport = false">
                                        Cancelar
                                    </button>

                                    <button type="submit"
                                            class="h-10 rounded-lg bg-gray-900 px-4 text-sm font-semibold text-white hover:bg-gray-800 transition">
                                        Enviar e importar
                                    </button>
                                </div>
                            </form>

                            {{-- Erros da importação --}}
                            @if (session('import_erros'))
                                <div class="rounded-lg border border-red-200 bg-red-50 p-4">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="text-sm text-red-800">
                                            <div class="font-semibold">Erros na importação</div>
                                            <div class="text-red-700">
                                                {{ count(session('import_erros')) }} linha(s) com problema.
                                            </div>
                                        </div>

                                        <button type="button"
                                                class="text-sm font-semibold text-red-800 underline"
                                                @click="showImportErrors = !showImportErrors">
                                            <span x-text="showImportErrors ? 'Ocultar' : 'Ver detalhes'"></span>
                                        </button>
                                    </div>

                                    <div x-show="showImportErrors" x-cloak class="mt-3">
                                        <div class="max-h-60 overflow-auto rounded-lg border border-red-200 bg-white">
                                            <ul class="divide-y divide-red-100 text-sm">
                                                @foreach (session('import_erros') as $e)
                                                    <li class="px-3 py-2">
                                                        <div class="font-semibold text-gray-800">
                                                            Linha {{ $e['linha'] ?? '-' }} — {{ $e['distribuidor'] ?? '—' }}
                                                        </div>
                                                        <div class="text-gray-600">
                                                            CNPJ: {{ $e['cnpj'] ?? '—' }}
                                                        </div>
                                                        <div class="text-red-700">
                                                            {{ $e['erro'] ?? 'Erro não informado' }}
                                                        </div>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                {{-- /MODAL --}}
            </div>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto p-6 space-y-4">
        {{-- Flash --}}
        @if (session('success'))
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-800">
                {{ session('success') }}
            </div>
        @endif

        {{-- FILTRO (mesmo padrão do Produtos) --}}
        <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-100 px-5 py-4">
            <form method="GET" action="{{ route('admin.distribuidores.index') }}"
                  class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                <div class="md:col-span-6">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Buscar</label>
                    <input
                        type="text"
                        name="q"
                        value="{{ $q ?? request('q') }}"
                        placeholder="Razão social, CNPJ, e-mail…"
                        class="h-10 w-full rounded-lg border-gray-200 px-3 text-sm shadow-sm focus:border-gray-900 focus:ring-2 focus:ring-gray-900/10"
                    />
                </div>

                <div class="md:col-span-4">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Gestor</label>
                    <select
                        name="gestor_id"
                        class="h-10 w-full rounded-lg border-gray-200 px-3 text-sm shadow-sm focus:border-gray-900 focus:ring-2 focus:ring-gray-900/10"
                    >
                        <option value="">Todos</option>
                        @foreach ($gestores as $g)
                            <option value="{{ $g->id }}" @selected((int)($gestorId ?? request('gestor_id')) === (int)$g->id)>
                                {{ $g->razao_social }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-2 flex gap-2">
                    <button
                        type="submit"
                        class="h-10 w-full rounded-lg border border-gray-200 bg-white px-3 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition"
                    >
                        Filtrar
                    </button>

                    <a
                        href="{{ route('admin.distribuidores.index') }}"
                        class="h-10 w-full rounded-lg border border-gray-200 bg-white px-3 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition inline-flex items-center justify-center"
                    >
                        Limpar
                    </a>
                </div>
            </form>
        </div>

        {{-- Tabela --}}
        <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-[900px] w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600 text-xs uppercase tracking-wide">
                        <tr class="border-b border-gray-100 text-left">
                            <th class="px-4 py-3 whitespace-nowrap">Razão Social</th>
                            <th class="px-4 py-3 whitespace-nowrap">CNPJ</th>
                            <th class="px-4 py-3 whitespace-nowrap">Gestor</th>
                            <th class="px-4 py-3 whitespace-nowrap">E-mails</th>
                            <th class="px-4 py-3 whitespace-nowrap text-center w-32">Ações</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100">
                        @forelse($distribuidores as $d)
                            <tr class="hover:bg-gray-50/60">
                                <td class="px-4 py-3 font-medium text-gray-900">{{ $d->razao_social }}</td>
                                <td class="px-4 py-3">{{ $d->cnpj ?? '—' }}</td>
                                <td class="px-4 py-3">{{ $d->gestor->razao_social ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    @php
                                        $emails = is_array($d->emails) ? $d->emails : (is_string($d->emails) ? json_decode($d->emails, true) : []);
                                        $emailsTxt = is_array($emails) ? implode(', ', $emails) : '—';
                                    @endphp
                                    {{ $emailsTxt ?: '—' }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <a href="{{ route('admin.distribuidores.show', $d) }}"
                                       class="text-blue-700 hover:underline">Ver</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-10 text-center text-sm text-gray-500">
                                    Nenhum distribuidor cadastrado.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Paginação --}}
        <div class="flex justify-end">
            {{ $distribuidores->links() }}
        </div>
    </div>
</x-app-layout>
