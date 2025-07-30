<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Cadastrar Novo Produto</h2>
    </x-slot>

    <div class="p-6 max-w-xl mt-6 mx-auto bg-white rounded shadow space-y-6">
        @if ($errors->any())
            <div class="p-4 bg-red-100 text-red-800 rounded">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.produtos.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf

            <div>
                <label for="nome" class="block text-sm font-medium text-gray-700">Nome</label>
                <input type="text" name="nome" id="nome" class="form-input mt-1 block w-full" required>
            </div>

            <div>
                <label for="descricao" class="block text-sm font-medium text-gray-700">Descrição</label>
                <textarea name="descricao" id="descricao" rows="3" class="form-input mt-1 block w-full"></textarea>
            </div>

            <div>
                <label for="preco" class="block text-sm font-medium text-gray-700">Preço</label>
                <input type="number" step="0.01" name="preco" id="preco" class="form-input mt-1 block w-full" required>
            </div>

            <div>
                <label for="quantidade_estoque" class="block text-sm font-medium text-gray-700">Quantidade em Estoque</label>
                <input type="number" name="quantidade_estoque" id="quantidade_estoque" class="form-input mt-1 block w-full" required>
            </div>

            <div>
                <label for="imagem" class="block text-sm font-medium text-gray-700">Imagem do Produto</label>
                <input type="file" name="imagem" id="imagem" class="form-input mt-1 block w-full">
            </div>

            <div>
                <button type="submit"
                        class="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                    Salvar Produto
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
