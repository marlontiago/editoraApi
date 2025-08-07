<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Criar Novo Pedido</h2>
    </x-slot>

    <div class="p-6 space-y-6">
        @if(session('success'))
            <div class="bg-green-100 text-green-800 p-4 rounded">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="bg-red-100 text-red-800 p-4 rounded">
                <ul class="list-disc pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.pedidos.store') }}" method="POST">
            @csrf

            <!-- Gestor -->
            <div class="mt-4">
                <label for="gestor_id" class="block font-semibold">Gestor</label>
                <select name="gestor_id" id="gestor_id" class="w-full border rounded">
                    <option value="">-- Selecione --</option>
                    @foreach($gestores as $gestor)
                        <option value="{{ $gestor->id }}">{{ $gestor->razao_social }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Distribuidor -->
            <div class="mt-4">
                <label for="distribuidor_id" class="block font-semibold">Distribuidor</label>
                <select name="distribuidor_id" id="distribuidor_id" class="w-full border rounded">
                    <option value="">-- Selecione --</option>
                    @foreach($distribuidores as $distribuidor)
                        <option value="{{ $distribuidor->id }}">{{ $distribuidor->user->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Cidades associadas -->
            <div class="mt-4" id="cidade-container">
                <label class="block font-semibold">Cidades Associadas</label>
                <textarea id="cidade_nome" class="w-full border rounded bg-gray-100 text-sm p-2" rows="2" readonly></textarea>
                <input type="hidden" name="cidade_id[]" id="cidade_id">
            </div>

            <!-- Desconto -->
            <div class="mt-4">
                <label for="desconto" class="block font-semibold">Desconto Geral (%)</label>
                <input type="number" name="desconto" id="desconto" class="w-full border rounded" min="0" max="100" step="0.01" value="0">
            </div>

            <!-- Data -->
            <div class="mt-4">
                <label for="data" class="block font-semibold">Data</label>
                <input type="date" name="data" id="data" class="w-full border rounded" required>
            </div>

            <!-- Produtos -->
            <div class="mt-6 space-y-4" id="produtos-container">
                <div class="produto border p-4 rounded bg-gray-100">
                    <label class="block font-semibold">Produto</label>
                    <select name="produtos[0][id]" class="w-full border rounded">
                        @foreach($produtos as $produto)
                            <option value="{{ $produto->id }}">{{ $produto->nome }}</option>
                        @endforeach
                    </select>

                    <label class="block mt-2 font-semibold">Quantidade</label>
                    <input type="number" name="produtos[0][quantidade]" class="w-full border rounded" required>
                </div>
            </div>

            <button type="button" onclick="adicionarProduto()" class="mt-4 bg-blue-500 text-white px-4 py-2 rounded">+ Adicionar Produto</button>

            <div class="mt-6">
                <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700">Salvar Pedido</button>
            </div>
        </form>
    </div>

    <script>
        let produtoIndex = 1;
        function adicionarProduto() {
            const container = document.getElementById('produtos-container');
            const novo = document.createElement('div');
            novo.classList.add('produto', 'border', 'p-4', 'rounded', 'bg-gray-100', 'mt-4');
            novo.innerHTML = `
                <label class="block font-semibold">Produto</label>
                <select name="produtos[${produtoIndex}][id]" class="w-full border rounded">
                    @foreach($produtos as $produto)
                        <option value="{{ $produto->id }}">{{ $produto->nome }}</option>
                    @endforeach
                </select>

                <label class="block mt-2 font-semibold">Quantidade</label>
                <input type="number" name="produtos[${produtoIndex}][quantidade]" class="w-full border rounded" required>
            `;
            container.appendChild(novo);
            produtoIndex++;
        }

        document.getElementById('gestor_id').addEventListener('change', function () {
            const gestorId = this.value;
            if (gestorId) {
                fetch(`/admin/cidades/por-gestor/${gestorId}`)
                    .then(res => res.json())
                    .then(cidades => preencherCidades(cidades));
            }
        });

        document.getElementById('distribuidor_id').addEventListener('change', function () {
            const distribuidorId = this.value;
            if (distribuidorId) {
                fetch(`/admin/cidades/por-distribuidor/${distribuidorId}`)
                    .then(res => res.json())
                    .then(cidades => preencherCidades(cidades));
            }
        });

        function preencherCidades(cidades) {
            const cidadeTextarea = document.getElementById('cidade_nome');
            const cidadeInput = document.getElementById('cidade_id');

            if (cidades.length > 0) {
                const nomes = cidades.map(c => c.name).join(', ');
                const ids = cidades.map(c => c.id).join(',');
                cidadeTextarea.value = nomes;
                cidadeInput.value = ids;
            } else {
                cidadeTextarea.value = 'Nenhuma cidade encontrada.';
                cidadeInput.value = '';
            }
        }
    </script>
</x-app-layout>
