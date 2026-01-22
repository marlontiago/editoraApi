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
                <h2 class="text-xl font-semibold text-gray-800 leading-tight">Gestores</h2>
                <p class="text-sm text-gray-500 mt-1">Cadastro, vínculo de distribuidores e importação.</p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('admin.gestores.vincular') }}"
                   class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 h-10 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
                        <circle cx="8.5" cy="7" r="4" stroke-width="2"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 8v6"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M23 11h-6"/>
                    </svg>
                    Vincular distribuidores
                </a>

                {{-- Botão que abre o modal --}}
                <button type="button"
                        class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 h-10 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition"
                        @click="openImport = true">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v12m0 0l4-4m-4 4l-4-4M5 21h14"/>
                    </svg>
                    Importar
                </button>

                <a href="{{ route('admin.gestores.create') }}"
                   class="inline-flex items-center gap-2 rounded-lg bg-gray-900 px-4 h-10 text-sm font-semibold text-white hover:bg-gray-800 transition">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14M5 12h14"/>
                    </svg>
                    Novo gestor
                </a>

                {{-- MODAL IMPORTAR --}}
                <div x-show="openImport" x-cloak class="fixed inset-0 z-[9999] flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-black/40" @click="openImport = false"></div>

                    <div class="relative w-full max-w-lg rounded-xl bg-white shadow-xl ring-1 ring-gray-200">
                        <div class="flex items-center justify-between border-b px-5 py-3">
                            <h3 class="text-base font-semibold text-gray-800">Importar gestores</h3>
                            <button class="text-gray-500 hover:text-gray-700" @click="openImport = false">✕</button>
                        </div>

                        <div class="px-5 py-4 space-y-4">
                            <p class="text-sm text-gray-600">
                                Selecione uma planilha <b>.xlsx</b> ou <b>.xls</b> para importar.
                            </p>

                            <form action="{{ route('admin.gestores.importar') }}"
                                  method="POST"
                                  enctype="multipart/form-data"
                                  class="space-y-3">
                                @csrf

                                <div class="space-y-2">
                                    <label class="block text-sm font-medium text-gray-700">Arquivo</label>

                                    <input type="file"
                                           name="arquivo"
                                           accept=".xlsx,.xls"
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

                            {{-- Erros da importação (organizados) --}}
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
                                                            Linha {{ $e['linha'] ?? '-' }} — {{ $e['gestor'] ?? '—' }}
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

        {{-- FILTRO (layout clean, mesmo padrão do Produtos) --}}
        <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-100 px-5 py-4">
            <form method="GET" action="{{ route('admin.gestores.index') }}"
                  class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                <div class="md:col-span-7">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Buscar</label>
                    <input
                        type="text"
                        name="q"
                        value="{{ $q ?? request('q') }}"
                        placeholder="Razão social, CNPJ, representante, e-mail…"
                        class="h-10 w-full rounded-lg border-gray-200 px-3 text-sm shadow-sm focus:border-gray-900 focus:ring-2 focus:ring-gray-900/10"
                    />
                </div>

                <div class="md:col-span-3">
                    <label class="block text-xs font-medium text-gray-600 mb-1">UF</label>
                    <select
                        name="uf"
                        class="h-10 w-full rounded-lg border-gray-200 px-3 text-sm shadow-sm focus:border-gray-900 focus:ring-2 focus:ring-gray-900/10"
                    >
                        <option value="">Todas</option>
                        @foreach ($ufs as $sigla)
                            <option value="{{ $sigla }}" @selected(($uf ?? request('uf')) === $sigla)>
                                {{ $sigla }}
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
                        href="{{ route('admin.gestores.index') }}"
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
                            <th class="px-4 py-3 whitespace-nowrap">E-mail</th>
                            <th class="px-4 py-3 whitespace-nowrap">Contrato até</th>
                            <th class="px-4 py-3 whitespace-nowrap">Assinado</th>
                            <th class="px-4 py-3 whitespace-nowrap text-center w-32">Ações</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100">
                        @forelse($gestores as $gestor)
                            <tr class="hover:bg-gray-50/60">
                                <td class="px-4 py-3 font-medium text-gray-900">{{ $gestor->razao_social }}</td>
                                <td class="px-4 py-3">{{ $gestor->cnpj }}</td>
                                <td class="px-4 py-3">{{ $gestor->email_exibicao }}</td>
                                <td class="px-4 py-3">
                                    @if($gestor->vencimento_contrato)
                                        {{ \Carbon\Carbon::parse($gestor->vencimento_contrato)->format('d/m/Y') }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if($gestor->contrato_assinado)
                                        <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700">Sim</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700">Não</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <a href="{{ route('admin.gestores.show', $gestor) }}"
                                       class="text-blue-700 hover:underline">Ver</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-10 text-center text-sm text-gray-500">
                                    Nenhum gestor cadastrado.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Paginação --}}
        <div class="flex justify-end">
            {{ $gestores->links() }}
        </div>
    </div>
</x-app-layout>
