<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Produtos') }}
        </h2>
    </x-slot>

    <div class="max-w-6xl mx-auto p-4">
        @if(session('success'))
            <div class="bg-green-100 text-green-800 p-2 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        <a href="{{ route('admin.produtos.create') }}" class="bg-blue-500 text-black border px-4 py-2 rounded mb-4 inline-block">Novo Produto</a>

        <table class="w-full border text-left mt-4">
            <thead class="bg-gray-200">
                <tr>
                    <th class="p-2">Imagem</th>
                    <th class="p-2">Nome</th>
                    <th class="p-2">Preço</th>
                    <th class="p-2">Estoque</th>
                    <th class="p-2">Ações</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($produtos as $produto)
                    <tr class="border-t">
                        <td class="p-2">
                            @if($produto->imagem)
                                <img src="{{ asset('storage/' . $produto->imagem) }}" class="w-16 h-16 object-cover rounded">
                            @else
                                <span>–</span>
                            @endif
                        </td>
                        <td class="p-2">{{ $produto->nome }}</td>
                        <td class="p-2">R$ {{ number_format($produto->preco, 2, ',', '.') }}</td>
                        <td class="p-2">{{ $produto->quantidade_estoque }}</td>
                        <td class="p-2">
                            <a href="{{ route('admin.produtos.edit', $produto) }}" class="text-blue-600">Editar</a> |
                            <form action="{{ route('admin.produtos.destroy', $produto) }}" method="POST" class="inline"
                                  onsubmit="return confirm('Tem certeza que deseja excluir?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600">Excluir</button>
                                
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mt-4">
            {{ $produtos->links() }}
        </div>
    </div>
</x-app-layout>
