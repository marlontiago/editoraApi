<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Lista de Produtos</h2>
    </x-slot>

    <div class="p-6 space-y-6">
        @if (session('success'))
            <div class="p-4 bg-green-100 text-green-800 rounded">
                {{ session('success') }}
            </div>
        @endif

        <div class="mb-4">
            <a href="{{ route('admin.produtos.create') }}"
               class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                Novo Produto
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border rounded shadow text-sm">
                <thead class="bg-gray-200">
                    <tr>
                        <th class="p-2 border">Imagem</th>
                        <th class="p-2 border">Nome</th>
                        <th class="p-2 border">Título</th>
                        <th class="p-2 border">Coleção</th>
                        <th class="p-2 border">ISBN</th>
                        <th class="p-2 border">Autores</th>
                        <th class="p-2 border">Edição</th>
                        <th class="p-2 border">Ano</th>
                        <th class="p-2 border">Nº Páginas</th>
                        <th class="p-2 border">Peso (kg)</th>
                        <th class="p-2 border">Ano Escolar</th>
                        <th class="p-2 border">Preço</th>
                        <th class="p-2 border">Estoque</th>
                        <th class="p-2 border">Editar</th>
                        <th class="p-2 border">Excluir</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($produtos as $produto)
                        <tr class="border-b">
                            <td class="p-2 text-center">
                                @if ($produto->imagem && Storage::disk('public')->exists($produto->imagem))
                                    <img src="{{ asset('storage/' . $produto->imagem) }}"
                                         alt="{{ $produto->nome }}"
                                         class="w-16 h-16 object-cover rounded mx-auto">
                                @else
                                    <span class="text-gray-500">Sem imagem</span>
                                @endif
                            </td>
                            <td class="p-2">{{ $produto->nome }}</td>
                            <td class="p-2">{{ $produto->titulo ?? '—' }}</td>
                            <td class="p-2">{{ $produto->colecao->nome ?? '—' }}</td>
                            <td class="p-2">{{ $produto->isbn ?? '—' }}</td>
                            <td class="p-2">{{ $produto->autores ?? '—' }}</td>
                            <td class="p-2">{{ $produto->edicao ?? '—' }}</td>
                            <td class="p-2">{{ $produto->ano ?? '—' }}</td>
                            <td class="p-2">{{ $produto->numero_paginas ?? '—' }}</td>
                            <td class="p-2">{{ $produto->peso ? number_format($produto->peso, 3, ',', '.') : '—' }}</td>
                            <td class="p-2">{{ $produto->ano_escolar ?? '—' }}</td>
                            <td class="p-2">R$ {{ number_format($produto->preco, 2, ',', '.') }}</td>
                            <td class="p-2">{{ $produto->quantidade_estoque }}</td>
                            <td class="p-2 text-blue-600 hover:underline">
                                <a href="{{ route('admin.produtos.edit', $produto) }}">Editar</a>
                            </td>
                            <td class="p-2 text-red-600">
                                <form action="{{ route('admin.produtos.destroy', $produto) }}" method="POST" class="inline"
                                      onsubmit="return confirm('Tem certeza que deseja excluir este produto?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="hover:underline">Excluir</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="15" class="p-4 text-center text-gray-500">Nenhum produto encontrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
