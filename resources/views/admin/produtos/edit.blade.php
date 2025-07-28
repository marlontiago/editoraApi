<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Editar Produto</h2>
    </x-slot>

    <div class="p-6">
        <form action="{{ route('admin.produtos.update', $produto) }}" method="POST">
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
                <input type="number" name="estoque" value="{{ $produto->estoque }}" class="w-full border rounded px-3 py-2" required>
            </div>

            <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded">Atualizar</button>
            <a href="{{ route('admin.produtos.index') }}" class="ml-2 text-gray-600">Cancelar</a>
        </form>
    </div>
</x-app-layout>