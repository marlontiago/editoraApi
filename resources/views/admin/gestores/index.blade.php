<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between"
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
            <h2 class="text-xl font-semibold text-gray-800">
                Gestores
            </h2>

            <div class="flex items-center gap-2">
                <a href="{{ route('admin.gestores.vincular') }}"
                   class="inline-flex h-9 items-center bg-blue-700 text-white rounded-md border px-3 text-sm hover:bg-blue-900">
                    Vincular Distribuidores
                </a>

                {{-- Botão que abre o modal --}}
                <button type="button"
                        class="inline-flex h-9 items-center bg-indigo-600 text-white rounded-md border px-3 text-sm hover:bg-indigo-700"
                        @click="openImport = true">
                    Importar gestores
                </button>

                <a href="{{ route('admin.gestores.create') }}"
                   class="inline-flex items-center rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                    + Novo Gestor
                </a>

                {{-- MODAL IMPORTAR --}}
                <div x-show="openImport" x-cloak class="fixed inset-0 z-[9999] flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-black/40" @click="openImport = false"></div>

                    <div class="relative w-full max-w-lg rounded-lg bg-white shadow-xl border border-gray-200">
                        <div class="flex items-center justify-between border-b px-4 py-3">
                            <h3 class="text-base font-semibold text-gray-800">Importar gestores</h3>
                            <button class="text-gray-500 hover:text-gray-700" @click="openImport = false">✕</button>
                        </div>

                        <div class="px-4 py-4 space-y-4">
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
                                            class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                                            @click="openImport = false">
                                        Cancelar
                                    </button>

                                    <button type="submit"
                                            class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                                        Enviar e importar
                                    </button>
                                </div>
                            </form>

                            {{-- Erros da importação (organizados) --}}
                            @if (session('import_erros'))
                                <div class="rounded-md border border-red-200 bg-red-50 p-3">
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
                                        <div class="max-h-60 overflow-auto rounded border border-red-200 bg-white">
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

    <div class="max-w-7xl mx-auto p-6">
        @if (session('success'))
            <div class="mb-4 rounded-md border border-green-300 bg-green-50 p-4 text-green-800">
                {{ session('success') }}
            </div>
        @endif
        {{-- FILTRO --}}
        <div class="bg-white shadow rounded-lg p-4">
            <form method="GET" action="{{ route('admin.gestores.index') }}" class="grid grid-cols-1 md:grid-cols-12 gap-3">
                <div class="md:col-span-7">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Buscar</label>
                    <input
                        type="text"
                        name="q"
                        value="{{ $q ?? request('q') }}"
                        placeholder="Razão social, CNPJ, representante, e-mail…"
                        class="w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm"
                    />
                </div>

                <div class="md:col-span-3">
                    <label class="block text-xs font-medium text-gray-600 mb-1">UF</label>
                    <select
                        name="uf"
                        class="w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm"
                    >
                        <option value="">Todas</option>
                        @foreach ($ufs as $sigla)
                            <option value="{{ $sigla }}" @selected(($uf ?? request('uf')) === $sigla)>
                                {{ $sigla }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-2 flex items-end gap-2">
                    <button
                        type="submit"
                        class="inline-flex h-9 w-full items-center justify-center rounded-md bg-blue-700 px-3 text-sm font-medium text-white hover:bg-blue-900"
                    >
                        Filtrar
                    </button>

                    <a
                        href="{{ route('admin.gestores.index') }}"
                        class="inline-flex h-9 w-full items-center justify-center rounded-md border bg-white px-3 text-sm text-gray-700 hover:bg-gray-50"
                    >
                        Limpar
                    </a>
                </div>
            </form>
        </div>

        <div class="bg-white shadow rounded-lg overflow-hidden">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="bg-gray-100 text-left text-gray-600 border-b">
                        <th class="px-4 py-2">Razão Social</th>
                        <th class="px-4 py-2">CNPJ</th>
                        <th class="px-4 py-2">E-mail</th>
                        <th class="px-4 py-2">Contrato até</th>
                        <th class="px-4 py-2">Assinado</th>
                        <th class="px-4 py-2 text-center w-32">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($gestores as $gestor)
                        <tr>
                            <td class="px-4 py-2">{{ $gestor->razao_social }}</td>
                            <td class="px-4 py-2">{{ $gestor->cnpj }}</td>
                            <td class="px-4 py-2">{{ $gestor->email_exibicao }}</td>
                            <td class="px-4 py-2">
                                @if($gestor->vencimento_contrato)
                                    {{ \Carbon\Carbon::parse($gestor->vencimento_contrato)->format('d/m/Y') }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-2">
                                @if($gestor->contrato_assinado)
                                    <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700">Sim</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700">Não</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-center">
                                <a href="{{ route('admin.gestores.show', $gestor) }}"
                                   class="text-blue-600 hover:underline">Ver</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-4 text-center text-gray-500">
                                Nenhum gestor cadastrado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $gestores->links() }}
        </div>
    </div>
</x-app-layout>
