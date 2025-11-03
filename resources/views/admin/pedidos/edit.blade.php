{{-- resources/views/admin/pedidos/edit.blade.php --}}
<x-app-layout>
  <x-slot name="header">
    <h2 class="text-xl font-semibold text-gray-800">Editar Pedido #{{ $pedido->id }}</h2>
  </x-slot>

  @php
    // ===== Pré-cálculos para evitar @json complexos e facilitar o template =====
    $cidadeAtual     = optional($pedido->cidades)->first();
    $cidadeAtualId   = old('cidade_id', $cidadeAtual?->id);
    $cidadeAtualNome = $cidadeAtual?->name ?? '—';
    $ufAtual         = old('state', $pedido->state);

    $clienteNome = optional($pedido->cliente)->razao_social ?? optional($pedido->cliente)->nome ?? '—';
    $gestorNome  = optional($pedido->gestor)->razao_social ?? '—';
    $distNome    = optional($pedido->distribuidor)->razao_social ?? '—';

    // Produtos pré-carregados no formato esperado pelo JS do "create"
    $produtosOldEdit = old('produtos', $pedido->produtos->map(function($p) {
        return [
          'id'         => $p->id,
          'quantidade' => (int) ($p->pivot->quantidade ?? 1),
          'desconto'   => (float) ($p->pivot->desconto_item ?? 0),
        ];
    })->values()->all());

    $cfopLabels = $cfopLabels ?? config('cfop.labels', []);

    $statuses = [
      'em_andamento' => 'Em andamento',
      'finalizado'   => 'Finalizado',
      'cancelado'    => 'Cancelado',
    ];
  @endphp

  <div class="max-w-6xl mx-auto p-6">
    @if(session('success'))
      <div class="mb-6 rounded-md border border-green-300 bg-green-50 p-4 text-green-800">
        {{ session('success') }}
      </div>
    @endif

    @if(session('error'))
      <div class="mb-6 rounded-md border border-red-300 bg-red-50 p-4 text-red-800">
        {{ session('error') }}
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

    <form id="pedido-edit-form" action="{{ route('admin.pedidos.update', $pedido) }}" method="POST"
          class="bg-white shadow rounded-lg p-6 grid grid-cols-12 gap-4">
      @csrf
      @method('PUT')

      {{-- ====== Somente visual (com hidden para enviar valor) ====== --}}

      {{-- Cliente (visual) --}}
      <div class="col-span-12 md:col-span-6">
        <label class="block text-sm font-medium text-gray-700">Cliente</label>
        <input type="text" class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 shadow-sm" value="{{ $clienteNome }}" readonly>
        <input type="hidden" name="cliente_id" value="{{ old('cliente_id', $pedido->cliente_id) }}">
      </div>

      {{-- Gestor (visual) --}}
      <div class="col-span-12 md:col-span-6">
        <label class="block text-sm font-medium text-gray-700">Gestor</label>
        <input type="text" class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 shadow-sm" value="{{ $gestorNome }}" readonly>
        <input type="hidden" name="gestor_id" value="{{ old('gestor_id', $pedido->gestor_id) }}">
      </div>

      {{-- Distribuidor (visual) --}}
      <div class="col-span-12 md:col-span-6">
        <label class="block text-sm font-medium text-gray-700">Distribuidor</label>
        <input type="text" class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 shadow-sm" value="{{ $distNome }}" readonly>
        <input type="hidden" name="distribuidor_id" value="{{ old('distribuidor_id', $pedido->distribuidor_id) }}">
      </div>

      {{-- UF (visual) --}}
      <div class="col-span-12 md:col-span-3">
        <label class="block text-sm font-medium text-gray-800">UF</label>
        <input type="text" class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 shadow-sm" value="{{ $ufAtual ?: '—' }}" readonly>
        <input type="hidden" name="state" value="{{ $ufAtual }}">
      </div>

      {{-- Cidade da Venda (visual) --}}
      <div class="col-span-12 md:col-span-9">
        <label class="block text-sm font-medium text-gray-700">Cidade da Venda</label>
        <input type="text" class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 shadow-sm" value="{{ $cidadeAtualNome }}" readonly>
        <input type="hidden" name="cidade_id" value="{{ $cidadeAtualId }}">
      </div>

      {{-- ====== Campos editáveis ====== --}}

      {{-- Data --}}
      <div class="col-span-12 md:col-span-6">
        <label for="data" class="block text-sm font-medium text-gray-700">Data</label>
        <input type="date" name="data" id="data"
               value="{{ old('data', \Carbon\Carbon::parse($pedido->data)->format('Y-m-d')) }}"
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
      </div>

      {{-- Status (NOVO) --}}
      <div class="col-span-12 md:col-span-6">
        <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
        <select name="status" id="status" required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
          @foreach($statuses as $value => $label)
            <option value="{{ $value }}" @selected(old('status', $pedido->status) === $value)>{{ $label }}</option>
          @endforeach
        </select>
      </div>

      {{-- CFOP (opcional) --}}
      <div class="col-span-12 md:col-span-6">
        <label for="cfop" class="block text-sm font-medium text-gray-700">CFOP (opcional)</label>
        @if(!empty($cfopLabels))
          <select name="cfop" id="cfop"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            <option value="">-- Selecione --</option>
            @foreach($cfopLabels as $code => $label)
              <option value="{{ $code }}" @selected(old('cfop', $pedido->cfop) === $code)>{{ $code }} — {{ $label }}</option>
            @endforeach
          </select>
          <p class="text-xs text-gray-500 mt-1">Este CFOP será usado como padrão ao emitir a Nota.</p>
        @else
          <input type="text" name="cfop" id="cfop" value="{{ old('cfop', $pedido->cfop) }}" pattern="\d{4}" maxlength="4"
                 class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                 placeholder="Ex.: 5910" />
          <p class="text-xs text-gray-500 mt-1">Informe 4 dígitos (ex.: 5910). Configure labels em <code>config/cfop.php</code>.</p>
        @endif
      </div>

      {{-- ===================== COLEÇÕES (visual/ação igual ao create) ===================== --}}
      <div class="col-span-12">
        <label class="block text-sm font-medium text-gray-700">Coleções</label>

        <div class="mt-2 grid grid-cols-12 gap-3 items-end">
          <div class="col-span-12 md:col-span-6">
            <select id="colecao_select" name="colecao_id"
                    class="block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
              <option value="">-- Selecione a coleção --</option>
            </select>
          </div>

          <div class="col-span-6 md:col-span-3">
            <label class="block text-sm font-medium text-gray-700">Quantidade padrão</label>
            <input type="number" id="colecao_qtd" name="colecao_qtd" min="1"
                   value="{{ old('colecao_qtd', 1) }}"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
          </div>

          <div class="col-span-6 md:col-span-2">
            <label class="block text-sm font-medium text-gray-700">Desc. padrão (%)</label>
            <input type="number" id="colecao_desc" name="colecao_desc" min="0" max="100" step="0.01"
                   value="{{ old('colecao_desc', 0) }}"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
          </div>

          <div class="col-span-12 md:col-span-1">
            <button type="button" id="btn_add_colecao"
                    class="inline-flex w-full items-center justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700">
              + Adicionar
            </button>
          </div>
        </div>

        <div id="colecao-preview" class="mt-3 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3"></div>
        <p class="text-xs text-gray-500 mt-2">Use uma coleção para adicionar/atualizar rapidamente os itens.</p>
      </div>
      {{-- =================== FIM COLEÇÕES =================== --}}

      {{-- Produtos (mesmo componente do create) --}}
      <div class="col-span-12">
        <label class="block text-sm font-medium text-gray-700">Produtos</label>
        <div id="produtos-container" class="space-y-4 mt-2"></div>

        <button type="button" onclick="adicionarProduto()"
                class="mt-3 inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
          + Adicionar Produto
        </button>
      </div>

      {{-- Observações --}}
      <div class="col-span-12">
        <label class="block text-sm font-medium text-gray-700">Observações</label>
        <textarea name="observacoes" rows="3"
                  class="mt-1 w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  placeholder="Anotações internas sobre o pedido (opcional)">{{ old('observacoes', $pedido->observacoes) }}</textarea>
      </div>

      {{-- Botões --}}
      <div class="col-span-12 flex justify-between md:justify-end gap-3 pt-4">
        <a href="{{ route('admin.pedidos.show', $pedido) }}"
           class="inline-flex h-10 items-center rounded-md border px-4 text-sm hover:bg-gray-50">
          Cancelar
        </a>
        <button type="submit"
                class="inline-flex h-10 items-center rounded-md bg-green-600 px-5 text-sm font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
          Salvar alterações
        </button>
      </div>
    </form>
  </div>

  {{-- ===== DADOS PARA JS (simples) ===== --}}
  <script>
    const ALL_PRODUCTS     = @json($produtos ?? []);
    const ALL_COLLECTIONS  = @json($colecoes ?? []);
    const OLD_PRODUTOS     = @json($produtosOldEdit);
  </script>

  {{-- ===== PRODUTOS (mesmo JS do create) ===== --}}
  <script>
    let produtoIndex = 0;
    const container = document.getElementById('produtos-container');
    const addBtn    = document.querySelector('button[onclick="adicionarProduto()"]');
    const fmtBRL    = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });

    function getProductById(id) { return (ALL_PRODUCTS || []).find(p => String(p.id) === String(id)) || null; }
    function getSelectedProductIds() {
      return Array.from(container.querySelectorAll('select[name^="produtos["][name$="[id]"]'))
        .map(sel => sel.value).filter(v => v !== '');
    }
    function buildOptions(excludeIds = [], selectedId = null) {
      const frag = document.createDocumentFragment();
      frag.append(new Option('-- Selecione --', ''));
      (ALL_PRODUCTS || []).forEach(p => {
        const isSelected = String(p.id) === String(selectedId);
        const isExcluded = excludeIds.includes(String(p.id));
        if (isExcluded && !isSelected) return;
        const opt = new Option(p.titulo ?? p.nome ?? `#${p.id}`, p.id);
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
      const maxReached = chosen.length >= (ALL_PRODUCTS || []).length;
      addBtn.disabled = maxReached;
      addBtn.classList.toggle('opacity-50', maxReached);
      addBtn.title = maxReached ? 'Todos os produtos já foram adicionados' : '';
    }
    function makeProdutoRow(preset = {}) {
      const idx = produtoIndex++;
      const row = document.createElement('div');
      row.className = 'produto border p-4 rounded-md bg-gray-50 grid grid-cols-12 gap-4 items-start';
      row.innerHTML = `
        <div class="col-span-12 md:col-span-6 flex items-start gap-3">
          <div class="w-20 h-20 flex-shrink-0">
            <img src="" alt="Imagem do produto" class="product-thumb w-20 h-20 object-cover rounded-md shadow-sm" />
          </div>
          <div class="flex-1">
            <label class="block text-sm font-medium text-gray-700">Produto</label>
            <select name="produtos[${idx}][id]"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></select>
          </div>
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

        <div class="col-span-12">
          <div class="mt-2 rounded-md bg-white border p-3 text-sm text-gray-700 space-x-3 calc-area">
            <span><span class="font-medium">Preço de tabela:</span> <span class="unit-price">R$ 0,00</span></span>
            <span>•</span>
            <span><span class="font-medium">Preço com desconto:</span> <span class="unit-disc">R$ 0,00</span></span>
            <span>•</span>
            <span><span class="font-medium">Total:</span> <span class="total-tabela">R$ 0,00</span></span>
            <span>•</span>
            <span><span class="font-medium">Total com desconto:</span> <span class="total-desc">R$ 0,00</span></span>
          </div>
        </div>
      `;
      const sel = row.querySelector('select');
      sel.append(buildOptions(getSelectedProductIds(), preset.id ?? null));
      return row;
    }
    function calcRow(row) {
      const sel = row.querySelector('select[name^="produtos["][name$="[id]"]');
      const qEl = row.querySelector('input[name^="produtos["][name$="[quantidade]"]');
      const dEl = row.querySelector('input[name^="produtos["][name$="[desconto]"]');

      const unitSpan   = row.querySelector('.unit-price');
      const unitDSpan  = row.querySelector('.unit-disc');
      const totalSpan  = row.querySelector('.total-tabela');
      const totalDSpan = row.querySelector('.total-desc');
      const imgEl      = row.querySelector('.product-thumb');

      const pid   = sel?.value || '';
      theProd     = getProductById(pid);
      const qtd   = Math.max(1, parseInt(qEl?.value || '1', 10));
      const desc  = Math.max(0, Math.min(100, parseFloat(dEl?.value || '0')));

      const unit  = theProd ? Number(theProd.preco || 0) : 0;
      const unitD = unit * (1 - (desc / 100));
      const totalTabela = unit  * qtd;
      const totalDesc   = unitD * qtd;

      unitSpan.textContent   = fmtBRL.format(unit);
      unitDSpan.textContent  = fmtBRL.format(unitD);
      totalSpan.textContent  = fmtBRL.format(totalTabela);
      totalDSpan.textContent = fmtBRL.format(totalDesc);

      const PLACEHOLDER = 'data:image/svg+xml;utf8,' + encodeURIComponent(
        '<svg xmlns="http://www.w3.org/2000/svg" width="80" height="80"><rect width="100%" height="100%" fill="#E5E7EB"/><text x="50%" y="50%" alignment-baseline="middle" text-anchor="middle" font-size="10" fill="#9CA3AF">Sem imagem</text></svg>'
      );
      if (!theProd) { imgEl.src = PLACEHOLDER; imgEl.alt = 'Sem produto'; return; }

      if (theProd.imagem) {
        const testImg = new Image();
        testImg.onload = () => { imgEl.src = theProd.imagem; imgEl.alt = theProd.titulo || 'Produto'; };
        testImg.onerror = () => { imgEl.src = PLACEHOLDER; imgEl.alt = theProd.titulo || 'Sem imagem'; };
        testImg.src = theProd.imagem;
      } else {
        imgEl.src = PLACEHOLDER;
        imgEl.alt = theProd.titulo || 'Sem imagem';
      }
    }
    function calcAll() { container.querySelectorAll('.produto').forEach(calcRow); }
    function adicionarProduto(preset = {}) {
      const row = makeProdutoRow(preset);
      container.appendChild(row);
      refreshAllProductSelects();
      calcRow(row);
    }
    container.addEventListener('change', (e) => {
      if (e.target.matches('select[name^="produtos["][name$="[id]"]')) {
        refreshAllProductSelects();
        calcRow(e.target.closest('.produto'));
      }
      if (e.target.matches('input[name^="produtos["][name$="[quantidade]"]')
          || e.target.matches('input[name^="produtos["][name$="[desconto]"]')) {
        calcRow(e.target.closest('.produto'));
      }
    });
    container.addEventListener('input', (e) => {
      if (e.target.matches('input[name^="produtos["][name$="[quantidade]"]')
          || e.target.matches('input[name^="produtos["][name$="[desconto]"]')) {
        calcRow(e.target.closest('.produto'));
      }
    });
    container.addEventListener('click', (e) => {
      if (e.target.closest('.remove-row')) {
        e.target.closest('.produto').remove();
        refreshAllProductSelects();
        calcAll();
      }
    });
    document.addEventListener('DOMContentLoaded', () => {
      if (Array.isArray(OLD_PRODUTOS) && OLD_PRODUTOS.length) {
        OLD_PRODUTOS.forEach(p => adicionarProduto(p));
        calcAll();
      }
    });
  </script>

  {{-- ===== COLEÇÕES (popular select + preview + adicionar itens) ===== --}}
  <script>
    const colecaoSelect = document.getElementById('colecao_select');
    const colecaoQtdEl  = document.getElementById('colecao_qtd');
    const colecaoDescEl = document.getElementById('colecao_desc');
    const colecaoPrev   = document.getElementById('colecao-preview');

    function getCollectionById(id) {
      return (ALL_COLLECTIONS || []).find(c => String(c.id) === String(id)) || null;
    }
    document.addEventListener('DOMContentLoaded', () => {
      if (!colecaoSelect) return;
      colecaoSelect.innerHTML = '';
      colecaoSelect.append(new Option('-- Selecione a coleção --', ''));
      (ALL_COLLECTIONS || []).forEach(c => {
        const qty = Array.isArray(c.produtos) ? c.produtos.length : 0;
        colecaoSelect.append(new Option(`${c.nome} (${qty} itens)`, c.id));
      });
    });
    function renderColecaoPreview() {
      const id  = colecaoSelect.value;
      const col = getCollectionById(id);
      const qtd = Math.max(1, parseInt(colecaoQtdEl.value || '1', 10));
      const des = Math.max(0, Math.min(100, parseFloat(colecaoDescEl.value || '0')));
      colecaoPrev.innerHTML = '';
      if (!col || !Array.isArray(col.produtos) || col.produtos.length === 0) return;

      const fmt = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });
      col.produtos.forEach(p => {
        const unit  = Number(p.preco || 0);
        const uDesc = unit * (1 - (des / 100));
        const tot   = unit  * qtd;
        const totD  = uDesc * qtd;

        const card = document.createElement('div');
        card.className = 'border rounded-lg p-3 bg-gray-50 flex items-center gap-3';

        const img = document.createElement('img');
        img.className = 'w-12 h-12 rounded object-cover ring-1 ring-gray-200 flex-shrink-0';
        img.alt = p.titulo || 'Produto';
        img.src = p.imagem || ('data:image/svg+xml;utf8,' + encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48"><rect width="100%" height="100%" fill="#E5E7EB"/><text x="50%" y="50%" alignment-baseline="middle" text-anchor="middle" font-size="9" fill="#9CA3AF">Sem</text></svg>'));

        const info = document.createElement('div');
        info.className = 'flex-1 min-w-0';
        info.innerHTML = `
          <div class="text-sm font-medium text-gray-800 truncate">${p.titulo}</div>
          <div class="text-xs text-gray-600 mt-1 space-x-2">
            <span>Unit: <strong>${fmt.format(unit)}</strong></span>
            <span>Desc: <strong>${fmt.format(uDesc)}</strong></span>
          </div>
          <div class="text-xs text-gray-700 mt-1 space-x-2">
            <span>Total: <strong>${fmt.format(tot)}</strong></span>
            <span>c/ desc: <strong>${fmt.format(totD)}</strong></span>
          </div>
        `;
        card.appendChild(img);
        card.appendChild(info);
        colecaoPrev.appendChild(card);
      });
    }
    colecaoSelect?.addEventListener('change', renderColecaoPreview);
    colecaoQtdEl?.addEventListener('input', renderColecaoPreview);
    colecaoDescEl?.addEventListener('input', renderColecaoPreview);

    document.getElementById('btn_add_colecao')?.addEventListener('click', () => {
      const colId = colecaoSelect.value;
      if (!colId) return;
      const col   = getCollectionById(colId);
      if (!col) return;

      const qtd = Math.max(1, parseInt(colecao_qtd.value || '1', 10));
      const des = Math.max(0, Math.min(100, parseFloat(colecao_desc.value || '0')));
      const chosenSet = new Set(getSelectedProductIds().map(String));

      for (const p of (col.produtos || [])) {
        if (chosenSet.has(String(p.id))) {
          const selects = container.querySelectorAll('select[name^="produtos["][name$="[id]"]');
          for (const sel of selects) {
            if (String(sel.value) === String(p.id)) {
              const row = sel.closest('.produto');
              const qEl = row.querySelector('input[name^="produtos["][name$="[quantidade]"]');
              const dEl = row.querySelector('input[name^="produtos["][name$="[desconto]"]');
              qEl.value = Math.max(1, parseInt(qEl.value || '1', 10)) + qtd;
              dEl.value = des;
              calcRow(row);
            }
          }
        } else {
          adicionarProduto({ id: p.id, quantidade: qtd, desconto: des });
        }
      }
      refreshAllProductSelects();
      calcAll();
    });

    // Guard de submissão: exige ao menos 1 produto ou uma coleção
    document.getElementById('pedido-edit-form').addEventListener('submit', function(e) {
      const selects = Array.from(container.querySelectorAll('select[name^="produtos["][name$="[id]"]'));
      const selectedIds = selects.map(s => s.value).filter(v => v !== '');
      if (selectedIds.length === 0) {
        if (!document.getElementById('colecao_select').value) {
          alert('Selecione ao menos um produto ou uma coleção.');
          e.preventDefault();
          return false;
        }
      }
    });
  </script>
</x-app-layout>
