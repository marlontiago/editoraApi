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

            {{-- ==================== DADOS GERAIS ==================== --}}
            <div class="grid grid-cols-12 gap-4 bg-white p-6 rounded-2xl shadow">

                {{-- Data --}}
                <div class="col-span-12 md:col-span-3">
                    <label class="block text-sm font-medium text-gray-700">Data</label>
                    <input
                        type="date"
                        name="data"
                        value="{{ old('data', \Carbon\Carbon::parse($pedido->data)->format('Y-m-d')) }}"
                        class="mt-1 w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        required
                    >
                </div>

                {{-- Status --}}
                <div class="col-span-12 md:col-span-3">
                    <label class="block text-sm font-medium text-gray-700">Status</label>
                    @php $statuses = ['em_andamento' => 'Em andamento', 'finalizado' => 'Finalizado', 'cancelado' => 'Cancelado']; @endphp
                    <select name="status" class="mt-1 w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                        @foreach ($statuses as $val => $label)
                            <option value="{{ $val }}" @selected(old('status', $pedido->status) === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Cliente --}}
                <div class="col-span-12 md:col-span-6">
                    <label for="cliente_id" class="block text-sm font-medium text-gray-700">Cliente</label>
                    <select
                        name="cliente_id"
                        id="cliente_id"
                        required
                        class="mt-1 block w-full rounded-lg border px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    >
                        <option value="">— Selecionar —</option>
                        @foreach ($clientes as $c)
                            <option value="{{ $c->id }}" @selected(old('cliente_id', $pedido->cliente_id) == $c->id)>
                                {{ $c->razao_social ?? $c->nome }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Gestor (opcional) --}}
                <div class="col-span-12 md:col-span-6">
                    <label class="block text-sm font-medium text-gray-700">Gestor (opcional)</label>
                    <select
                        name="gestor_id"
                        id="gestor_id"
                        class="mt-1 w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    >
                        <option value="">— Sem gestor —</option>
                        @foreach ($gestores as $g)
                            <option value="{{ $g->id }}" @selected(old('gestor_id', $pedido->gestor_id) == $g->id)>
                                {{ $g->razao_social }} @if($g->user) ({{ $g->user->name }}) @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Distribuidor (opcional) --}}
                <div class="col-span-12 md:col-span-6">
                    <label class="block text-sm font-medium text-gray-700">Distribuidor (opcional)</label>
                    <select
                        name="distribuidor_id"
                        id="distribuidor_id"
                        class="mt-1 w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    >
                        <option value="">— Sem distribuidor —</option>
                        @foreach ($distribuidores as $d)
                            <option value="{{ $d->id }}" @selected(old('distribuidor_id', $pedido->distribuidor_id) == $d->id)>
                                {{ $d->razao_social }} @if($d->user) ({{ $d->user->name }}) @endif
                            </option>
                        @endforeach
                    </select>
                </div>

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

                {{-- Observações --}}
                <div class="col-span-12">
                    <label class="block text-sm font-medium text-gray-700">Observações</label>
                    <textarea
                        name="observacoes"
                        rows="3"
                        class="mt-1 w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Anotações internas sobre o pedido (opcional)"
                    >{{ old('observacoes', $pedido->observacoes) }}</textarea>
                </div>
            </div>

            {{-- ==================== TABELA DE PRODUTOS ==================== --}}
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
                                <th class="px-4 py-3 text-sm font-medium text-gray-600" style="width: 160px;">Desc. item (%)</th>
                                <th class="px-4 py-3" style="width: 80px;"></th>
                            </tr>
                        </thead>
                        <tbody id="tabelaProdutos">
                            @foreach ($pedido->produtos as $p)
                                <tr class="border-t" data-index="{{ $loop->index }}">
                                    <td class="px-4 py-3 align-top">
                                        {{-- linha existente vem SEM select, com hidden id --}}
                                        <input type="hidden" name="produtos[{{ $loop->index }}][id]" value="{{ $p->id }}" class="inputId">
                                        <div class="font-medium">{{ $p->nome }}</div>
                                        <div class="text-xs text-gray-500">Preço atual: R$ {{ number_format($p->preco, 2, ',', '.') }}</div>
                                    </td>
                                    <td class="px-4 py-3 align-top">
                                        <input
                                            type="number"
                                            min="1"
                                            class="w-28 border rounded-lg px-2 py-1 inputQtd focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            name="produtos[{{ $loop->index }}][quantidade]"
                                            value="{{ old('produtos.'.$loop->index.'.quantidade', $p->pivot->quantidade) }}"
                                            required
                                        >
                                    </td>
                                    <td class="px-4 py-3 align-top">
                                        <input
                                            type="number"
                                            min="0" max="100" step="0.01"
                                            class="w-36 border rounded-lg px-2 py-1 inputDesc focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            name="produtos[{{ $loop->index }}][desconto]"
                                            value="{{ old('produtos.'.$loop->index.'.desconto', $p->pivot->desconto_item ?? 0) }}"
                                        >
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
                    Dica: “Remover” exclui a linha do envio. O backend também limpa linhas inválidas.
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

    {{-- Template para NOVA linha --}}
    <template id="rowTemplate">
        <tr class="border-t" data-index="__INDEX__">
            <td class="px-4 py-3 align-top">
                <select class="w-full border rounded-lg px-2 py-2 produtoSelect focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">— Selecionar produto —</option>
                    @foreach ($produtos as $pp)
                        <option value="{{ $pp->id }}" data-nome="{{ $pp->nome }}">
                            {{ $pp->nome }} — R$ {{ number_format($pp->preco, 2, ',', '.') }}
                        </option>
                    @endforeach
                </select>
                <input type="hidden" data-name="produtos[__INDEX__][id]" class="inputId">
            </td>
            <td class="px-4 py-3 align-top">
                <input
                    type="number" min="1"
                    class="w-28 border rounded-lg px-2 py-1 inputQtd focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    data-name="produtos[__INDEX__][quantidade]" value="1" required
                >
            </td>
            <td class="px-4 py-3 align-top">
                <input
                    type="number" min="0" max="100" step="0.01"
                    class="w-36 border rounded-lg px-2 py-1 inputDesc focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    data-name="produtos[__INDEX__][desconto]" value="0"
                >
            </td>
            <td class="px-4 py-3 text-right align-top">
                <button type="button" class="text-red-600 hover:underline btnRemoveRow">Remover</button>
            </td>
        </tr>
    </template>

    {{-- Estado inicial para JS --}}
    <script>
        window.PEDIDO_ATUAL = {
            distribuidor_id: @json(old('distribuidor_id', $pedido->distribuidor_id)),
            gestor_id: @json(old('gestor_id', $pedido->gestor_id)),
            cidade_id: @json(old('cidade_id', $pedido->cidades->pluck('id')->first()))
        };
    </script>

    {{-- =================== JS: GESTOR / DISTRIBUIDOR / CIDADE =================== --}}
    <script>
        const distribuidorSelect = document.getElementById('distribuidor_id');
        const gestorSelect       = document.getElementById('gestor_id');
        const cidadeSelect       = document.getElementById('cidade_id');

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

        // Prioridade: distribuidor > gestor
        distribuidorSelect.addEventListener('change', async function () {
            const distribuidorId = this.value || null;
            if (distribuidorId) {
                await carregarCidadesPorDistribuidor(distribuidorId, null);
            } else if (gestorSelect.value) {
                await carregarCidadesPorGestor(gestorSelect.value, null);
            } else {
                resetCidadeSelect('-- Selecione o gestor ou o distribuidor --');
            }
        });

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

        gestorSelect.addEventListener('change', async function () {
            if (distribuidorSelect.value) return; // prioridade do distribuidor
            const gestorId = this.value || null;
            if (gestorId) {
                await carregarCidadesPorGestor(gestorId, null);
            } else {
                resetCidadeSelect('-- Selecione o gestor ou o distribuidor --');
            }
        });

        // Estado inicial do EDIT
        document.addEventListener('DOMContentLoaded', async () => {
            const dId = window.PEDIDO_ATUAL?.distribuidor_id ?? null;
            const gId = window.PEDIDO_ATUAL?.gestor_id ?? null;
            const cId = window.PEDIDO_ATUAL?.cidade_id ?? null;

            if (dId) {
                await carregarCidadesPorDistribuidor(dId, cId);
            } else if (gId) {
                await carregarCidadesPorGestor(gId, cId);
            } else {
                resetCidadeSelect('-- Selecione o gestor ou o distribuidor --');
            }
        });
    </script>

    {{-- =================== JS: PRODUTOS (sem duplicados) =================== --}}
    <script>
        const tabelaProdutosBody = document.getElementById('tabelaProdutos');
        const btnAddRow          = document.getElementById('btnAddRow');
        const rowTemplate        = document.getElementById('rowTemplate');
        let nextIndex = tabelaProdutosBody.querySelectorAll('tr[data-index]').length;

        // pega IDs já selecionados (linhas antigas com hidden e novas com select)
        function getSelectedProductIds() {
            const ids = [];
            // corrigido: selector do hidden termina com [id]
            tabelaProdutosBody.querySelectorAll('input.inputId[name^="produtos["][name$="[id]"]').forEach(h => {
                const v = String(h.value || '').trim();
                if (v) ids.push(v);
            });
            tabelaProdutosBody.querySelectorAll('tr[data-index] select.produtoSelect').forEach(sel => {
                const v = String(sel.value || '').trim();
                if (v) ids.push(v);
            });
            return Array.from(new Set(ids));
        }

        function refreshAllProductSelectOptions() {
            const chosen = getSelectedProductIds();
            tabelaProdutosBody.querySelectorAll('tr[data-index] select.produtoSelect').forEach(sel => {
                const current = String(sel.value || '');
                // preserva todas as opções originais (do template) lendo do DOM atual
                const allOpts = Array.from(sel.querySelectorAll('option')).map(o => ({value: o.value, text: o.textContent}));
                sel.innerHTML = '';
                const placeholder = document.createElement('option');
                placeholder.value = '';
                placeholder.textContent = '— Selecionar produto —';
                sel.appendChild(placeholder);
                allOpts.forEach(({value, text}) => {
                    if (value === '') return;
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

        function addProductRow() {
            const clone = rowTemplate.content.cloneNode(true);
            const html  = clone.firstElementChild.outerHTML.replaceAll('__INDEX__', String(nextIndex));
            const container = document.createElement('tbody');
            container.innerHTML = html;
            const tr = container.firstElementChild;

            // aplica os names reais
            tr.querySelectorAll('[data-name]').forEach(el => {
                el.setAttribute('name', el.getAttribute('data-name'));
                el.removeAttribute('data-name');
            });

            const select = tr.querySelector('select.produtoSelect');
            const hidden = tr.querySelector('input.inputId');

            select.addEventListener('change', () => {
                hidden.value = select.value || '';
                refreshAllProductSelectOptions();
            });

            tr.querySelector('.btnRemoveRow').addEventListener('click', () => {
                tr.remove();
                refreshAllProductSelectOptions();
            });

            tabelaProdutosBody.appendChild(tr);
            refreshAllProductSelectOptions();
            nextIndex++;
        }

        // remover linhas existentes
        tabelaProdutosBody.addEventListener('click', (e) => {
            const btn = e.target.closest('.btnRemoveRow');
            if (!btn) return;
            const tr = btn.closest('tr[data-index]');
            if (!tr) return;
            tr.remove();
            refreshAllProductSelectOptions();
        });

        // adicionar novas linhas
        btnAddRow?.addEventListener('click', addProductRow);

        // garantir que selects novos respeitem itens já existentes ao abrir a página
        document.addEventListener('DOMContentLoaded', () => {
            refreshAllProductSelectOptions();
        });

        // limpeza final antes de enviar (evita linhas sem id/quantidade)
        document.getElementById('formPedidoEdit')?.addEventListener('submit', () => {
            tabelaProdutosBody.querySelectorAll('tr[data-index]').forEach(tr => {
                const hidden = tr.querySelector('input.inputId');
                const qtd    = tr.querySelector('input.inputQtd');
                const idVal  = parseInt(hidden?.value || '0', 10);
                const qVal   = parseInt(qtd?.value || '0', 10);
                if (!(idVal > 0 && qVal > 0)) tr.remove();
            });
        });
    </script>
</x-app-layout>
