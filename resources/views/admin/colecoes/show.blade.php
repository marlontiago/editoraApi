<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">
                Coleção: {{ $colecao->nome }}
            </h2>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.colecoes.edit', $colecao) }}"
                   class="inline-flex h-9 items-center justify-center rounded-md border px-3 text-sm font-medium hover:bg-gray-50">
                    Editar Coleção
                </a>
                <a href="{{ route('admin.colecoes.index') }}"
                   class="inline-flex h-9 items-center justify-center rounded-md border px-3 text-sm font-medium hover:bg-gray-50">
                    Voltar
                </a>
            </div>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto p-6 space-y-6">
        {{-- Busca --}}
        <form method="GET" class="flex items-center gap-2">
            <input type="text" name="q" value="{{ $q }}" placeholder="Buscar por título ou ISBN…"
                   class="h-9 w-64 max-w-full rounded-md border-gray-300 px-3 text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500">
            <button type="submit" class="h-9 rounded-md border px-3 text-sm hover:bg-gray-50">Buscar</button>
            @if($q)
                <a href="{{ route('admin.colecoes.show', $colecao) }}" class="text-sm text-gray-600 hover:underline">Limpar</a>
            @endif
        </form>

        {{-- Grid de produtos --}}
        @if ($produtos->count())
            <div class="grid gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
                @foreach ($produtos as $p)
                    <div class="rounded-lg border bg-white shadow-sm overflow-hidden hover:shadow-md transition-shadow duration-200">
                        <div class="aspect-square bg-gray-50 grid place-items-center">
                            @if ($p->imagem_url)
                                <img src="{{ $p->imagem_url }}" alt="{{ $p->titulo ?? 'Produto' }}"
                                     class="h-full w-full object-cover">
                            @else
                                <div class="text-[10px] text-gray-500 text-center leading-tight">sem<br>imagem</div>
                            @endif
                        </div>

                        <div class="p-2 space-y-1">
                            <div class="font-medium text-sm line-clamp-2 leading-snug">{{ $p->titulo ?? '—' }}</div>
                            <div class="text-[11px] text-gray-600 truncate">ISBN: {{ $p->isbn ?? '—' }}</div>

                            <div class="flex items-center justify-between pt-1">
                                <div class="font-semibold text-[13px]">
                                    R$ {{ number_format((float)($p->preco ?? 0), 2, ',', '.') }}
                                </div>
                                <div class="text-[11px] text-gray-600">
                                    Estoque: <span class="font-semibold text-gray-800">{{ $p->quantidade_estoque ?? 0 }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="p-2 border-t">
                            <a href="{{ route('admin.produtos.edit', $p) }}"
                               class="block w-full text-center rounded-md border px-2 py-1 text-[11px] font-medium text-blue-700 border-blue-200 hover:bg-blue-50 transition">
                                Editar produto
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Paginação --}}
            <div class="flex justify-end pt-4">
                {{ $produtos->links() }}
            </div>
        @else
            <div class="rounded-md border border-gray-200 bg-white p-6 text-center text-gray-600">
                Nenhum produto nesta coleção.
            </div>
        @endif
    </div>
</x-app-layout>
