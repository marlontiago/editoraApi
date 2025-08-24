<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Criar Novo Pedido</h2>
    </x-slot>

    <div class="max-w-6xl mx-auto p-6">
        @if(session('success'))
            <div class="mb-6 rounded-md border border-green-300 bg-green-50 p-4 text-green-800">
                {{ session('success') }}
            </div>
        @endif

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

            {{-- Cliente (obrigatório) --}}
            <div class="col-span-12 md:col-span-6">
                <label for="cliente_id" class="block text-sm font-medium text-gray-700">Cliente</label>
                <select name="cliente_id" id="cliente_id" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">-- Selecione --</option>
                    @foreach($clientes as $cliente)
                        <option value="{{ $cliente->id }}" @selected(old('cliente_id') == $cliente->id)>{{ $cliente->razao_social ?? $cliente->nome }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Gestor (opcional) --}}
            <div class="col-span-12 md:col-span-6">
                <label for="gestor_id" class="block text-sm font-medium text-gray-700">Gestor (opcional)</label>
                <select name="gestor_id" id="gestor_id"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">-- Sem gestor --</option>
                    @foreach($gestores as $gestor)
                        <option value="{{ $gestor->id }}" @selected(old('gestor_id') == $gestor->id)>{{ $gestor->razao_social }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Distribuidor (opcional) --}}
            <div class="col-span-12 md:col-span-6">
                <label for="distribuidor_id" class="block text-sm font-medium text-gray-700">Distribuidor (opcional)</label>
                <select name="distribuidor_id" id="distribuidor_id"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">-- Sem distribuidor --</option>
                    @foreach($distribuidores as $d)
                        <option value="{{ $d->id }}" @selected(old('distribuidor_id') == $d->id)>{{ $d->razao_social }}</option>
                    @endforeach
                </select>
            </div>

            {{-- UF --}}
            <div>
                <label for="state" class="block text-sm font-medium text-gray-800">UF</label>
                <select name="state" id="state" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">-- Selecione --</option>
                    @foreach($cidadesUF as $uf)
                        <option value="{{ $uf }}" @selected(old('state') == $uf)>{{ $uf }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Cidade da Venda (via gestor/UF ou distribuidor) --}}
            <div class="col-span-12 md:col-span-6">
                <label for="cidade_id" class="block text-sm font-medium text-gray-700">Cidade da Venda</label>
                @php
                    $temDistOuGestor = old('distribuidor_id') || old('gestor_id') || old('state');
                @endphp
                <select name="cidade_id" id="cidade_id" {{ $temDistOuGestor ? '' : 'disabled' }}
                        class="mt-1 block w-full rounded-md border-gray-300 {{ $temDistOuGestor ? '' : 'bg-gray-50' }} shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">{{ $temDistOuGestor ? '-- Selecione --' : '-- Selecione o gestor ou distribuidor --' }}</option>
                </select>
            </div>

            {{-- Data --}}
            <div class="col-span-12 md:col-span-6">
                <label for="data" class="block text-sm font-medium text-gray-700">Data</label>
                <input type="date" name="data" id="data" value="{{ old('data') }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
            </div>

            {{-- Produtos --}}
            <div class="col-span-12">
                <label class="block text-sm font-medium text-gray-700">Produtos</label>
                <div id="produtos-container" class="space-y-4 mt-2"></div>

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

    {{-- Dados para JS --}}
    <script>
        const ALL_PRODUCTS     = @json($produtos->map(fn($p)=>['id'=>$p->id,'nome'=>$p->nome])->values());
        const OLD_PRODUTOS     = @json(old('produtos', []));
        const OLD_DISTRIBUIDOR = @json(old('distribuidor_id'));
        const OLD_GESTOR       = @json(old('gestor_id'));
        const OLD_CIDADE       = @json(old('cidade_id'));
    </script>

    <script>
        // ================== PRODUTOS (sem duplicados + desconto por item) ==================
        let produtoIndex = 0;
        const container = document.getElementById('produtos-container');
        const addBtn    = document.querySelector('button[onclick="adicionarProduto()"]');

        function getSelectedProductIds() {
            return Array.from(container.querySelectorAll('select[name^="produtos["][name$="[id]"]'))
                .map(sel => sel.value).filter(v => v !== '');
        }

        function buildOptions(excludeIds = [], selectedId = null) {
            const frag = document.createDocumentFragment();
            frag.append(new Option('-- Selecione --', ''));
            ALL_PRODUCTS.forEach(p => {
                const isSelected = String(p.id) === String(selectedId);
                const isExcluded = excludeIds.includes(String(p.id));
                if (isExcluded && !isSelected) return;
                const opt = new Option(p.nome, p.id);
                if (isSelected) opt.selected = true;
                frag.append(opt);
            });
            return frag;
        }

        function refreshAllProductSelects() {
            const chosen = getSelectedProductIds();
            const selects = container.querySelectorAll('select[name^="produtos["][name$="[id]"]');
            selects.forEach(sel => {
                const current = sel.value || null;
                sel.innerHTML = '';
                const exclude = chosen.filter(id => id !== current);
                sel.append(buildOptions(exclude, current));
            });
            const maxReached = chosen.length >= ALL_PRODUCTS.length;
            addBtn.disabled = maxReached;
            addBtn.classList.toggle('opacity-50', maxReached);
            addBtn.title = maxReached ? 'Todos os produtos já foram adicionados' : '';
        }

        function makeProdutoRow(preset = {}) {
            const idx = produtoIndex++;
            const row = document.createElement('div');
            row.className = 'produto border p-4 rounded-md bg-gray-50 grid grid-cols-12 gap-4';
            row.innerHTML = `
                <div class="col-span-12 md:col-span-6">
                    <label class="block text-sm font-medium text-gray-700">Produto</label>
                    <select name="produtos[${idx}][id]"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></select>
                </div>
                <div class="col-span-12 md:col-span-3">
                    <label class="block text-sm font-medium text-gray-700">Quantidade</label>
                    <input type="number" min="1" value="${preset.quantidade ?? 1}" name="produtos[${idx}][quantidade]"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                </div>
                <div class="col-span-12 md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Desc. item (%)</label>
                    <input type="number" min="0" max="100" step="0.01" value="${preset.desconto ?? 0}" name="produtos[${idx}][desconto]"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div class="col-span-12 md:col-span-1 flex items-end">
                    <button type="button" class="remove-row inline-flex items-center rounded-md border px-3 py-2 text-sm bg-red-600 text-white hover:bg-red-700">
                        Remover
                    </button>
                </div>
            `;
            const sel = row.querySelector('select');
            sel.append(buildOptions(getSelectedProductIds(), preset.id ?? null));
            return row;
        }

        function adicionarProduto(preset = {}) {
            const row = makeProdutoRow(preset);
            container.appendChild(row);
            refreshAllProductSelects();
        }

        container.addEventListener('change', (e) => {
            if (e.target.matches('select[name^="produtos["][name$="[id]"]')) {
                refreshAllProductSelects();
            }
        });

        container.addEventListener('click', (e) => {
            if (e.target.closest('.remove-row')) {
                e.target.closest('.produto').remove();
                refreshAllProductSelects();
            }
        });

        document.addEventListener('DOMContentLoaded', () => {
            if (Array.isArray(OLD_PRODUTOS) && OLD_PRODUTOS.length) {
                OLD_PRODUTOS.forEach(p => adicionarProduto(p));
            } else {
                adicionarProduto();
            }
        });
    </script>

    {{-- =================== GESTOR / DISTRIBUIDOR / CIDADE =================== --}}
    <script>
    const distribuidorSelect = document.getElementById('distribuidor_id');
    const gestorSelect       = document.getElementById('gestor_id');
    const cidadeSelect       = document.getElementById('cidade_id');
    const stateSelect        = document.getElementById('state');

    function resetCidadeSelect(placeholder = '-- Selecione --') {
        cidadeSelect.innerHTML = '';
        cidadeSelect.add(new Option(placeholder, ''));
        cidadeSelect.disabled = true;
        cidadeSelect.classList.add('bg-gray-50');
    }

    async function carregarCidadesPorDistribuidor(distribuidorId, selectedCidadeId = null) {
        resetCidadeSelect('-- Carregando... --');
        try {
            const resp = await fetch(`/admin/cidades/por-distribuidor/${distribuidorId}`);
            if (!resp.ok) throw new Error(`HTTP ${resp.status}`);
            const cidades = await resp.json();
            cidadeSelect.innerHTML = '';
            cidadeSelect.add(new Option('-- Selecione --', ''));
            cidades.forEach(c => {
                const opt = new Option(c.name, c.id);
                if (selectedCidadeId && String(selectedCidadeId) === String(c.id)) opt.selected = true;
                cidadeSelect.add(opt);
            });
            cidadeSelect.disabled = false;
            cidadeSelect.classList.remove('bg-gray-50');
            if (!cidades.length) resetCidadeSelect('Distribuidor sem cidades vinculadas');
        } catch (e) {
            console.error(e);
            resetCidadeSelect('Falha ao carregar cidades');
        }
    }

    async function carregarCidadesPorGestor(gestorId, selectedCidadeId = null) {
        resetCidadeSelect('-- Carregando... --');
        try {
            const resp = await fetch(`/admin/cidades/por-gestor/${gestorId}`);
            if (!resp.ok) throw new Error(`HTTP ${resp.status}`);
            const cidades = await resp.json();
            cidadeSelect.innerHTML = '';
            cidadeSelect.add(new Option('-- Selecione --', ''));
            cidades.forEach(c => {
                const opt = new Option(c.name, c.id);
                if (selectedCidadeId && String(selectedCidadeId) === String(c.id)) opt.selected = true;
                cidadeSelect.add(opt);
            });
            cidadeSelect.disabled = false;
            cidadeSelect.classList.remove('bg-gray-50');
            if (!cidades.length) resetCidadeSelect('Nenhuma cidade para a UF do gestor');
        } catch (e) {
            console.error(e);
            resetCidadeSelect('Falha ao carregar cidades');
        }
    }

    async function carregarCidadesPorUF(uf, selectedCidadeId = null) {
    resetCidadeSelect('-- Carregando... --');
    try {
        const resp = await fetch(`/admin/cidades/por-uf/${encodeURIComponent(uf)}`);
        if (!resp.ok) {
            const text = await resp.text();
            console.error('Falha ao carregar cidades:', resp.status, text);
            throw new Error(`HTTP ${resp.status}`);
        }
        const cidades = await resp.json();
        cidadeSelect.innerHTML = '';
        cidadeSelect.add(new Option('-- Selecione --', ''));
        cidades.forEach(c => {
            const opt = new Option(c.name, c.id);
            if (selectedCidadeId && String(selectedCidadeId) === String(c.id)) opt.selected = true;
            cidadeSelect.add(opt);
        });
        cidadeSelect.disabled = false;
        cidadeSelect.classList.remove('bg-gray-50');
        if (!cidades.length) resetCidadeSelect('UF sem cidades cadastradas');
    } catch (e) {
        console.error(e);
        resetCidadeSelect('Falha ao carregar cidades');
    }
}


    // Prioridade: 1) distribuidor  2) gestor  3) UF
    distribuidorSelect.addEventListener('change', async function () {
        const distribuidorId = this.value || null;
        if (distribuidorId) {
            await carregarCidadesPorDistribuidor(distribuidorId, null);
        } else if (gestorSelect.value) {
            await carregarCidadesPorGestor(gestorSelect.value, null);
        } else if (stateSelect.value) {
            await carregarCidadesPorUF(stateSelect.value, null);
        } else {
            resetCidadeSelect('-- Selecione gestor, distribuidor ou UF --');
        }
    });

    gestorSelect.addEventListener('change', async function () {
        if (distribuidorSelect.value) return; // prioridade do distribuidor
        const gestorId = this.value || null;
        if (gestorId) {
            await carregarCidadesPorGestor(gestorId, null);
        } else if (stateSelect.value) {
            await carregarCidadesPorUF(stateSelect.value, null);
        } else {
            resetCidadeSelect('-- Selecione gestor, distribuidor ou UF --');
        }
    });

    stateSelect.addEventListener('change', async function () {
        // Só carrega por UF se não houver distribuidor nem gestor selecionados
        if (distribuidorSelect.value || gestorSelect.value) return;
        const uf = this.value || null;
        if (uf) {
            await carregarCidadesPorUF(uf, null);
        } else {
            resetCidadeSelect('-- Selecione gestor, distribuidor ou UF --');
        }
    });

    // Estado inicial (old)
    document.addEventListener('DOMContentLoaded', async () => {
        const OLD_CIDADE = @json(old('cidade_id'));
        const OLD_DISTRIBUIDOR = @json(old('distribuidor_id'));
        const OLD_GESTOR = @json(old('gestor_id'));
        const OLD_STATE = @json(old('state'));

        if (OLD_DISTRIBUIDOR) {
            await carregarCidadesPorDistribuidor(OLD_DISTRIBUIDOR, OLD_CIDADE);
        } else if (OLD_GESTOR) {
            await carregarCidadesPorGestor(OLD_GESTOR, OLD_CIDADE);
        } else if (OLD_STATE) {
            await carregarCidadesPorUF(OLD_STATE, OLD_CIDADE);
        } else {
            resetCidadeSelect('-- Selecione gestor, distribuidor ou UF --');
        }
    });
</script>

</x-app-layout>
