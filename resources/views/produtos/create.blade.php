<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Novo Produto') }}
        </h2>
    </x-slot>

    <div class="max-w-xl mx-auto p-4">
        @if ($errors->any())
            <div class="bg-red-100 text-red-800 p-3 rounded mb-4">
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
                <label class="block">Nome:</label>
                <input type="text" name="nome" class="w-full border rounded p-2" required>
            </div>

            <div>
                <label class="block">Descrição:</label>
                <textarea name="descricao" class="w-full border rounded p-2"></textarea>
            </div>

            <div>
                <label class="block">Preço:</label>
                <input type="number" step="0.01" name="preco" class="w-full border rounded p-2" required>
            </div>

            <div>
                <label class="block">Quantidade em Estoque:</label>
                <input type="number" name="quantidade_estoque" class="w-full border rounded p-2" required>
            </div>

            <div>
                <label class="block">Imagem (opcional):</label>
                <input type="file" name="imagem" class="w-full">
            </div>

            <button type="submit" class="bg-black border text-black p-2 mt-2 rounded">Salvar</button>
        </form>
    </div>
</x-app-layout>
