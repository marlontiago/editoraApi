<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Lista de Produtos</h2>
    </x-slot>

    <div class="max-w-full mx-auto p-6 space-y-6">

        {{-- Flash --}}
        @if (session('success'))
            <div class="rounded-md border border-green-300 bg-green-50 p-4 text-green-800">
                {{ session('success') }}
            </div>
        @endif

               {{-- Produtos com estoque baixo --}}
        @if($produtosComEstoqueBaixo->isNotEmpty())
            <div x-data="{ open: false }" class="bg-red-100 p-3 rounded shadow mb-4">
                <button @click="open = !open" class="font-semibold flex items-center gap-2 w-full text-left">
                    ⚠️ Produtos com estoque baixo 
                    <span class="ml-auto text-sm text-red-600">({{ $produtosComEstoqueBaixo->count() }} itens)</span>
                </button>
                <ul x-show="open" x-transition 
                    class="mt-2 list-disc pl-5 text-sm text-gray-700 space-y-1">
                    @foreach($produtosComEstoqueBaixo as $produto)
                        <li>
                            {{ $produto->titulo }} 
                            <span class="text-red-600 font-semibold">
                                : {{ $produto->quantidade_estoque ?? 'vazio'}} em estoque
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Estoque em pedidos em potencial --}}
        @if($estoqueParaPedidosEmPotencial->isNotEmpty())
            <div x-data="{ open: false }" class="bg-yellow-100 p-3 rounded shadow">
                <button @click="open = !open" class="font-semibold flex items-center gap-2 w-full text-left">
                    ⚠️ Estoque em risco para pedidos futuros
                    <span class="ml-auto text-sm text-yellow-700">({{ $estoqueParaPedidosEmPotencial->count() }} itens)</span>
                </button>
                <ul x-show="open" x-transition 
                    class="mt-2 list-disc pl-5 text-sm text-gray-700 space-y-2">
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

        {{-- Toolbar: busca + novo + coleções --}}
<div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
    <form method="GET" action="{{ route('admin.produtos.index') }}" class="flex items-center gap-2">
        <input
            type="text"
            name="q"
            value="{{ request('q') }}"
            placeholder="Buscar por nome, título, autores, coleção..."
            class="h-10 w-72 max-w-full rounded-md border-gray-300 px-3 text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
        >
        <button type="submit"
                class="h-10 rounded-md border px-3 text-sm hover:bg-gray-50">
            Buscar
        </button>
        @if(request('q'))
            <a href="{{ route('admin.produtos.index') }}" class="text-sm text-gray-600 hover:underline">Limpar</a>
        @endif
    </form>

    

    <div class="flex items-center gap-2" x-data="{ openColecao:false }">

         <form method="POST"
              action="{{ route('admin.produtos.import') }}"
              enctype="multipart/form-data"
              class="flex items-center gap-2">
            @csrf

            <label class="inline-flex items-center justify-center rounded-md border text-white border-gray-300 bg-green-600 px-3 h-10 text-sm hover:bg-green-700  cursor-pointer">
                Importar produtos
                <input type="file"
                       name="arquivo"
                       accept=".xlsx,.xls,.csv"
                       class="hidden"
                       onchange="this.form.submit()">
            </label>
        </form>
        <a href="{{ route('admin.produtos.create') }}"
           class="inline-flex h-10 items-center justify-center rounded-md bg-blue-600 px-4 text-sm font-medium text-white hover:bg-blue-700">
            Novo Produto
        </a>

        <button type="button"
                @click="openColecao = true"
                class="inline-flex h-10 items-center justify-center rounded-md bg-indigo-600 px-4 text-sm font-medium text-white hover:bg-indigo-700">
            Gerenciar Coleções
        </button>

        {{-- Modal Coleções --}}
<div x-show="openColecao" x-transition
     class="fixed inset-0 z-50 flex items-center justify-center p-4"
     style="display:none">
    <div class="absolute inset-0 bg-black/40" @click="openColecao=false"></div>

    <div class="relative z-10 w-full max-w-3xl rounded-xl bg-white shadow-xl">
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
        // recebe do backend
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
            // base: /admin/colecoes/0 -> substitui 0 pelo id
            const base = @json(route('admin.colecoes.destroy', ['colecao' => 0]));
            return base.replace(/0$/, String(id));
        }
    }
}
</script>

    </div>
