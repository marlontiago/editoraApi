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
                            {{ $produto->nome }} 
                            <span class="text-red-600 font-semibold">
                                - {{ $produto->quantidade_estoque }} em estoque
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
                            Produto: <strong>{{ $produto->nome }}</strong> <br>
                            Em pedidos: <span class="font-medium text-green-700">{{ $produto->qtd_em_pedidos }}</span> <br>
                            Disponível: <span class="font-medium text-red-600">{{ $produto->quantidade_estoque }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Toolbar: busca + novo --}}
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

            <a href="{{ route('admin.produtos.create') }}"
            class="inline-flex h-10 items-center justify-center rounded-md bg-blue-600 px-4 text-sm font-medium text-white hover:bg-blue-700">
                Novo Produto
            </a>
        </div>

        {{-- Tabela --}}
        <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow">
            <table class="min-w-[1000px] w-full text-sm">
                <thead class="bg-gray-50 text-gray-700">
                    <tr class="text-left text-xs font-semibold uppercase tracking-wide">
                        <th class="px-3 py-2">Imagem</th>
                        <th class="px-3 py-2">Nome</th>
                        <th class="px-3 py-2 hidden md:table-cell">Título</th>
                        <th class="px-3 py-2 hidden lg:table-cell">Coleção</th>
                        <th class="px-3 py-2 hidden lg:table-cell">ISBN</th>
                        <th class="px-3 py-2 hidden xl:table-cell">Autores</th>
                        <th class="px-3 py-2 hidden xl:table-cell">Edição</th>
                        <th class="px-3 py-2 hidden lg:table-cell">Ano</th>
                        <th class="px-3 py-2 hidden xl:table-cell">Páginas</th>
                        <th class="px-3 py-2 hidden xl:table-cell">Peso (kg)</th>
                        <th class="px-3 py-2 hidden lg:table-cell">Ano Escolar</th>
                        <th class="px-3 py-2 whitespace-nowrap">Preço</th>
                        <th class="px-3 py-2">Estoque</th>
                        <th class="px-3 py-2 text-center">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($produtos as $produto)
                        <tr class="odd:bg-white even:bg-gray-50 hover:bg-gray-100/70">
                            {{-- Imagem --}}
                            <td class="px-3 py-2">
                                @if ($produto->imagem && Storage::disk('public')->exists($produto->imagem))
                                    <img src="{{ asset('storage/' . $produto->imagem) }}"
                                         alt="{{ $produto->nome }}"
                                         class="h-12 w-12 rounded object-cover ring-1 ring-gray-200">
                                @else
                                    <div class="h-12 w-12 rounded bg-gray-100 grid place-items-center text-[10px] text-gray-500 ring-1 ring-gray-200">
                                        sem<br>imagem
                                    </div>
                                @endif
                            </td>

                            {{-- Nome (sempre visível) --}}
                            <td class="px-3 py-2 align-top">
                                <div class="font-medium text-gray-900">{{ $produto->nome }}</div>
                                <div class="mt-0.5 text-xs text-gray-500 hidden md:block">
                                    {{ $produto->colecao?->nome ?? '—' }}
                                </div>
                            </td>

                            <td class="px-3 py-2 hidden md:table-cell">{{ $produto->titulo ?? '—' }}</td>
                            <td class="px-3 py-2 hidden lg:table-cell">{{ $produto->colecao?->nome ?? '—' }}</td>
                           <td class="px-3 py-2 hidden lg:table-cell">{{ $produto->isbn_formatado }}</td>
                            <td class="px-3 py-2 hidden xl:table-cell">
                                <span class="line-clamp-2">{{ $produto->autores ?? '—' }}</span>
                            </td>
                            <td class="px-3 py-2 hidden xl:table-cell">{{ $produto->edicao ?? '—' }}</td>
                            <td class="px-3 py-2 hidden lg:table-cell">{{ $produto->ano ?? '—' }}</td>
                            <td class="px-3 py-2 hidden xl:table-cell">{{ $produto->numero_paginas ?? '—' }}</td>
                            <td class="px-3 py-2 hidden xl:table-cell">
                                {{ $produto->peso ? number_format($produto->peso, 3, ',', '.') : '—' }}
                            </td>
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
                                    <a href="{{ route('admin.produtos.edit', $produto) }}"
                                       class="inline-flex items-center rounded-md border px-3 py-1.5 text-xs font-medium text-blue-700 border-blue-200 hover:bg-blue-50">
                                        Editar
                                    </a>
                                    <form action="{{ route('admin.produtos.destroy', $produto) }}" method="POST" class="inline"
                                          onsubmit="return confirm('Tem certeza que deseja excluir este produto?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="inline-flex items-center rounded-md border px-3 py-1.5 text-xs font-medium text-red-700 border-red-200 hover:bg-red-50">
                                            Excluir
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
