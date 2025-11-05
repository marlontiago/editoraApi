<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Editar Pedido #{{ $pedido->id }}</h2>
    </x-slot>

    @if (session('error'))
        <div class="bg-red-100 text-red-800 p-3 rounded mb-4">
            {{ session('error') }}
        </div>
    @endif

    <div class="max-w-6xl mx-auto p-6">
        @if ($errors->any())
            <div class="mb-6 rounded-md border border-red-300 bg-red-50 p-4 text-red-800">
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($errors->all() as $e)
                        <li class="text-sm">{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('success'))
            <div class="mb-6 rounded-md border border-green-300 bg-green-50 p-4 text-green-800">
                {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="{{ route('admin.pedidos.update', $pedido) }}"
              class="bg-white shadow rounded-lg p-6 grid grid-cols-12 gap-4" id="formPedidoEdit">
            @csrf
            @method('PUT')

            {{-- ==================== DADOS GERAIS (layout igual ao create) ==================== --}}

            {{-- Cliente (somente leitura visual; envia hidden) --}}
            <div class="col-span-12 md:col-span-6">
                <label for="cliente_id_display" class="block text-sm font-medium text-gray-700">Cliente</label>
                <select id="cliente_id_display" disabled
                        class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 shadow-sm">
                    <option value="{{ $pedido->cliente_id }}" selected>
                        {{ optional($pedido->cliente)->razao_social ?? optional($pedido->cliente)->nome ?? '—' }}
                    </option>
                </select>
                <input type="hidden" name="cliente_id" value="{{ old('cliente_id', $pedido->cliente_id) }}">
            </div>

            {{-- Gestor (somente leitura visual; envia hidden) --}}
            @php $gestorAtual = $pedido->gestor; @endphp
            <div class="col-span-12 md:col-span-6">
                <label class="block text-sm font-medium text-gray-700">Gestor</label>
                <input type="text" class="mt-1 w-full rounded-md border-gray-300 bg-gray-100 shadow-sm"
                       value="{{ $gestorAtual?->razao_social ?: '—' }}" readonly>
                <input type="hidden" name="gestor_id" value="{{ old('gestor_id', $gestorAtual?->id) }}">
            </div>

            {{-- Distribuidor (somente leitura visual; envia hidden) --}}
            @php $distAtual = $pedido->distribuidor; @endphp
            <div class="col-span-12 md:col-span-6">
                <label class="block text-sm font-medium text-gray-700">Distribuidor</label>
                <input type="text" class="mt-1 w-full rounded-md border-gray-300 bg-gray-100 shadow-sm"
                       value="{{ $distAtual?->razao_social ?: '—' }}" readonly>
                <input type="hidden" name="distribuidor_id" value="{{ old('distribuidor_id', $distAtual?->id) }}">
            </div>

            {{-- UF (fixa do gestor; somente leitura visual; envia hidden "state") --}}
            @php $ufGestor = optional($pedido->gestor)->estado_uf; @endphp
            <div class="col-span-12 md:col-span-6">
                <label class="block text-sm font-medium text-gray-800">UF</label>
                <input type="text" id="ufDisplay"
                       value="{{ $ufGestor ?: '—' }}"
                       class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 shadow-sm"
                       readonly>
                <input type="hidden" id="ufHidden" name="state" value="{{ old('state', $ufGestor) }}">
            </div>

            {{-- Cidade da Venda (DESABILITADA para edição; mantém o <select id="cidade_id"> para sua lógica JS, mas sem name) --}}
            <div class="col-span-12 md:col-span-6">
                <label for="cidade_id" class="block text-sm font-medium text-gray-700">Cidade da Venda</label>
                @php
                    $temDistOuGestor = $pedido->distribuidor_id || $pedido->gestor_id;
                    $cidadeAtualId   = old('cidade_id', $pedido->cidades->pluck('id')->first());
                    $cidadeAtualNome = optional($pedido->cidades->first())->name;
                @endphp
                <select id="cidade_id" name="cidade_id_display" disabled
                        class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 shadow-sm">
                    <option value="{{ $cidadeAtualId }}" selected>
                        {{ $cidadeAtualNome ?: '—' }}
                    </option>
                </select>
                <input type="hidden" id="cidade_id_hidden" name="cidade_id" value="{{ $cidadeAtualId }}">
            </div>

            {{-- Data (editável) --}}
            <div class="col-span-12 md:col-span-6">
                <label class="block text-sm font-medium text-gray-700">Data</label>
                <input type="date" name="data"
                       value="{{ old('data', \Carbon\Carbon::parse($pedido->data)->format('Y-m-d')) }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                       required>
            </div>

            {{-- Status (editável) --}}
            <div class="col-span-12 md:col-span-6">
                <label class="block text-sm font-medium text-gray-700">Status</label>
                @php $statuses = ['em_andamento' => 'Em andamento', 'finalizado' => 'Finalizado', 'cancelado' => 'Cancelado']; @endphp
                <select name="status"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        required>
                    @foreach ($statuses as $val => $label)
                        <option value="{{ $val }}" @selected(old('status', $pedido->status) === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Observações (editável) --}}
            <div class="col-span-12">
                <label class="block text-sm font-medium text-gray-700">Observações</label>
                <textarea name="observacoes" rows="3"
                          class="mt-1 w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                          placeholder="Anotações internas sobre o pedido (opcional)">{{ old('observacoes', $pedido->observacoes) }}</textarea>
            </div>

            {{-- ==================== PRODUTOS (mantida sua lógica atual) ==================== --}}
            <div class="col-span-12">
                <div class="bg-white p-4 rounded-lg border">
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
            </div>

            {{-- Botões --}}
            <div class="col-span-12 flex items-center justify-end gap-3 pt-2">
                <a href="{{ route('admin.pedidos.show', $pedido) }}"
                   class="px-4 py-2 rounded-lg border hover:bg-gray-50">
                    Cancelar
                </a>
                <button type="submit"
                        class="bg-blue-600 text-white px-5 py-2 rounded-lg hover:bg-blue-700 transition">
                    Salvar alterações
                </button>
            </div>
        </form>
    </div>

    {{-- ==================== TEMPLATE para NOVA linha ==================== --}}
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

    {{-- ==================== DADOS JS COMPARTILHADOS ==================== --}}
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
        /* =================== ESTADO INICIAL (mantido) =================== */
        window.PEDIDO_ATUAL = {
            distribuidor_id: @json(old('distribuidor_id', $pedido->distribuidor_id)),
            gestor_id:       @json(old('gestor_id', $pedido->gestor_id)),
            cidade_id:       @json(old('cidade_id', $pedido->cidades->pluck('id')->first())),
            uf_gestor:       @json(optional($pedido->gestor)->estado_uf),
        };

        /* ======= CIDADE: carregar por distribuidor/gestor (mantido) ======= */
        const cidadeSelect = document.getElementById('cidade_id');       // <select> DESABILITADO (somente exibição)
        const cidadeHidden = document.getElementById('cidade_id_hidden'); // hidden que será enviado
        const ufDisplay    = document.getElementById('ufDisplay');
        const ufHidden     = document.getElementById('ufHidden');

        function resetCidadeSelect(placeholder = '-- Selecione --') {
            if (!cidadeSelect) return;
            cidadeSelect.innerHTML = '';
            cidadeSelect.add(new Option(placeholder, ''));
            cidadeSelect.disabled = true; // permanece desabilitado no edit
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
                cidadeSelect.disabled = true;                 // mantém travado
                cidadeSelect.classList.add('bg-gray-100');    // visual de bloqueado
                // sincroniza hidden
                if (cidadeHidden) cidadeHidden.value = String(selectedCidadeId || cidadeSelect.value || '');
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
                cidadeSelect.disabled = true;
                cidadeSelect.classList.add('bg-gray-100');
                // sincroniza hidden
                if (cidadeHidden) cidadeHidden.value = String(selectedCidadeId || cidadeSelect.value || '');
                if (!cidades.length) resetCidadeSelect('Nenhuma cidade para a UF do gestor');
            } catch (e) {
                console.error(e);
                resetCidadeSelect('Falha ao carregar cidades');
            }
        }

        /* ======= PRODUTOS (mantida sua lógica) ======= */
        const tabelaProdutosBody = document.getElementById('tabelaProdutos');
        const btnAddRow          = document.getElementById('btnAddRow');
        const rowTemplate        = document.getElementById('rowTemplate');
        let nextIndex = tabelaProdutosBody ? tabelaProdutosBody.querySelectorAll('tr[data-index]').length : 0;

        function getSelectedProductIds() {
            if (!tabelaProdutosBody) return [];
            const ids = [];
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
                    opt.textContent = text;
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

            tr.querySelector('.btnRemoveRow')?.addEventListener('click', () => {
                tr.remove();
                refreshAllProductSelectOptions();
            });

            if (select) {
                select.addEventListener('change', () => {
                    hidden.value = select.value || '';
                    refreshAllProductSelectOptions();
                });
            }

            tabelaProdutosBody.appendChild(tr);
            refreshAllProductSelectOptions();
            wireRowEvents(tr);
            nextIndex++;
        }

        /* =================== BOOT =================== */
        document.addEventListener('DOMContentLoaded', async () => {
            // UF fixa (somente visual)
            const uf = window.PEDIDO_ATUAL?.uf_gestor || '';
            if (ufDisplay) ufDisplay.value = uf || '—';
            if (ufHidden)  ufHidden.value  = uf || '';

            // Carrega cidades (somente para exibição) e sincroniza o hidden
            const dId = window.PEDIDO_ATUAL?.distribuidor_id ?? null;
            const gId = window.PEDIDO_ATUAL?.gestor_id ?? null;
            const cId = window.PEDIDO_ATUAL?.cidade_id ?? null;

            if (dId) {
                await carregarCidadesPorDistribuidor(dId, cId);
            } else if (gId) {
                await carregarCidadesPorGestor(gId, cId);
            } else {
                resetCidadeSelect('-- Selecione o gestor ou o distribuidor --');
                if (cidadeHidden) cidadeHidden.value = '';
            }

            // Produtos existentes + futuros
            refreshAllProductSelectOptions();
            btnAddRow?.addEventListener('click', addProductRow);

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
