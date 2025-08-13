<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Criar Novo Pedido</h2>
    </x-slot>

    <div class="max-w-6xl mx-auto p-6">
        {{-- Mensagens de sucesso --}}
        @if(session('success'))
            <div class="mb-6 rounded-md border border-green-300 bg-green-50 p-4 text-green-800">
                {{ session('success') }}
            </div>
        @endif

        {{-- Erros de validação --}}
        @if($errors->any())
            <div class="mb-6 rounded-md border border-red-300 bg-red-50 p-4 text-red-800">
                <ul class="list-disc pl-5 space-y-1">
                    @foreach($errors->all() as $error)
                        <li class="text-sm">{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.pedidos.store') }}" method="POST"
              class="bg-white shadow rounded-lg p-6 grid grid-cols-12 gap-4">
            @csrf

            {{-- Gestor --}}
            <div class="col-span-12 md:col-span-6">
                <label for="gestor_id" class="block text-sm font-medium text-gray-700">Gestor</label>
                <select name="gestor_id" id="gestor_id"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">-- Selecione --</option>
                    @foreach($gestores as $gestor)
                        <option value="{{ $gestor->id }}" @selected(old('gestor_id') == $gestor->id)>{{ $gestor->razao_social }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Distribuidor (dependente do gestor) --}}
            <div class="col-span-12 md:col-span-6">
                <label for="distribuidor_id" class="block text-sm font-medium text-gray-700">Distribuidor</label>
                <select name="distribuidor_id" id="distribuidor_id" disabled
                        class="mt-1 block w-full rounded-md border-gray-300 bg-gray-50 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">-- Selecione o gestor primeiro --</option>
                </select>
            </div>


            {{-- Desconto --}}
            <div class="col-span-12 md:col-span-6">
                <label for="desconto" class="block text-sm font-medium text-gray-700">Desconto Geral (%)</label>
                <input type="number" name="desconto" id="desconto" min="0" max="100" step="0.01" value="0"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>

            {{-- Data --}}
            <div class="col-span-12 md:col-span-6">
                <label for="data" class="block text-sm font-medium text-gray-700">Data</label>
                <input type="date" name="data" id="data"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
            </div>

            {{-- Produtos --}}
            <div class="col-span-12">
                <label class="block text-sm font-medium text-gray-700">Produtos</label>
                <div id="produtos-container" class="space-y-4 mt-2">
                    <div class="produto border p-4 rounded-md bg-gray-50 grid grid-cols-12 gap-4">
                        <div class="col-span-12 md:col-span-8">
                            <label class="block text-sm font-medium text-gray-700">Produto</label>
                            <select name="produtos[0][id]"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                @foreach($produtos as $produto)
                                    <option value="{{ $produto->id }}">{{ $produto->nome }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-span-12 md:col-span-4">
                            <label class="block text-sm font-medium text-gray-700">Quantidade</label>
                            <input type="number" name="produtos[0][quantidade]"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                        </div>
                    </div>
                </div>

                <button type="button" onclick="adicionarProduto()"
                        class="mt-3 inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                    + Adicionar Produto
                </button>
            </div>

            {{-- Botões --}}
            <div class="col-span-12 flex justify-end gap-3 pt-4">
                <a href="{{ route('admin.pedidos.index') }}"
                   class="inline-flex h-10 items-center rounded-md border px-4 text-sm hover:bg-gray-50">
                    Cancelar
                </a>
                <button type="submit"
                        class="inline-flex h-10 items-center rounded-md bg-green-600 px-5 text-sm font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                    Salvar Pedido
                </button>
            </div>
        </form>
    </div>

    {{-- JS --}}
    <script>
    let produtoIndex = 1;

    function adicionarProduto() {
        const container = document.getElementById('produtos-container');
        const novo = document.createElement('div');
        novo.className = 'produto border p-4 rounded-md bg-gray-50 grid grid-cols-12 gap-4 mt-4';
        novo.innerHTML = `
            <div class="col-span-12 md:col-span-8">
                <label class="block text-sm font-medium text-gray-700">Produto</label>
                <select name="produtos[${produtoIndex}][id]"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @foreach($produtos as $produto)
                        <option value="{{ $produto->id }}">{{ $produto->nome }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-span-12 md:col-span-4">
                <label class="block text-sm font-medium text-gray-700">Quantidade</label>
                <input type="number" name="produtos[${produtoIndex}][quantidade]"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
            </div>`;
        container.appendChild(novo);
        produtoIndex++;
    }

    const gestorSelect       = document.getElementById('gestor_id');
    const distribuidorSelect = document.getElementById('distribuidor_id');

    async function carregarDistribuidores(gestorId, selectedId = null) {
        // Reseta select
        distribuidorSelect.innerHTML = '';
        distribuidorSelect.disabled  = true;
        distribuidorSelect.classList.add('bg-gray-50');

        if (!gestorId) {
            const opt = new Option('-- Selecione o gestor primeiro --', '');
            distribuidorSelect.add(opt);
            return;
        }

        try {
            const url  = `{{ route('admin.admin.distribuidores.por-gestor', ':id') }}`.replace(':id', gestorId);
            const resp = await fetch(url, { credentials: 'same-origin' });
            if (!resp.ok) throw new Error(`HTTP ${resp.status}`);

            const data = await resp.json(); // [{id, text}]
            // Preenche
            distribuidorSelect.add(new Option('-- Selecione --', ''));
            data.forEach(item => {
                const opt = new Option(item.text, item.id);
                if (selectedId && String(selectedId) === String(item.id)) opt.selected = true;
                distribuidorSelect.add(opt);
            });

            distribuidorSelect.disabled = false;
            distribuidorSelect.classList.remove('bg-gray-50');
        } catch (e) {
            console.error(e);
            distribuidorSelect.add(new Option('Falha ao carregar distribuidores', ''));
        }
    }

    // Mantém sua lógica de cidades:
    function preencherCidades(cidades) {
        const cidadeTextarea = document.getElementById('cidade_nome');
        const cidadeInput    = document.getElementById('cidade_id');
        if (Array.isArray(cidades) && cidades.length > 0) {
            cidadeTextarea.value = cidades.map(c => c.name).join(', ');
            cidadeInput.value    = cidades.map(c => c.id).join(',');
        } else {
            cidadeTextarea.value = 'Nenhuma cidade encontrada.';
            cidadeInput.value    = '';
        }
    }

    // Eventos
    gestorSelect.addEventListener('change', function () {
        const gestorId = this.value;

        // 1) Carrega distribuidores do gestor
        carregarDistribuidores(gestorId);

        // 2) Carrega cidades pelo gestor (sua rota atual)
        if (gestorId) {
            fetch(`/admin/cidades/por-gestor/${gestorId}`)
                .then(r => r.json())
                .then(preencherCidades)
                .catch(() => preencherCidades([]));
        } else {
            preencherCidades([]);
        }
    });

    distribuidorSelect.addEventListener('change', function () {
        const distribuidorId = this.value;
        if (distribuidorId) {
            fetch(`/admin/cidades/por-distribuidor/${distribuidorId}`)
                .then(r => r.json())
                .then(preencherCidades)
                .catch(() => preencherCidades([]));
        }
    });

    // Estado inicial (ex.: retorno com erro de validação)
    document.addEventListener('DOMContentLoaded', () => {
        const oldGestor        = @json(old('gestor_id'));
        const oldDistribuidor  = @json(old('distribuidor_id'));
        if (oldGestor) {
            carregarDistribuidores(oldGestor, oldDistribuidor);
            // também recarrega cidades do gestor escolhido
            fetch(`/admin/cidades/por-gestor/${oldGestor}`)
                .then(r => r.json())
                .then(preencherCidades)
                .catch(() => preencherCidades([]));
        }
    });
</script>

</x-app-layout>
