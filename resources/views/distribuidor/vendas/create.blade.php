<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Registrar Venda</h2>
    </x-slot>

    <div class="max-w-xl mx-auto p-6">
        @if ($errors->any())
            <div class="bg-red-100 text-red-800 p-3 rounded mb-4">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('distribuidor.vendas.store') }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label class="block mb-1">Produto:</label>
                <select name="produto_id" class="w-full border rounded p-2" required>
                    <option value="">Selecione…</option>
                    @foreach ($produtos as $produto)
                        <option value="{{ $produto->id }}">
                            {{ $produto->nome }} — R$ {{ number_format($produto->preco, 2, ',', '.') }} (Estoque: {{ $produto->quantidade_estoque }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block mb-1">Quantidade:</label>
                <input type="number" name="quantidade" min="1" class="w-full border rounded p-2" required>
            </div>

            <button class=" text-black border mt-2 px-4 py-2 rounded" type="submit">Salvar</button>
        </form>
    </div>
</x-app-layout>
