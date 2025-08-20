<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Editar Pedido #{{ $pedido->id }}</h2>
    </x-slot>

    @if (session('error'))
        <div class="bg-red-100 text-red-800 p-3 rounded mb-4">
            {{ session('error') }}
        </div>
    @endif

    <div class="p-6 space-y-6">
        @if ($errors->any())
            <div class="bg-red-100 text-red-800 p-3 rounded">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('success'))
            <div class="bg-green-100 text-green-800 p-3 rounded">
                {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="{{ route('admin.pedidos.update', $pedido) }}" class="space-y-6" id="formPedidoEdit">
            @csrf
            @method('PUT')

            <!-- Dados gerais -->
            {{-- ESTILO: viramos grid de 12 colunas e limitamos larguras com max-w-* para os campos não “explodirem” --}}
            <div class="grid grid-cols-12 gap-4 bg-white p-6 rounded-2xl shadow">

                <div class="col-span-12 md:col-span-3">
                    <label class="block text-sm font-medium text-gray-700">Data</label>
                    <input type="date" name="data"
                        value="{{ old('data', \Carbon\Carbon::parse($pedido->data)->format('Y-m-d')) }}"
                        class="mt-1 w-full max-w-md border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div class="col-span-12 md:col-span-3">
                    <label class="block text-sm font-medium text-gray-700">Status</label>
                    @php
                        $statuses = ['em_andamento' => 'Em andamento', 'finalizado' => 'Finalizado', 'cancelado' => 'Cancelado'];
                    @endphp
                    <select name="status"
                            class="mt-1 w-full max-w-md border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        @foreach ($statuses as $val => $label)
                            <option value="{{ $val }}" @selected(old('status', $pedido->status) === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-span-12 md:col-span-3">
                    <label class="block text-sm font-medium text-gray-700">Gestor</label>
                    <select name="gestor_id" id="gestor_id"
                            class="mt-1 w-full max-w-md border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">— Selecionar —</option>
                        @foreach ($gestores as $g)
                            <option value="{{ $g->id }}" @selected(old('gestor_id', $pedido->gestor_id) == $g->id)>
                                {{ $g->razao_social }} @if($g->user) ({{ $g->user->name }}) @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-span-12 md:col-span-3">
                    <label class="block text-sm font-medium text-gray-700">Distribuidor</label>
                    <select name="distribuidor_id" id="distribuidor_id"
                            class="mt-1 w-full max-w-md border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">— Selecionar —</option>
                        @foreach ($distribuidores as $d)
                            <option value="{{ $d->id }}" @selected(old('distribuidor_id', $pedido->distribuidor_id) == $d->id)>
                                {{ $d->razao_social }} @if($d->user) ({{ $d->user->name }}) @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-span-12 md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Desconto (%)</label>
                    <input type="number" name="desconto" min="0" max="100" step="0.01"
                        value="{{ old('desconto', $pedido->desconto) }}"
                        class="mt-1 w-full max-w-md border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                {{-- ESTILO: cidade ocupava a largura toda; limitamos a largura com max-w-md e ajustamos col-span --}}
                <div class="col-span-12 md:col-span-4">
                    <label for="cidade_id" class="block text-sm font-medium text-gray-700">Cidade da Venda</label>
                    <select name="cidade_id" id="cidade_id" disabled
                            class="mt-1 w-full max-w-md rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">-- Selecione o distribuidor primeiro --</option>
                    </select>
                </div>

            </div>

            <!-- Produtos -->
            {{-- ESTILO: leve polimento visual (bordas arredondadas, padding maior) --}}
            <div class="bg-white p-6 rounded-2xl shadow">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">Produtos do Pedido</h3>
                    <button type="button" id="btnAddRow"
                        class="bg-green-600 text-white px-3 py-2 rounded-lg hover:bg-green-700 transition">
                        + Adicionar produto
                    </button>
                </div>

                <div class="overflow-x-auto rounded-lg border border-gray-200">
                    <table class="min-w-full text-left border-collapse">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-sm font-medium text-gray-600">Produto</th>
                                <th class="px-4 py-3 text-sm font-medium text-gray-600" style="width: 140px;">Quantidade</th>
                                <th class="px-4 py-3" style="width: 80px;"></th>
                            </tr>
                        </thead>
                        <tbody id="tabelaProdutos">
                            {{-- Linhas existentes (itens atuais) --}}
                            @foreach ($pedido->produtos as $p)
                                <tr class="border-t" data-index="{{ $loop->index }}">
                                    <td class="px-4 py-3 align-top">
                                        <input type="hidden" name="produtos[{{ $loop->index }}][id]" value="{{ $p->id }}" class="inputId">
                                        <div class="font-medium">{{ $p->nome }}</div>
                                        <div class="text-xs text-gray-500">
                                            Preço atual: R$ {{ number_format($p->preco, 2, ',', '.') }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 align-top">
                                        <input type="number" min="0" class="w-28 border rounded-lg px-2 py-1 inputQtd focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            name="produtos[{{ $loop->index }}][quantidade]"
                                            value="{{ old('produtos.'.$loop->index.'.quantidade', $p->pivot->quantidade) }}">
                                    </td>
                                    <td class="px-4 py-3 text-right align-top">
                                        <button type="button" class="text-red-600 hover:underline btnRemoveRow">Remover</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <p class="text-sm text-gray-500 mt-3">
                    Dica: para <strong>remover</strong> um item, clique em “Remover” (a linha sai do envio).
                </p>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('admin.pedidos.show', $pedido) }}" class="px-4 py-2 rounded-lg border hover:bg-gray-50">
                    Cancelar
                </a>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                    Salvar alterações
                </button>
            </div>
        </form>
    </div>

    {{-- Template oculto para nova linha (usa __INDEX__ que será substituído no JS) --}}
    <template id="rowTemplate">
        <tr class="border-t" data-index="__INDEX__">
            <td class="px-4 py-3">
                <select class="w-full max-w-md border rounded-lg px-2 py-2 produtoSelect focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">— Selecionar produto —</option>
                    @foreach ($produtos as $pp)
                        <option value="{{ $pp->id }}" data-nome="{{ $pp->nome }}">
                            {{ $pp->nome }} — R$ {{ number_format($pp->preco, 2, ',', '.') }}
                        </option>
                    @endforeach
                </select>

                {{-- inputs “reais” preenchidos via JS --}}
                <input type="hidden" data-name="produtos[__INDEX__][id]" class="inputId">
            </td>
            <td class="px-4 py-3">
                <input type="number" min="1" class="w-28 border rounded-lg px-2 py-1 inputQtd focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                       data-name="produtos[__INDEX__][quantidade]" value="1">
            </td>
            <td class="px-4 py-3 text-right">
                <button type="button" class="text-red-600 hover:underline btnRemoveRow">Remover</button>
            </td>
        </tr>
    </template>

    <script>
        window.PEDIDO_ATUAL = {
            distribuidor_id: @json($pedido->distribuidor_id),
            // Se o pedido pode ter várias cidades na pivot, pegue a primeira (ou a correta):
            cidade_id: @json($pedido->cidades->pluck('id')->first())
        };
    </script>

    <script>
        // ======================= GESTOR / DISTRIBUIDOR / CIDADE (EDIT) =======================
        const gestorSelect       = document.getElementById('gestor_id');
        const distribuidorSelect = document.getElementById('distribuidor_id');
        const cidadeSelect       = document.getElementById('cidade_id');

        function resetCidadeSelect(placeholder = '-- Selecione --') {
            cidadeSelect.innerHTML = '';
            cidadeSelect.add(new Option(placeholder, ''));
            cidadeSelect.disabled = true;
            cidadeSelect.classList.add('bg-gray-50');
        }

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
                // SUA ROTA: GET /admin/distribuidores/por-gestor/{gestor}
                const resp = await fetch(`/admin/distribuidores/por-gestor/${gestorId}`, { credentials: 'same-origin' });
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

                // Se já tem distribuidor selecionado (estado inicial do edit),
                // carrega as cidades desse distribuidor e marca a cidade atual do pedido.
                if (selectedId) {
                    await carregarCidadesPorDistribuidor(selectedId, window.PEDIDO_ATUAL?.cidade_id ?? null);
                }
            } catch (e) {
                console.error(e);
                distribuidorSelect.add(new Option('Falha ao carregar distribuidores', ''));
            }
        }

        async function carregarCidadesPorDistribuidor(distribuidorId, selectedCidadeId = null) {
            resetCidadeSelect('-- Carregando... --');
            try {
                // SUA ROTA: GET /admin/cidades/por-distribuidor/{id}
                const resp = await fetch(`/admin/cidades/por-distribuidor/${distribuidorId}`, { credentials: 'same-origin' });
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
        gestorSelect?.addEventListener('change', async function () {
            const gestorId = this.value || null;
            await carregarDistribuidores(gestorId);
        });

        distribuidorSelect?.addEventListener('change', async function () {
            const distribuidorId = this.value || null;
            if (distribuidorId) {
                await carregarCidadesPorDistribuidor(distribuidorId, null);
            } else {
                resetCidadeSelect('-- Selecione o distribuidor primeiro --');
            }
        });

        // Estado inicial do EDIT:
        // - Se já existe um gestor selecionado, carrega distribuidores e mantém o distribuidor atual do pedido.
        // - Em seguida, carrega as cidades do distribuidor e seleciona a "cidade da venda" do pedido.
        document.addEventListener('DOMContentLoaded', async () => {
            const gestorIdAtual       = document.getElementById('gestor_id')?.value || null;
            const distribuidorIdAtual = window.PEDIDO_ATUAL?.distribuidor_id ?? null;

            if (gestorIdAtual) {
                await carregarDistribuidores(gestorIdAtual, distribuidorIdAtual);
                if (distribuidorIdAtual) {
                    await carregarCidadesPorDistribuidor(distribuidorIdAtual, window.PEDIDO_ATUAL?.cidade_id ?? null);
                }
            } else {
                // se o edit não tem gestor setado, deixa selects em modo padrão
                resetCidadeSelect('-- Selecione o distribuidor primeiro --');
            }
        });
    </script>

    <script>
        // ======================= PRODUTOS (ADD/REMOVE + EVITAR DUPLICADOS) =======================

        // Tabela e botões
        const tabelaProdutosBody = document.getElementById('tabelaProdutos');
        const btnAddRow          = document.getElementById('btnAddRow');
        const rowTemplate        = document.getElementById('rowTemplate');

        // Índice começa no número de linhas já existentes (itens atuais)
        let nextIndex = tabelaProdutosBody.querySelectorAll('tr[data-index]').length;

        // util: coleta todos os IDs selecionados nas linhas (existentes + novas)
        function getSelectedProductIds() {
            const ids = [];

            // 1) Linhas existentes (com <input type="hidden" name="produtos[i][id]">)
            tabelaProdutosBody.querySelectorAll('input.inputId[name^="produtos["][name$="][id]"]').forEach(h => {
                const v = String(h.value || '').trim();
                if (v) ids.push(v);
            });

            // 2) Linhas novas (template) ainda com select e hidden (o hidden já tem "name" atribuído)
            tabelaProdutosBody.querySelectorAll('tr[data-index] select.produtoSelect').forEach(sel => {
                const v = String(sel.value || '').trim();
                if (v) ids.push(v);
            });

            // Retorna sem duplicados
            return Array.from(new Set(ids));
        }

        // util: reconstrói as opções de TODOS os selects, removendo as já escolhidas em outras linhas
        function refreshAllProductSelectOptions() {
            const chosen = getSelectedProductIds();

            tabelaProdutosBody.querySelectorAll('tr[data-index] select.produtoSelect').forEach(sel => {
                const current = String(sel.value || '');

                // Reconstrói as opções preservando a primeira e o valor atual
                const allOpts = Array.from(sel.querySelectorAll('option')).map(o => ({value: o.value, text: o.textContent}));
                sel.innerHTML = '';

                // primeira opção (placeholder)
                const placeholder = document.createElement('option');
                placeholder.value = '';
                placeholder.textContent = '— Selecionar produto —';
                sel.appendChild(placeholder);

                // repõe opções removendo as já escolhidas, exceto o próprio current
                allOpts.forEach(({value, text}) => {
                    if (value === '') return; // já adicionamos placeholder
                    const isExcluded = chosen.includes(value) && value !== current;
                    if (isExcluded) return;

                    const opt = document.createElement('option');
                    opt.value = value;
                    opt.textContent = text;
                    if (value === current) opt.selected = true;
                    sel.appendChild(opt);
                });
            });
        }

        // Cria uma nova linha a partir do template
        function addProductRow() {
            const clone = rowTemplate.content.cloneNode(true);
            const html  = clone.firstElementChild.outerHTML.replaceAll('__INDEX__', String(nextIndex));
            const row   = document.createElement('tbody'); // contêiner temporário
            row.innerHTML = html;
            const tr = row.firstElementChild;

            // 1) dar "name" real aos inputs do template (usamos data-name para isso)
            tr.querySelectorAll('[data-name]').forEach(el => {
                el.setAttribute('name', el.getAttribute('data-name'));
                el.removeAttribute('data-name');
            });

            // 2) sincronizar select -> hidden inputId
            const select = tr.querySelector('select.produtoSelect');
            const hidden = tr.querySelector('input.inputId');

            // Ao trocar o select, grava no hidden e atualiza opções dos outros selects (evitar duplicado)
            select.addEventListener('change', () => {
                hidden.value = select.value || '';
                refreshAllProductSelectOptions();
            });

            // 3) botão remover
            tr.querySelector('.btnRemoveRow').addEventListener('click', () => {
                tr.remove();
                refreshAllProductSelectOptions();
            });

            // 4) anexa na tabela
            tabelaProdutosBody.appendChild(tr);

            // 5) atualiza opções considerando esta nova linha
            refreshAllProductSelectOptions();

            // incrementa índice para a próxima linha
            nextIndex++;
        }

        // Remoção para linhas que já existem (itens atuais)
        tabelaProdutosBody.addEventListener('click', (e) => {
            const btn = e.target.closest('.btnRemoveRow');
            if (!btn) return;

            const tr = btn.closest('tr[data-index]');
            if (!tr) return;

            tr.remove();
            refreshAllProductSelectOptions();
        });

        // Botão “+ Adicionar produto”
        btnAddRow?.addEventListener('click', addProductRow);

        // Antes de enviar o formulário, remove linhas inválidas (sem produto ou qtd <= 0)
        document.getElementById('formPedidoEdit')?.addEventListener('submit', () => {
            // 1) remove linhas novas sem produto ou com quantidade inválida
            tabelaProdutosBody.querySelectorAll('tr[data-index]').forEach(tr => {
                const hidden = tr.querySelector('input.inputId');
                const qtd    = tr.querySelector('input.inputQtd');
                const idVal  = parseInt(hidden?.value || '0', 10);
                const qVal   = parseInt(qtd?.value || '0', 10);
                if (!(idVal > 0 && qVal > 0)) tr.remove();
            });

            // 2) nas linhas antigas, se a quantidade for 0 ou vazia, o seu backend já filtra (você já implementou essa limpeza no controller)
        });
    </script>


</x-app-layout>
