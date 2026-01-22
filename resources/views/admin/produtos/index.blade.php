<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold text-gray-800 leading-tight">Lista de Produtos</h2>
                <p class="text-sm text-gray-500 mt-1">Catálogo, estoque, importação e coleções.</p>
            </div>

            <div class="flex items-center gap-2" x-data="{ openColecao:false }">

                <form method="POST"
                      action="{{ route('admin.produtos.import') }}"
                      enctype="multipart/form-data"
                      class="flex items-center gap-2">
                    @csrf

                    <label class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white px-3 h-10 text-sm font-semibold text-gray-700 hover:bg-gray-50 cursor-pointer transition">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v12m0 0l4-4m-4 4l-4-4M5 21h14"/>
                        </svg>
                        <span class="ml-2">Importar</span>
                        <input type="file"
                               name="arquivo"
                               accept=".xlsx,.xls,.csv"
                               class="hidden"
                               onchange="this.form.submit()">
                    </label>
                </form>

                <a href="{{ route('admin.produtos.create') }}"
                   class="inline-flex items-center gap-2 rounded-lg bg-gray-900 px-4 h-10 text-sm font-semibold text-white hover:bg-gray-800 transition">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14M5 12h14"/>
                    </svg>
                    Novo produto
                </a>

                <button type="button"
                        @click="openColecao = true"
                        class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 h-10 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h16M4 12h10M4 17h16"/>
                    </svg>
                    Coleções
                </button>

                {{-- Modal Coleções (mantido) --}}
                <div
                    x-show="openColecao"
                    x-transition
                    x-cloak
                    class="fixed inset-0 z-50 flex items-center justify-center p-4"
                    :class="openColecao ? 'pointer-events-auto' : 'pointer-events-none'"
                    style="display:none"
                >
                    <div class="absolute inset-0 bg-black/40 pointer-events-auto" @click="openColecao=false"></div>

                    <div class="relative z-10 w-full max-w-3xl rounded-xl bg-white shadow-xl pointer-events-auto">
                        <div class="flex items-center justify-between border-b px-5 py-3">
                            <h3 class="text-lg font-semibold text-gray-800">Gerenciar Coleções</h3>
                            <button class="text-gray-500 hover:text-gray-700" @click="openColecao=false">✕</button>
                        </div>

                        {{-- ==== Criar nova coleção ==== --}}
                        <form action="{{ route('admin.colecoes.quickCreate') }}" method="POST" class="p-5"
                              x-data="colecaoForm()" x-init="init()">
                            @csrf

                            <div class="grid grid-cols-12 gap-4">
                                <div class="col-span-12 sm:col-span-4">
                                    <label class="block text-sm font-medium text-gray-700">Código <span class="text-red-500">*</span></label>
                                    <input type="text" name="codigo" x-model="codigo"
                                           class="mt-1 w-full rounded-md border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                           placeholder="Ex.: COL-2025-01" required>
                                </div>
                                <div class="col-span-12 sm:col-span-8">
                                    <label class="block text-sm font-medium text-gray-700">Nome <span class="text-red-500">*</span></label>
                                    <input type="text" name="nome" x-model="nome"
                                           class="mt-1 w-full rounded-md border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                           placeholder="Ex.: Coleção Matemática Fundamental" required>
                                </div>

                                <div class="col-span-12">
                                    <div class="mt-2 max-h-72 overflow-auto rounded-md border">
                                        {{-- Lista de produtos com checkboxes --}}
                                        <template x-for="p in filtrados()" :key="p.id">
                                            <label class="flex items-center gap-3 px-3 py-2 border-b last:border-b-0">
                                                <input type="checkbox" class="rounded border-gray-300"
                                                       :value="p.id" name="produtos[]" x-model="selecionados">
                                                <div class="flex-1 min-w-0">
                                                    <div class="text-sm font-medium text-gray-800 truncate" x-text="p.titulo ?? '—'"></div>
                                                    <div class="text-xs text-gray-500 truncate">
                                                        <span x-text="'ISBN: ' + (p.isbn ?? '—')"></span>
                                                        <span class="mx-2">•</span>
                                                        <span x-text="'Autores: ' + (p.autores ?? '—')"></span>
                                                    </div>
                                                </div>
                                            </label>
                                        </template>

                                        {{-- Caso filtro não encontre nada --}}
                                        <div class="p-4 text-center text-sm text-gray-500" x-show="filtrados().length===0">
                                            Nenhum produto encontrado para este filtro.
                                        </div>
                                    </div>
                                    <p class="mt-1 text-xs text-gray-500">Você pode criar a coleção sem selecionar produtos agora e vincular depois.</p>
                                </div>
                            </div>

                            <div class="mt-5 flex items-center justify-end gap-2 border-t pt-4">
                                <button type="button" @click="openColecao=false"
                                        class="rounded-md border px-3 py-2 text-sm hover:bg-gray-50">Cancelar</button>
                                <button type="submit"
                                        class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                                    Salvar Coleção
                                </button>
                            </div>
                        </form>

                        {{-- ==== Coleções existentes ==== --}}
                        <div class="border-t px-5 py-4" x-data="colecoesList()">
                            <div class="max-h-56 overflow-auto rounded-md border">
                                <template x-for="c in filtradas()" :key="c.id">
                                    <div class="flex items-center justify-between px-3 py-2 border-b last:border-b-0">
                                        <div class="min-w-0">
                                            <div class="text-sm font-medium text-gray-800 truncate" x-text="c.nome"></div>
                                            <div class="text-xs text-gray-500">
                                                <span x-text="'Código: ' + c.codigo"></span>
                                                <span class="mx-2">•</span>
                                                <span x-text="(c.produtos_count ?? 0) + ' produto(s)'"></span>
                                            </div>
                                        </div>

                                        {{-- Excluir --}}
                                        <form :action="routeDestroy(c.id)" method="POST"
                                              onsubmit="return confirm('Excluir esta coleção? Os produtos vinculados ficarão sem coleção.');"
                                              class="shrink-0">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="inline-flex items-center rounded-md border px-3 py-1.5 text-xs font-medium text-red-700 border-red-200 hover:bg-red-50"
                                                    :disabled="(c.produtos_count ?? 0) > 0 && false">
                                                Excluir
                                            </button>
                                        </form>
                                    </div>
                                </template>

                                <div class="p-4 text-center text-sm text-gray-500" x-show="filtradas().length===0">
                                    Nenhuma coleção encontrada para este filtro.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Alpine data para o modal --}}
                <script>
                    function colecaoForm(){
                        return {
                            codigo: '',
                            nome: '',
                            busca: '',
                            selecionados: [],
                            produtosBase: @json($produtosParaColecao ?? []),
                            init(){},
                            filtrados(){
                                if(!this.busca) return this.produtosBase;
                                const b = this.busca.toLowerCase();
                                return this.produtosBase.filter(p => {
                                    return (p.titulo ?? '').toLowerCase().includes(b)
                                        || (p.isbn ?? '').toLowerCase().includes(b)
                                        || (p.autores ?? '').toLowerCase().includes(b);
                                });
                            },
                            toggleAll(on){
                                if(on){
                                    this.selecionados = this.filtrados().map(p => p.id);
                                } else {
                                    this.selecionados = [];
                                }
                            }
                        }
                    }

                    function colecoesList(){
                        return {
                            busca: '',
                            colecoes: @json($colecoesResumo ?? []),
                            filtradas(){
                                if(!this.busca) return this.colecoes;
                                const b = this.busca.toLowerCase();
                                return this.colecoes.filter(c =>
                                    (c.nome ?? '').toLowerCase().includes(b) ||
                                    (c.codigo ?? '').toLowerCase().includes(b)
                                );
                            },
                            routeDestroy(id){
                                const base = @json(route('admin.colecoes.destroy', ['colecao' => 0]));
                                return base.replace(/0$/, String(id));
                            }
                        }
                    }
                </script>

            </div>
        </div>
    </x-slot>

    @php
        $sort = request('sort', 'titulo');
        $dir  = request('dir', 'asc');
    @endphp

    <div class="max-w-full mx-auto p-6 space-y-4">

        {{-- Flash --}}
        @if (session('success'))
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-800">
                {{ session('success') }}
            </div>
        @endif

        {{-- Produtos com estoque baixo --}}
        @if($produtosComEstoqueBaixo->isNotEmpty())
            <div x-data="{ open: false }" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3">
                <button @click="open = !open" class="w-full text-left flex items-center gap-2">
                    <span class="text-sm font-semibold text-red-800">⚠️ Produtos com estoque baixo</span>
                    <span class="ml-auto text-xs font-medium text-red-700 rounded-full bg-red-100 px-2 py-0.5">
                        {{ $produtosComEstoqueBaixo->count() }} itens
                    </span>
                </button>
                <ul x-show="open" x-transition class="mt-2 list-disc pl-5 text-sm text-gray-700 space-y-1">
                    @foreach($produtosComEstoqueBaixo as $produto)
                        <li>
                            {{ $produto->titulo }}
                            <span class="text-red-700 font-semibold">
                                : {{ $produto->quantidade_estoque ?? 'vazio'}} em estoque
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Estoque em pedidos em potencial --}}
        @if($estoqueParaPedidosEmPotencial->isNotEmpty())
            <div x-data="{ open: false }" class="rounded-xl border border-yellow-200 bg-yellow-50 px-4 py-3">
                <button @click="open = !open" class="w-full text-left flex items-center gap-2">
                    <span class="text-sm font-semibold text-yellow-900">⚠️ Estoque em risco para pedidos futuros</span>
                    <span class="ml-auto text-xs font-medium text-yellow-800 rounded-full bg-yellow-100 px-2 py-0.5">
                        {{ $estoqueParaPedidosEmPotencial->count() }} itens
                    </span>
                </button>
                <ul x-show="open" x-transition class="mt-2 list-disc pl-5 text-sm text-gray-700 space-y-2">
                    @foreach($estoqueParaPedidosEmPotencial as $produto)
                        <li>
                            Produto: <strong>{{ $produto->titulo }}</strong> <br>
                            Em pedidos: <span class="font-medium text-green-700">{{ $produto->qtd_em_pedidos }}</span> <br>
                            Disponível: <span class="font-medium text-red-600">{{ $produto->quantidade_estoque }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Busca (layout clean) --}}
        <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-100 px-5 py-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <form method="GET" action="{{ route('admin.produtos.index') }}" class="flex items-center gap-2 w-full sm:w-auto">
                    <div class="relative w-full sm:w-[420px] max-w-full">
                        <input
                            type="text"
                            name="q"
                            value="{{ request('q') }}"
                            placeholder="Buscar por nome, título, autores, coleção..."
                            class="h-10 w-full rounded-lg border-gray-200 px-3 pr-10 text-sm shadow-sm focus:border-gray-900 focus:ring-2 focus:ring-gray-900/10"
                        >
                        <svg class="h-4 w-4 text-gray-400 absolute right-3 top-1/2 -translate-y-1/2" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M10 18a8 8 0 100-16 8 8 0 000 16z"/>
                        </svg>
                    </div>

                    <button type="submit"
                            class="h-10 rounded-lg border border-gray-200 bg-white px-3 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition">
                        Buscar
                    </button>

                    {{-- preserva sort/dir ao buscar --}}
                    @if(request('sort'))
                        <input type="hidden" name="sort" value="{{ request('sort') }}">
                    @endif
                    @if(request('dir'))
                        <input type="hidden" name="dir" value="{{ request('dir') }}">
                    @endif

                    @if(request('q'))
                        <a href="{{ route('admin.produtos.index') }}" class="text-sm text-gray-500 hover:underline">Limpar</a>
                    @endif
                </form>

                <div class="text-xs text-gray-500">
                    Ordenação: <span class="font-medium text-gray-700">{{ $sort }}</span> •
                    <span class="font-medium text-gray-700">{{ $dir }}</span>
                </div>
            </div>
        </div>

        {{-- Tabela --}}
        <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-100 overflow-x-auto">
            <table class="min-w-[1000px] w-full text-sm">
                <thead class="bg-gray-50 text-gray-600 text-xs uppercase tracking-wide">
                    <tr class="border-b border-gray-100">
                        {{-- Cód. (clicável) --}}
                        <th class="px-4 py-3 hidden md:table-cell whitespace-nowrap">
                            <a
                                href="{{ route('admin.produtos.index', array_merge(request()->only(['q','per_page']), [
                                    'sort' => 'codigo',
                                    'dir'  => ($sort === 'codigo' && $dir === 'asc') ? 'desc' : 'asc',
                                ])) }}"
                                class="inline-flex items-center gap-1 hover:underline cursor-pointer"
                            >
                                Cód.
                                @if($sort === 'codigo')
                                    <span class="text-[10px] text-gray-400">{{ $dir === 'asc' ? '▲' : '▼' }}</span>
                                @endif
                            </a>
                        </th>

                        <th class="px-4 py-3 whitespace-nowrap">Imagem</th>

                        {{-- Título (clicável) --}}
                        <th class="px-4 py-3 hidden md:table-cell whitespace-nowrap">
                            <a
                                href="{{ route('admin.produtos.index', array_merge(request()->only(['q','per_page']), [
                                    'sort' => 'titulo',
                                    'dir'  => ($sort === 'titulo' && $dir === 'asc') ? 'desc' : 'asc',
                                ])) }}"
                                class="inline-flex items-center gap-1 hover:underline cursor-pointer"
                            >
                                Título
                                @if($sort === 'titulo')
                                    <span class="text-[10px] text-gray-400">{{ $dir === 'asc' ? '▲' : '▼' }}</span>
                                @endif
                            </a>
                        </th>

                        <th class="px-4 py-3 hidden lg:table-cell whitespace-nowrap">Coleção</th>
                        <th class="px-4 py-3 hidden lg:table-cell whitespace-nowrap">ISBN</th>
                        <th class="px-4 py-3 hidden xl:table-cell whitespace-nowrap">Autores</th>
                        <th class="px-4 py-3 hidden xl:table-cell whitespace-nowrap">Edição</th>
                        <th class="px-4 py-3 hidden lg:table-cell whitespace-nowrap">Ano</th>
                        <th class="px-4 py-3 hidden xl:table-cell whitespace-nowrap">Páginas</th>
                        <th class="px-4 py-3 hidden lg:table-cell whitespace-nowrap">Ano Escolar</th>
                        <th class="px-4 py-3 whitespace-nowrap">Preço</th>
                        <th class="px-4 py-3 whitespace-nowrap">Estoque</th>
                        <th class="px-4 py-3 whitespace-nowrap text-right">Ações</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100">
                    @forelse ($produtos as $produto)
                        <tr class="hover:bg-gray-50/60">
                            {{-- Código --}}
                            <td class="px-4 py-3 hidden md:table-cell">{{ $produto->codigo }}</td>

                            {{-- Imagem --}}
                            <td class="px-4 py-3">
                                @if ($produto->imagem_url)
                                    <img src="{{ $produto->imagem_url }}" alt="{{ $produto->nome }}"
                                        class="h-12 w-12 rounded-lg object-cover ring-1 ring-gray-200">
                                @else
                                    <div class="h-12 w-12 rounded-lg bg-gray-100 grid place-items-center text-[10px] text-gray-500 ring-1 ring-gray-200">
                                        sem<br>imagem
                                    </div>
                                @endif
                            </td>

                            <td class="px-4 py-3 hidden md:table-cell">
                                <div class="font-medium text-gray-900">{{ $produto->titulo ?? '—' }}</div>
                            </td>

                            <td class="px-4 py-3 hidden lg:table-cell">{{ $produto->colecao?->nome ?? '—' }}</td>
                            <td class="px-4 py-3 hidden lg:table-cell">{{ $produto->isbn }}</td>

                            <td class="px-4 py-3 hidden xl:table-cell">
                                <span class="line-clamp-2 text-gray-700">{{ $produto->autores ?? '—' }}</span>
                            </td>

                            <td class="px-4 py-3 hidden xl:table-cell">{{ $produto->edicao ?? '—' }}</td>
                            <td class="px-4 py-3 hidden lg:table-cell">{{ $produto->ano ?? '—' }}</td>
                            <td class="px-4 py-3 hidden xl:table-cell">{{ $produto->numero_paginas ?? '—' }}</td>

                            <td class="px-4 py-3 hidden lg:table-cell">
                                @if($produto->ano_escolar)
                                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-700">
                                        {{ $produto->ano_escolar }}
                                    </span>
                                @else
                                    —
                                @endif
                            </td>

                            {{-- Preço / Estoque (sempre visíveis) --}}
                            <td class="px-4 py-3 font-semibold whitespace-nowrap text-gray-900">
                                R$ {{ number_format((float)($produto->preco ?? 0), 2, ',', '.') }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="font-medium text-gray-900">{{ $produto->quantidade_estoque }}</span>
                            </td>

                            {{-- Ações --}}
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    {{-- Editar --}}
                                    <a href="{{ route('admin.produtos.edit', $produto) }}"
                                       class="inline-flex items-center justify-center rounded-md border border-blue-200 p-2 text-blue-700 hover:bg-blue-50 transition"
                                       title="Editar">
                                        <x-heroicon-o-pencil-square class="w-5 h-5" />
                                    </a>

                                    {{-- Excluir --}}
                                    <form action="{{ route('admin.produtos.destroy', $produto) }}" method="POST" class="inline"
                                          onsubmit="return confirm('Tem certeza que deseja excluir este produto?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="inline-flex items-center justify-center rounded-md border border-red-200 p-2 text-red-700 hover:bg-red-50 transition"
                                                title="Excluir">
                                            <x-heroicon-o-trash class="w-5 h-5" />
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="13" class="px-4 py-10 text-center text-sm text-gray-500">
                                Nenhum produto encontrado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Paginação --}}
        @if(method_exists($produtos, 'links'))
            <div class="flex justify-end">
                {{ $produtos->appends(request()->only('q','sort','dir','per_page'))->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
