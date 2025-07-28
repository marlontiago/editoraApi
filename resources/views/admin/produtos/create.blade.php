<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Novo Produto</h2>
    </x-slot>

    <div class="p-6">
        <form action="{{ route('admin.produtos.store') }}" method="POST">
            @csrf

            <div class="mb-4">
                <label class="block">Nome</label>
                <input type="text" name="nome" class="w-full border rounded px-3 py-2" required>
            </div>

            <div class="mb-4">
                <label class="block">Descrição</label>
                <textarea name="descricao" class="w-full border rounded px-3 py-2"></textarea>
            </div>

            <div class="mb-4">
                <label class="block">Preço</label>
                <input type="number" step="0.01" name="preco" class="w-full border rounded px-3 py-2" required>
            </div>

            <div class="mb-4">
                <label class="block">Estoque</label>
                <input type="number" name="estoque" class="w-full border rounded px-3 py-2" required>
            </div>

            <button type="submit" class="text-black border px-4 py-2 rounded">Salvar</button>
            <a href="{{ route('admin.produtos.index') }}" class="ml-2 text-gray-600 border px-4 py-2 rounded">Cancelar</a>
        </form>
    </div>
</x-app-layout>