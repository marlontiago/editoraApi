<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Editar Produto</h2>
    </x-slot>

    <div class="p-6">
        <form action="{{ route('admin.produtos.update', $produto) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="mb-4">
                <label class="block">Nome</label>
                <input type="text" name="nome" value="{{ $produto->nome }}" class="w-full border rounded px-3 py-2" required>
            </div>

            <div class="mb-4">
                <label class="block">Descrição</label>
                <textarea name="descricao" class="w-full border rounded px-3 py-2">{{ $produto->descricao }}</textarea>
            </div>

            <div class="mb-4">
                <label class="block">Preço</label>
                <input type="number" step="0.01" name="preco" value="{{ $produto->preco }}" class="w-full border rounded px-3 py-2" required>
            </div>

            <div class="mb-4">
                <label class="block">Estoque</label>
                <input type="number" name="quantidade_estoque" value="{{ old('quantidade_estoque', $produto->quantidade_estoque) }}" class="w-full border rounded px-3 py-2" required>
            </div>

            <div class="mb-4">
                <label for="imagem" class="block text-sm font-medium text-gray-700">Imagem do Produto</label>
                <input type="file" name="imagem" id="imagem" class="form-input mt-1 block w-full">
            </div>

            <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded">Atualizar</button>
            <a href="{{ route('admin.produtos.index') }}" class="ml-2 text-gray-600">Cancelar</a>
        </form>
    </div>
</x-app-layout>