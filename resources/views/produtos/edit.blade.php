<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Editar Produto') }}
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

        <form action="{{ route('admin.produtos.update', $produto) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block">Nome:</label>
                <input type="text" name="nome" value="{{ $produto->nome }}" class="w-full border rounded p-2" required>
            </div>

            <div>
                <label class="block">Descrição:</label>
                <textarea name="descricao" class="w-full border rounded p-2">{{ $produto->descricao }}</textarea>
            </div>

            <div>
                <label class="block">Preço:</label>
                <input type="number" step="0.01" name="preco" value="{{ $produto->preco }}" class="w-full border rounded p-2" required>
            </div>

            <div>
                <label class="block">Quantidade em Estoque:</label>
                <input type="number" name="quantidade_estoque" value="{{ $produto->quantidade_estoque }}" class="w-full border rounded p-2" required>
            </div>

            <div>
                <label class="block">Imagem Atual:</label>
                @if ($produto->imagem)
                    <img src="{{ asset('storage/' . $produto->imagem) }}" alt="Imagem atual" class="w-32 h-32 object-cover mb-2 rounded border">
                @else
                    <p class="text-sm text-gray-500">Sem imagem.</p>
                @endif
                <input type="file" name="imagem" class="w-full mt-2">
            </div>

            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Atualizar</button>
        </form>
    </div>
</x-app-layout>
