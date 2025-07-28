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
            <table class="min-w-full bg-white border rounded shadow">
                <thead class="bg-gray-200">
                    <tr>
                        <th class="p-2 border text-left">Imagem</th>
                        <th class="p-2 border text-left">Nome</th>
                        <th class="p-2 border text-left">Preço</th>
                        <th class="p-2 border text-left">Estoque</th>
                        <th class="p-2 border text-left">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($produtos as $produto)
                        <tr class="border-b">
                            <td class="p-2">
                                @if ($produto->imagem && Storage::disk('public')->exists($produto->imagem))
                                    <img src="{{ asset('storage/' . $produto->imagem) }}"
                                         alt="{{ $produto->nome }}"
                                         class="w-16 h-16 object-cover rounded">
                                @else
                                    <span class="text-gray-500">Sem imagem</span>
                                @endif
                            </td>
                            <td class="p-2">{{ $produto->nome }}</td>
                            <td class="p-2">R$ {{ number_format($produto->preco, 2, ',', '.') }}</td>
                            <td class="p-2">{{ $produto->quantidade_estoque }}</td>
                            <td class="p-2">
                                <a href="{{ route('admin.produtos.edit', $produto) }}"
                                   class="text-blue-600 hover:underline">Editar</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-4 text-center text-gray-500">Nenhum produto encontrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
