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

            {{-- Cidade da Venda --}}
            <div class="col-span-12 md:col-span-6">
                <label for="cidade_id" class="block text-sm font-medium text-gray-700">Cidade da Venda</label>
                <select name="cidade_id" id="cidade_id" disabled
                        class="mt-1 block w-full rounded-md border-gray-300 bg-gray-50 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">-- Selecione o distribuidor primeiro --</option>
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


    <script>
        const ALL_PRODUCTS = @json($produtos->map(fn($p)=>['id'=>$p->id,'nome'=>$p->nome])->values());
    </script>

    <script>
    
        let produtoIndex = 0;

        const container = document.getElementById('produtos-container');
        const addBtn    = document.querySelector('button[onclick="adicionarProduto()"]');

        // util: ids selecionados atualmente
        function getSelectedProductIds() {
            return Array.from(container.querySelectorAll('select[name^="produtos["][name$="[id]"]'))
                .map(sel => sel.value)
                .filter(v => v !== '');
        }

        // util: monta <option> com base nos que não podem aparecer
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

        // recria as opções de TODOS os selects respeitando o que já foi escolhido
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

        // cria um bloco de produto
        function makeProdutoRow() {
            const idx = produtoIndex++;
            const row = document.createElement('div');
            row.className = 'produto border p-4 rounded-md bg-gray-50 grid grid-cols-12 gap-4';
            row.innerHTML = `
                <div class="col-span-12 md:col-span-8">
                    <label class="block text-sm font-medium text-gray-700">Produto</label>
                    <select name="produtos[${idx}][id]"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></select>
                </div>
                <div class="col-span-12 md:col-span-3">
                    <label class="block text-sm font-medium text-gray-700">Quantidade</label>
                    <input type="number" min="1" value="1" name="produtos[${idx}][quantidade]"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                </div>
                <div class="col-span-12 md:col-span-1 flex items-end">
                    <button type="button" class="remove-row inline-flex items-center rounded-md border px-3 py-2 text-sm bg-red-600 text-white hover:bg-red-700">
                        Remover
                    </button>
                </div>
            `;
            const sel = row.querySelector('select');
            sel.append(buildOptions(getSelectedProductIds()));
            return row;
        }

        function adicionarProduto() {
            const row = makeProdutoRow();
            container.appendChild(row);
            refreshAllProductSelects();
        }

        // delegação de eventos: change nos selects → atualizar filtros
        container.addEventListener('change', (e) => {
            if (e.target.matches('select[name^="produtos["][name$="[id]"]')) {
                refreshAllProductSelects();
            }
        });

        // remover linha
        container.addEventListener('click', (e) => {
            if (e.target.closest('.remove-row')) {
                const row = e.target.closest('.produto');
                row.remove();
                refreshAllProductSelects();
            }
        });

        // inicial: transforma a primeira linha existente do teu HTML em “controlada”
        document.addEventListener('DOMContentLoaded', () => {
            const first = container.querySelector('.produto');
            if (first) first.remove();
            adicionarProduto();
        });
    </script>

    <script>
        // ======================= GESTOR / DISTRIBUIDOR / CIDADE DA VENDA =======================
        const gestorSelect       = document.getElementById('gestor_id');
        const distribuidorSelect = document.getElementById('distribuidor_id');
        const cidadeSelect       = document.getElementById('cidade_id');

        // Reseta o select de cidades
        function resetCidadeSelect(placeholder = '-- Selecione --') {
            cidadeSelect.innerHTML = '';
            cidadeSelect.add(new Option(placeholder, ''));
            cidadeSelect.disabled = true;
            cidadeSelect.classList.add('bg-gray-50');
        }

        // Carrega distribuidores do gestor (rota: GET /admin/distribuidores/por-gestor/{gestor})
        async function carregarDistribuidores(gestorId, selectedId = null) {
            distribuidorSelect.innerHTML = '';
            distribuidorSelect.disabled  = true;
            distribuidorSelect.classList.add('bg-gray-50');

            // ao trocar gestor, limpa cidades
            resetCidadeSelect('-- Selecione o distribuidor primeiro --');

            if (!gestorId) {
                distribuidorSelect.add(new Option('-- Selecione o gestor primeiro --', ''));
                return;
            }

            try {
                const url  = `/admin/distribuidores/por-gestor/${gestorId}`;
                const resp = await fetch(url, { credentials: 'same-origin' });
                if (!resp.ok) throw new Error(`HTTP ${resp.status}`);

                const data = await resp.json(); // [{id, text}]
                distribuidorSelect.add(new Option('-- Selecione --', ''));
                data.forEach(item => {
                    const opt = new Option(item.text, item.id);
                    if (selectedId && String(selectedId) === String(item.id)) opt.selected = true;
                    distribuidorSelect.add(opt);
                });

                distribuidorSelect.disabled = false;
                distribuidorSelect.classList.remove('bg-gray-50');

                // se já veio selectedId (ex.: old input), carrega cidades
                if (selectedId) {
                    await carregarCidadesPorDistribuidor(selectedId, @json(old('cidade_id')));
                }
            } catch (e) {
                console.error(e);
                distribuidorSelect.add(new Option('Falha ao carregar distribuidores', ''));
            }
        }

        // Carrega cidades do distribuidor (rota: GET /admin/cidades/por-distribuidor/{id})
        async function carregarCidadesPorDistribuidor(distribuidorId, selectedCidadeId = null) {
            resetCidadeSelect('-- Carregando... --');

            try {
                const resp = await fetch(`/admin/cidades/por-distribuidor/${distribuidorId}`);
                if (!resp.ok) throw new Error(`HTTP ${resp.status}`);

                const cidades = await resp.json(); // [{id, name}]
                cidadeSelect.innerHTML = '';
                cidadeSelect.add(new Option('-- Selecione --', ''));

                cidades.forEach(c => {
                    const opt = new Option(c.name, c.id);
                    if (selectedCidadeId && String(selectedCidadeId) === String(c.id)) opt.selected = true;
                    cidadeSelect.add(opt);
                });

                cidadeSelect.disabled = false;
                cidadeSelect.classList.remove('bg-gray-50');
            } catch (e) {
                console.error(e);
                resetCidadeSelect('Falha ao carregar cidades');
            }
        }

        // Eventos
        gestorSelect.addEventListener('change', async function () {
            const gestorId = this.value || null;
            await carregarDistribuidores(gestorId);
        });

        distribuidorSelect.addEventListener('change', async function () {
            const distribuidorId = this.value || null;
            if (distribuidorId) {
                await carregarCidadesPorDistribuidor(distribuidorId);
            } else {
                resetCidadeSelect('-- Selecione o distribuidor primeiro --');
            }
        });

        // Estado inicial (old: pós validação)
        document.addEventListener('DOMContentLoaded', async () => {
            const oldGestor       = @json(old('gestor_id'));
            const oldDistribuidor = @json(old('distribuidor_id'));
            const oldCidade       = @json(old('cidade_id'));

            if (oldGestor) {
                await carregarDistribuidores(oldGestor, oldDistribuidor);
                if (oldDistribuidor) {
                    await carregarCidadesPorDistribuidor(oldDistribuidor, oldCidade);
                }
            }
        });
    </script>
</x-app-layout>