</div>

        {{-- Tabela --}}
        <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow">
            <table class="min-w-[1000px] w-full text-sm">
                <thead class="bg-gray-50 text-gray-700">
                    <tr class="text-left text-xs font-semibold uppercase tracking-wide">
                        <th class="px-3 py-2 hidden md:table-cell">Cód.</th>
                        <th class="px-3 py-2">Imagem</th>
                        <th class="px-3 py-2 hidden md:table-cell">Título</th>
                        <th class="px-3 py-2 hidden lg:table-cell">Coleção</th>
                        <th class="px-3 py-2 hidden lg:table-cell">ISBN</th>
                        <th class="px-3 py-2 hidden xl:table-cell">Autores</th>
                        <th class="px-3 py-2 hidden xl:table-cell">Edição</th>
                        <th class="px-3 py-2 hidden lg:table-cell">Ano</th>
                        <th class="px-3 py-2 hidden xl:table-cell">Páginas</th>
                        <th class="px-3 py-2 hidden lg:table-cell">Ano Escolar</th>
                        <th class="px-3 py-2 whitespace-nowrap">Preço</th>
                        <th class="px-3 py-2">Estoque</th>
                        <th class="px-3 py-2 text-center">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($produtos as $produto)
                        <tr class="odd:bg-white even:bg-gray-50 hover:bg-gray-100/70">
                            {{-- Código --}}
                            <td class="px-3 py-2 hidden md:table-cell">{{ $produto->codigo }}</td>
                            {{-- Imagem --}}
                            <td class="px-3 py-2">
                                @if ($produto->imagem_url)
                                    <img src="{{ $produto->imagem_url }}" alt="{{ $produto->nome }}"
                                        class="h-12 w-12 rounded object-cover ring-1 ring-gray-200">
                                @else
                                    <div class="h-12 w-12 rounded bg-gray-100 grid place-items-center text-[10px] text-gray-500 ring-1 ring-gray-200">
                                        sem<br>imagem
                                    </div>
                                @endif
                            </td>

                            <td class="px-3 py-2 hidden md:table-cell">{{ $produto->titulo ?? '—' }}</td>
                            <td class="px-3 py-2 hidden lg:table-cell">{{ $produto->colecao?->nome ?? '—' }}</td>
                           <td class="px-3 py-2 hidden lg:table-cell">{{ $produto->isbn }}</td>
                            <td class="px-3 py-2 hidden xl:table-cell">
                                <span class="line-clamp-2">{{ $produto->autores ?? '—' }}</span>
                            </td>
                            <td class="px-3 py-2 hidden xl:table-cell">{{ $produto->edicao ?? '—' }}</td>
                            <td class="px-3 py-2 hidden lg:table-cell">{{ $produto->ano ?? '—' }}</td>
                            <td class="px-3 py-2 hidden xl:table-cell">{{ $produto->numero_paginas ?? '—' }}</td>
                            
                            <td class="px-3 py-2 hidden lg:table-cell">
                                @if($produto->ano_escolar)
                                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-700">
                                        {{ $produto->ano_escolar }}
                                    </span>
                                @else
                                    —
                                @endif
                            </td>

                            {{-- Preço / Estoque (sempre visíveis) --}}
                            <td class="px-3 py-2 font-semibold whitespace-nowrap">
                                R$ {{ number_format((float)$produto->preco, 2, ',', '.') }}
                            </td>
                            <td class="px-3 py-2">{{ $produto->quantidade_estoque }}</td>

                            {{-- Ações --}}
                            <td class="px-3 py-2">
    <div class="flex items-center justify-center gap-2">
        {{-- Editar --}}
        <a href="{{ route('admin.produtos.edit', $produto) }}"
           class="inline-flex items-center justify-center rounded-md border border-blue-200 p-2 text-blue-700 hover:bg-blue-50"
           title="Editar">
            <x-heroicon-o-pencil-square class="w-5 h-5" />
        </a>

        {{-- Excluir --}}
        <form action="{{ route('admin.produtos.destroy', $produto) }}" method="POST" class="inline"
              onsubmit="return confirm('Tem certeza que deseja excluir este produto?');">
            @csrf
            @method('DELETE')
            <button type="submit"
                    class="inline-flex items-center justify-center rounded-md border border-red-200 p-2 text-red-700 hover:bg-red-50"
                    title="Excluir">
                <x-heroicon-o-trash class="w-5 h-5" />
            </button>
        </form>
    </div>
</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="13" class="px-3 py-6 text-center text-gray-500">Nenhum produto encontrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Paginação (se usar LengthAwarePaginator) --}}
        @if(method_exists($produtos, 'links'))
            <div class="flex justify-end">
                {{ $produtos->appends(request()->only('q'))->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
