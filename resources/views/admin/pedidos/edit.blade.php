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

                {{-- Gestor (somente leitura) --}}
                @php $gestorAtual = $pedido->gestor; @endphp
                <div class="col-span-12 md:col-span-6">
                    <label class="block text-sm font-medium text-gray-700">Gestor</label>
                    <input
                        type="text"
                        class="mt-1 w-full rounded-lg border px-3 py-2 bg-gray-100"
                        value="{{ $gestorAtual?->razao_social ?: '—' }}"
                        readonly
                    >
                    <input type="hidden" name="gestor_id" value="{{ $gestorAtual?->id }}">
                </div>

                {{-- Distribuidor (somente leitura) --}}
                @php $distAtual = $pedido->distribuidor; @endphp
                <div class="col-span-12 md:col-span-6">
                    <label class="block text-sm font-medium text-gray-700">Distribuidor</label>
                    <input
                        type="text"
                        class="mt-1 w-full rounded-lg border px-3 py-2 bg-gray-100"
                        value="{{ $distAtual?->razao_social ?: '—' }}"
                        readonly
                    >
                    <input type="hidden" name="distribuidor_id" value="{{ $distAtual?->id }}">
                </div>

                {{-- UF (fixa do gestor) --}}
                @php $ufGestor = optional($pedido->gestor)->estado_uf; @endphp
                <div class="col-span-12 md:col-span-3">
                    <label class="block text-sm font-medium text-gray-800">UF (do gestor)</label>
                    <input
                        type="text"
                        id="ufDisplay"
                        value="{{ $ufGestor ?: '—' }}"
                        class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 shadow-sm"
                        readonly
                    >
                    <input type="hidden" id="ufHidden" name="state" value="{{ $ufGestor }}">
                </div>

                {{-- Cidade da Venda --}}
                <div class="col-span-12 md:col-span-6">
                    <label for="cidade_id" class="block text-sm font-medium text-gray-700">Cidade da Venda</label>
                    @php
                        $temDistOuGestor = old('distribuidor_id', $pedido->distribuidor_id) || old('gestor_id', $pedido->gestor_id);
                    @endphp
                    <select name="cidade_id" id="cidade_id" {{ $temDistOuGestor ? '' : 'disabled' }}
                            class="mt-1 block w-full rounded-md border-gray-300 {{ $temDistOuGestor ? '' : 'bg-gray-50' }} shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">{{ $temDistOuGestor ? '-- Selecione --' : '-- Selecione o gestor ou o distribuidor --' }}</option>
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
                                @php
                                    $qtd       = (int) ($p->pivot->quantidade ?? 0);
                                    $descPerc  = (float) ($p->pivot->desconto_item ?? 0);
                                    $precoTab  = (float) ($p->preco ?? 0);
                                    $precoDesc = $precoTab * (1 - ($descPerc / 100));
                                    $titulo    = $p->titulo ?: $p->nome;
                                @endphp
                                <tr class="border-t" data-index="{{ $loop->index }}">
                                    <td class="px-4 py-2 align-top">
                                        <input type="hidden" name="produtos[{{ $loop->index }}][id]" value="{{ $p->id }}" class="inputId">
                                        <div class="font-medium">{{ $titulo }}</div>
                                        <div class="text-xs text-gray-500">
                                            Tabela: R$ {{ number_format($precoTab, 2, ',', '.') }} •
                                            c/ desc ({{ number_format($descPerc,2,',','.') }}%): R$
                                            {{ number_format($precoDesc, 2, ',', '.') }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-2 text-center align-top">
                                        <input type="number" min="1"
                                            class="w-28 border rounded-lg px-2 py-1 inputQtd focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            name="produtos[{{ $loop->index }}][quantidade]"
                                            value="{{ old('produtos.'.$loop->index.'.quantidade', $qtd) }}" required>
                                    </td>
                                    <td class="px-4 py-2 align-top">
                                        <input type="number" min="0" max="100" step="0.01"
                                            class="w-36 border rounded-lg px-2 py-1 inputDesc focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            name="produtos[{{ $loop->index }}][desconto]"
                                            value="{{ old('produtos.'.$loop->index.'.desconto', $descPerc) }}">
                                    </td>
                                    <td class="px-4 py-2 text-right align-top">
                                        <button type="button" class="text-red-600 hover:underline btnRemoveRow">Remover</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
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
                        <option value="{{ $pp->id }}"
                                data-titulo="{{ $pp->titulo ?? $pp->nome }}"
                                data-preco="{{ (float) ($pp->preco ?? 0) }}">
                            {{ $pp->titulo ?? $pp->nome }} — Tabela: R$ {{ number_format((float)($pp->preco ?? 0), 2, ',', '.') }}
                        </option>
                    @endforeach
                </select>
                <input type="hidden" data-name="produtos[__INDEX__][id]" class="inputId">

                {{-- faixa de informações (atualiza em tempo real) --}}
                <div class="mt-2 rounded-md bg-white border p-2 text-xs text-gray-700 space-x-2 infoPreco hidden">
                    <span class="unit">Tabela: R$ 0,00</span>
                    <span>•</span>
                    <span class="unitDesc">c/ desc: R$ 0,00</span>
                </div>
            </td>
            <td class="px-4 py-3 align-top">
                <input type="number" min="1"
                    class="w-28 border rounded-lg px-2 py-1 inputQtd focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    data-name="produtos[__INDEX__][quantidade]" value="1" required>
            </td>
            <td class="px-4 py-3 align-top">
                <input type="number" min="0" max="100" step="0.01"
                    class="w-36 border rounded-lg px-2 py-1 inputDesc focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    data-name="produtos[__INDEX__][desconto]" value="0">
            </td>
            <td class="px-4 py-3 text-right align-top">
                <button type="button" class="text-red-600 hover:underline btnRemoveRow">Remover</button>
            </td>
        </tr>
    </template>

    {{-- ===== Dados JS compartilhados ===== --}}
    <script>
        const ALL_PRODUCTS = {!! $produtos->map(fn($p) => [
            'id'     => $p->id,
            'titulo' => $p->titulo ?? $p->nome,
            'preco'  => (float) ($p->preco ?? 0),
        ])->values()->toJson() !!};

        const fmtBRL = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });
        const getProductById = (id) => ALL_PRODUCTS.find(p => String(p.id) === String(id)) || null;
    </script>

    <script>
        /* =================== ESTADO INICIAL (único) =================== */
        window.PEDIDO_ATUAL = {
            distribuidor_id: @json(old('distribuidor_id', $pedido->distribuidor_id)),
            gestor_id:       @json(old('gestor_id', $pedido->gestor_id)),
            cidade_id:       @json(old('cidade_id', $pedido->cidades->pluck('id')->first())),
            uf_gestor:       @json(optional($pedido->gestor)->estado_uf),
        };

        /* =================== CIDADE: carregar por distribuidor/gestor =================== */
        const cidadeSelect = document.getElementById('cidade_id');
        const ufDisplay    = document.getElementById('ufDisplay');
        const ufHidden     = document.getElementById('ufHidden');

        function resetCidadeSelect(placeholder = '-- Selecione --') {
            if (!cidadeSelect) return;
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

        /* =================== PRODUTOS: sem duplicados + infos dinâmicas =================== */
        const tabelaProdutosBody = document.getElementById('tabelaProdutos');
        const btnAddRow          = document.getElementById('btnAddRow');
        const rowTemplate        = document.getElementById('rowTemplate');
        let nextIndex = tabelaProdutosBody ? tabelaProdutosBody.querySelectorAll('tr[data-index]').length : 0;

        function getSelectedProductIds() {
            if (!tabelaProdutosBody) return [];
            const ids = [];
            // hidden inputs de linhas existentes
            tabelaProdutosBody.querySelectorAll('input.inputId[name^="produtos["][name$="[id]"]').forEach(h => {
                const v = String(h.value || '').trim();
                if (v) ids.push(v);
            });
            // selects das novas linhas (se houver)
            tabelaProdutosBody.querySelectorAll('tr[data-index] select.produtoSelect').forEach(sel => {
                const v = String(sel.value || '').trim();
                if (v) ids.push(v);
            });
            return Array.from(new Set(ids));
        }

        function refreshAllProductSelectOptions() {
            if (!tabelaProdutosBody) return;
            const chosen = getSelectedProductIds();

            tabelaProdutosBody.querySelectorAll('tr[data-index] select.produtoSelect').forEach(sel => {
                const current = String(sel.value || '');
                const allOpts = Array.from(sel.querySelectorAll('option')).map(o => ({
                    value: o.value,
                    text:  o.textContent,
                    dataset: { titulo: o.dataset.titulo, preco: o.dataset.preco }
                }));

                sel.innerHTML = '';
                const placeholder = document.createElement('option');
                placeholder.value = '';
                placeholder.textContent = '— Selecionar produto —';
                sel.appendChild(placeholder);

                allOpts.forEach(({value, text, dataset}) => {
                    if (value === '') return;
                    const isExcluded = chosen.includes(value) && value !== current;
                    if (isExcluded) return;

                    const opt = document.createElement('option');
                    opt.value = value;
                    opt.textContent = text; // mantém "Título — Tabela: R$ ..."
                    if (dataset && dataset.titulo) opt.dataset.titulo = dataset.titulo;
                    if (dataset && dataset.preco)  opt.dataset.preco  = dataset.preco;

                    if (value === current) opt.selected = true;
                    sel.appendChild(opt);
                });
            });
        }

        function updateRowInfo(tr) {
            const sel   = tr.querySelector('select.produtoSelect');
            const descI = tr.querySelector('input.inputDesc');
            const info  = tr.querySelector('.infoPreco');
            if (!sel || !descI || !info) return;

            const pid   = sel.value || '';
            const prod  = getProductById(pid);
            const desc  = Math.max(0, Math.min(100, parseFloat(descI.value || '0')));
            const unit  = prod ? Number(prod.preco || 0) : 0;
            const unitD = unit * (1 - (desc / 100));

            info.querySelector('.unit').textContent     = `Tabela: ${fmtBRL.format(unit)}`;
            info.querySelector('.unitDesc').textContent = `c/ desc: ${fmtBRL.format(unitD)}`;
            info.classList.toggle('hidden', !prod);

            // (opcional) atualiza texto da opção selecionada incluindo desconto
            const optSel = sel.options[sel.selectedIndex];
            if (optSel && prod) {
                optSel.textContent = `${prod.titulo} — Tabela: ${fmtBRL.format(unit)} — c/ desc: ${fmtBRL.format(unitD)}`;
            }
        }

        function wireRowEvents(tr) {
            const sel   = tr.querySelector('select.produtoSelect');
            const hid   = tr.querySelector('input.inputId');
            const descI = tr.querySelector('input.inputDesc');

            if (sel) {
                sel.addEventListener('change', () => {
                    if (hid) hid.value = sel.value || '';
                    refreshAllProductSelectOptions();
                    updateRowInfo(tr);
                });
            }
            if (descI) {
                descI.addEventListener('input',  () => updateRowInfo(tr));
                descI.addEventListener('change', () => updateRowInfo(tr));
            }

            // primeira atualização
            updateRowInfo(tr);
        }

        function addProductRow() {
            if (!rowTemplate || !tabelaProdutosBody) return;

            const clone = rowTemplate.content.cloneNode(true);
            const html  = clone.firstElementChild.outerHTML.replaceAll('__INDEX__', String(nextIndex));
            const container = document.createElement('tbody');
            container.innerHTML = html;
            const tr = container.firstElementChild;

            tr.querySelectorAll('[data-name]').forEach(el => {
                el.setAttribute('name', el.getAttribute('data-name'));
                el.removeAttribute('data-name');
            });

            const select = tr.querySelector('select.produtoSelect');
            const hidden = tr.querySelector('input.inputId');

            // evento de remover
            tr.querySelector('.btnRemoveRow')?.addEventListener('click', () => {
                tr.remove();
                refreshAllProductSelectOptions();
            });

            // seta hidden quando escolher produto
            if (select) {
                select.addEventListener('change', () => {
                    hidden.value = select.value || '';
                    refreshAllProductSelectOptions();
                });
            }

            tabelaProdutosBody.appendChild(tr);
            refreshAllProductSelectOptions();
            wireRowEvents(tr); // <<< importante
            nextIndex++;
        }

        /* =================== BOOT =================== */
        document.addEventListener('DOMContentLoaded', async () => {
            // Preenche UF (fixa)
            const uf = window.PEDIDO_ATUAL?.uf_gestor || '';
            if (ufDisplay) ufDisplay.value = uf || '—';
            if (ufHidden)  ufHidden.value  = uf || '';

            // Carrega cidades com base no contexto atual
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

            // Produtos: linhas existentes (vindas do backend) não têm select,
            // mas podem ter inputDesc; nada a fazer nelas além do refresh dos selects futuros.
            refreshAllProductSelectOptions();

            // Botão adicionar linha
            btnAddRow?.addEventListener('click', addProductRow);

            // Remover linhas existentes (delegation)
            tabelaProdutosBody?.addEventListener('click', (e) => {
                const btn = e.target.closest('.btnRemoveRow');
                if (!btn) return;
                const tr = btn.closest('tr[data-index]');
                if (!tr) return;
                tr.remove();
                refreshAllProductSelectOptions();
            });

            // Limpeza final antes de enviar
            const form = document.getElementById('formPedidoEdit');
            form?.addEventListener('submit', () => {
                if (!tabelaProdutosBody) return;
                tabelaProdutosBody.querySelectorAll('tr[data-index]').forEach(tr => {
                    const hidden = tr.querySelector('input.inputId');
                    const qtd    = tr.querySelector('input.inputQtd');
                    const idVal  = parseInt(hidden?.value || '0', 10);
                    const qVal   = parseInt(qtd?.value || '0', 10);
                    if (!(idVal > 0 && qVal > 0)) tr.remove();
                });
            });
        });
    </script>

</x-app-layout>
