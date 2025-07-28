<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Registrar Venda</h2>
    </x-slot>

    <div class="p-6">
        <form method="POST" action="{{ route('distribuidor.vendas.store') }}">
            @csrf
            <div id="produtos" class="space-y-4">
                <div class="produto flex gap-4">
                    <select name="produtos[0][id]" class="form-select w-full" required>
                        <option value="">Selecione um produto</option>
                        @foreach($produtos as $produto)
                            <option value="{{ $produto->id }}">{{ $produto->nome }} - R$ {{ number_format($produto->preco, 2, ',', '.') }}</option>
                        @endforeach
                    </select>
                    <input type="number" name="produtos[0][quantidade]" class="form-input w-24" placeholder="Qtd" min="1" required>
                </div>
            </div>

            <button type="button" onclick="addProduto()" class="mt-4 px-4 py-2 text-black border rounded">+ Produto</button>

            <button type="submit" class="mt-4 px-6 py-2 bg-green-600 text-white rounded hover:bg-green-700">Salvar Venda</button>
        </form>
    </div>

    <script>
        let count = 1;
        function addProduto() {
            const container = document.getElementById('produtos');
            const div = document.createElement('div');
            div.className = 'produto flex gap-4 mt-2';
            div.innerHTML = `{!! str_replace(["\n", "'"], ["", "\\'"], view('components.produto-linha', compact('produtos'))->render()) !!}`.replace(/__INDEX__/g, count);
            container.appendChild(div);
            count++;
        }
    </script>
</x-app-layout>
