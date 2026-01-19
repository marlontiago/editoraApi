<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Produtos') }}
        </h2>
    </x-slot>

    @php
        $dirCodigo = (request('sort') === 'codigo' && request('dir') === 'asc') ? 'desc' : 'asc';
        $dirTitulo = (request('sort') === 'titulo' && request('dir') === 'asc') ? 'desc' : 'asc';
    @endphp

    <div class="max-w-6xl mx-auto p-4">
        {{-- Flash success --}}
        @if(session('success'))
            <div class="bg-green-100 text-green-800 p-2 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        {{-- Novo produto --}}
        <a
            href="{{ route('admin.produtos.create') }}"
            class="bg-blue-500 text-black border px-4 py-2 rounded mb-4 inline-block"
        >
            Novo Produto
        </a>

        <table class="w-full border text-left mt-4">
            <thead class="bg-gray-200">
                <tr>
                    <th class="p-2">Imagem</th>

                    {{-- CÓD (ordem numérica) --}}
                   <th class="p-2">
    <a href="/admin/produtos?sort=codigo&dir=asc" class="text-blue-700 underline">
        CÓD TESTE
    </a>
</th>

                    {{-- TÍTULO (ordem alfabética) --}}
                    <th class="p-2">
                        <a
                            href="{{ route('admin.produtos.index', array_merge(request()->query(), [
                                'sort' => 'titulo',
                                'dir'  => $dirTitulo
                            ])) }}"
                            class="inline-flex items-center gap-1 text-blue-700 hover:text-blue-900 hover:underline cursor-pointer"
                        >
                            Título
                            @if(request('sort') === 'titulo')
                                <span class="text-xs">
                                    {{ request('dir') === 'asc' ? '▲' : '▼' }}
                                </span>
                            @endif
                        </a>
                    </th>

                    <th class="p-2">Preço</th>
                    <th class="p-2">Estoque</th>
                    <th class="p-2">Ações</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($produtos as $produto)
                    <tr class="border-t">
                        <td class="p-2">
                            @if($produto->imagem)
                                <img
                                    src="{{ asset('storage/' . $produto->imagem) }}"
                                    class="w-16 h-16 object-cover rounded"
                                    alt="Imagem do produto"
                                >
                            @else
                                <span>–</span>
                            @endif
                        </td>

                        <td class="p-2">
                            {{ $produto->codigo }}
                        </td>

                        <td class="p-2">
                            {{ $produto->titulo }}
                        </td>

                        <td class="p-2">
                            R$ {{ number_format($produto->preco ?? 0, 2, ',', '.') }}
                        </td>

                        <td class="p-2">
                            {{ $produto->quantidade_estoque ?? '—' }}
                        </td>

                        <td class="p-2">
                            <a
                                href="{{ route('admin.produtos.edit', $produto) }}"
                                class="text-blue-600 hover:underline"
                            >
                                Editar
                            </a>
                            |
                            <form
                                action="{{ route('admin.produtos.destroy', $produto) }}"
                                method="POST"
                                class="inline"
                                onsubmit="return confirm('Tem certeza que deseja excluir?')"
                            >
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline">
                                    Excluir
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="p-4 text-center text-gray-500">
                            Nenhum produto encontrado.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Paginação --}}
        <div class="mt-4">
            {{ $produtos->links() }}
        </div>
    </div>
</x-app-layout>
