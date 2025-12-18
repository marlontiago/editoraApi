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
            <option value="{{ $gestor->id }}" data-uf="{{ $gestor->estado_uf }}" @selected(old('gestor_id') == $gestor->id)>{{ $gestor->razao_social }}</option>
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
            <option value="{{ $d->id }}"
                    data-gestor-id="{{ $d->gestor_id ?? '' }}"
                    @selected(old('distribuidor_id') == $d->id)>
              {{ $d->razao_social }}
            </option>
          @endforeach
        </select>
      </div>

      {{-- UF --}}
      <div class="col-span-12 md:col-span-6">
        <label for="state" class="block text-sm font-medium text-gray-800">UF</label>
        <select name="state" id="state"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
          <option value="">-- Selecione --</option>
          @foreach($cidadesUF as $uf)
            <option value="{{ $uf }}" @selected(old('state') == $uf)>{{ $uf }}</option>
          @endforeach
        </select>
      </div>

      {{-- Cidade da Venda --}}
      <div class="col-span-12 md:col-span-6">
        <label for="cidade_id" class="block text-sm font-medium text-gray-700">Cidade da Venda</label>
        @php $temDistOuGestor = old('distribuidor_id') || old('gestor_id') || old('state'); @endphp
        <select name="cidade_id" id="cidade_id" {{ $temDistOuGestor ? '' : 'disabled' }}
                class="mt-1 block w-full rounded-md border-gray-300 {{ $temDistOuGestor ? '' : 'bg-gray-50' }} shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
          <option value="">{{ $temDistOuGestor ? '-- Selecione --' : '-- Selecione o gestor, distribuidor ou UF --' }}</option>
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

        <div class="flex items-center gap-2 mt-2">
          <button type="button" onclick="adicionarProduto()"
                  class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
            + Adicionar Produto
          </button>

          {{-- NOVO: botão Adicionar Coleção --}}
          <button type="button" id="btn-add-colecao"
                  class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
            + Adicionar Coleção
          </button>
        </div>

        <div id="produtos-container" class="space-y-4 mt-4"></div>
        {{-- Aviso quando não houver itens --}}
        <div id="produtos-empty" class="mt-2 text-sm text-gray-500">
          Nenhum produto adicionado. Use “Adicionar Produto” ou “Adicionar Coleção”.
        </div>
      </div>

      {{-- Observações --}}
      <div class="col-span-12">
        <label class="block text-sm font-medium text-gray-700">Observações</label>
        <textarea name="observacoes" rows="3"
                  class="mt-1 w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  placeholder="Anotações internas sobre o pedido (opcional)">{{ old('observacoes') }}</textarea>
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

  {{-- ===== MODAL: ADICIONAR COLEÇÃO ===== --}}
  <div id="colecao-modal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/40" data-close></div>
    <div class="absolute inset-0 flex items-start justify-center p-4 pt-16 sm:pt-24 overflow-y-auto">
      <div class="w-full max-w-lg rounded-xl bg-white shadow-xl border max-h-[90vh] overflow-y-auto">
        <div class="border-b px-5 py-3">
          <h3 class="text-lg font-semibold text-gray-800">Adicionar Coleção</h3>
        </div>
        <div class="px-5 py-4 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700" for="colecao_select">Selecione a coleção</label>
            <select id="colecao_select"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
              <option value="">-- Selecione --</option>
              @foreach(($colecoes ?? []) as $c)
                <option value="{{ $c->id }}">{{ $c->nome ?? $c->codigo ?? ('Coleção #'.$c->id) }}</option>
              @endforeach
            </select>
          </div>

          <div class="grid grid-cols-12 gap-4">
            <div class="col-span-12 md:col-span-6">
              <label for="colecao_qtd" class="block text-sm font-medium text-gray-700">Quantidade por item</label>
              <input type="number" id="colecao_qtd" min="1" value="1"
                     class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div class="col-span-12 md:col-span-6">
              <label for="colecao_desc" class="block text-sm font-medium text-gray-700">Desconto (%)</label>
              <input type="number" id="colecao_desc" min="0" max="100" step="0.01" value="0"
                     class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>
          </div>

          <p class="text-xs text-gray-500">
            Ao confirmar, todos os produtos dessa coleção serão adicionados abaixo usando a quantidade e o desconto informados.
            Produtos já selecionados não serão duplicados.
          </p>
          <div id="colecao-feedback" class="hidden text-sm"></div>
        </div>
        <div class="flex items-center justify-end gap-2 px-5 py-3 border-t">
          <button type="button" class="rounded-md border px-4 py-2 text-sm hover:bg-gray-50" data-close>Cancelar</button>
          <button type="button" id="confirm-add-colecao"
                  class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
            Adicionar
          </button>
        </div>
      </div>
    </div>
  </div>

  {{-- ===== OVERLAY GLOBAL DE LOADING ===== --}}
  <div id="loading-overlay" class="fixed inset-0 z-[9999] hidden">
    <div class="absolute inset-0 bg-black/40"></div>
    <div class="absolute inset-0 flex items-center justify-center">
      <div class="bg-white rounded-xl shadow-lg px-6 py-4 flex flex-col items-center gap-3 border">
        <div class="h-8 w-8 rounded-full border-4 border-blue-500 border-t-transparent animate-spin"></div>
        <p class="text-sm text-gray-700">
          Adicionando produtos da coleção, aguarde...
        </p>
      </div>
    </div>
  </div>

  {{-- ===== DADOS PARA JS ===== --}}
  <script>
    const ALL_PRODUCTS = {!! $produtos->toJson() !!};
    const ALL_COLECOES = @json($colecoes ?? []);

    const OLD_PRODUTOS     = @json(old('produtos', []));
    const OLD_DISTRIBUIDOR = @json(old('distribuidor_id'));
    const OLD_GESTOR       = @json(old('gestor_id'));
    const OLD_CIDADE       = @json(old('cidade_id'));
  </script>

  {{-- ===== PRODUTOS ===== --}}
    
  <script>
    let produtoIndex = 0;
    const container = document.getElementById('produtos-container');
    const addBtn    = document.querySelector('button[onclick="adicionarProduto()"]');

    const fmtBRL = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });

    function getProductById(id) {
      return ALL_PRODUCTS.find(p => String(p.id) === String(id)) || null;
    }

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
        const opt = new Option(p.titulo, p.id);
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
      const prod  = getProductById(pid);
      const qtd   = Math.max(1, parseInt(qEl?.value || '1', 10));
      const desc  = Math.max(0, Math.min(100, parseFloat(dEl?.value || '0')));

      const unit  = prod ? Number(prod.preco || 0) : 0;
      const unitD = unit * (1 - (desc / 100));
      const totalTabela = unit  * qtd;
      const totalDesc   = unitD * qtd;

      unitSpan.textContent   = fmtBRL.format(unit);
      unitDSpan.textContent  = fmtBRL.format(unitD);
      totalSpan.textContent  = fmtBRL.format(totalTabela);
      totalDSpan.textContent = fmtBRL.format(totalDesc);

      const PLACEHOLDER = 'data:image/svg+xml;utf8,' + encodeURIComponent(
        '<svg xmlns="http://www.w3.org/2000/svg" width="80" height="80">' +
          '<rect width="100%" height="100%" fill="#E5E7EB"/>' +
          '<text x="50%" y="50%" alignment-baseline="middle" text-anchor="middle" font-size="10" fill="#9CA3AF">Sem imagem</text>' +
        '</svg>'
      );

      if (!prod) {
        imgEl.src = PLACEHOLDER;
        imgEl.alt = 'Sem produto';
        return;
      }

      if (prod.imagem) {
        const testImg = new Image();
        testImg.onload = () => {
          imgEl.src = prod.imagem;
          imgEl.alt = prod.titulo || 'Produto';
        };
        testImg.onerror = () => {
          imgEl.src = PLACEHOLDER;
          imgEl.alt = prod.titulo || 'Sem imagem';
          console.warn('Falha ao carregar imagem:', prod.imagem);
        };
        testImg.src = prod.imagem;
      } else {
        imgEl.src = PLACEHOLDER;
        imgEl.alt = prod.titulo || 'Sem imagem';
      }
    }

    function calcAll() {
      container.querySelectorAll('.produto').forEach(calcRow);
    }

    // BASE: usada tanto pelo clique normal quanto pelo bulk da coleção
    function adicionarProdutoBase(preset = {}, opts = {}) {
      const { withCalc = true, withRefresh = true } = opts;

      const empty = document.getElementById('produtos-empty');
      if (empty) empty.classList.add('hidden');

      const row = makeProdutoRow(preset);
      container.appendChild(row);

      if (withRefresh) refreshAllProductSelects();
      if (withCalc) calcRow(row);

      return row;
    }

    // Clique normal em “Adicionar Produto”
    function adicionarProduto(preset = {}) {
      return adicionarProdutoBase(preset, { withCalc: true, withRefresh: true });
    }

    container.addEventListener('change', (e) => {
      if (e.target.matches('select[name^="produtos["][name$="[id]"]')) {
        refreshAllProductSelects();
        calcRow(e.target.closest('.produto'));
      }
      if (
        e.target.matches('input[name^="produtos["][name$="[quantidade]"]') ||
        e.target.matches('input[name^="produtos["][name$="[desconto]"]')
      ) {
        calcRow(e.target.closest('.produto'));
      }
    });

    container.addEventListener('input', (e) => {
      if (
        e.target.matches('input[name^="produtos["][name$="[quantidade]"]') ||
        e.target.matches('input[name^="produtos["][name$="[desconto]"]')
      ) {
        calcRow(e.target.closest('.produto'));
      }
    });

    container.addEventListener('click', (e) => {
      if (e.target.closest('.remove-row')) {
        e.target.closest('.produto').remove();
        refreshAllProductSelects();
        calcAll();
        if (container.querySelectorAll('.produto').length === 0) {
          const empty = document.getElementById('produtos-empty');
          if (empty) empty.classList.remove('hidden');
        }
      }
    });

    document.addEventListener('DOMContentLoaded', () => {
      if (Array.isArray(OLD_PRODUTOS) && OLD_PRODUTOS.length) {
        OLD_PRODUTOS.forEach(p => adicionarProduto(p));
        calcAll();
      } else {
        const empty = document.getElementById('produtos-empty');
        if (empty) empty.classList.remove('hidden');
      }
    });
  </script>


  {{-- ===== GESTOR / DISTRIBUIDOR / CIDADES ===== --}}
  <script>
    const distSelect = document.getElementById('distribuidor_id');
    const originalDistOptions = Array.from(distSelect.options).map(opt => ({
      value: opt.value,
      text: opt.text,
      gestorId: opt.getAttribute('data-gestor-id') || '',
      selected: opt.selected
    }));

    function rebuildDistribuidorOptions(gestorId) {
      const hadValue = distSelect.value;
      distSelect.innerHTML = '';

      const first = new Option('-- Sem distribuidor --', '');
      distSelect.add(first);

      const pool = (!gestorId)
        ? originalDistOptions
        : originalDistOptions.filter(o => o.value === '' || (o.gestorId && String(o.gestorId) === String(gestorId)));

      pool.forEach(o => {
        if (o.value === '') return;
        const opt = new Option(o.text, o.value);
        opt.setAttribute('data-gestor-id', o.gestorId || '');
        distSelect.add(opt);
      });

      const stillValid = Array.from(distSelect.options).some(o => o.value === hadValue);
      distSelect.value = stillValid ? hadValue : '';
    }

    document.getElementById('gestor_id').addEventListener('change', function () {
      rebuildDistribuidorOptions(this.value || '');
    });

    document.addEventListener('DOMContentLoaded', function () {
      const oldGestor = @json(old('gestor_id'));
      rebuildDistribuidorOptions(oldGestor || '');
    });
  </script>

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

    function cidadeLabel(c, ufFallback = null) {
      const uf = (c.state && String(c.state).trim()) || (ufFallback && String(ufFallback).trim()) || '';
      return uf ? `${c.name} - ${uf}` : c.name;
    }

    function rebuildCidadeOptions(cidades, { allowOccupied = false, ufFallback = null } = {}) {
      cidadeSelect.innerHTML = '';
      cidadeSelect.add(new Option('-- Selecione --', ''));

      const sorted = [...(cidades || [])].sort((a, b) => {
        const la = cidadeLabel(a, ufFallback).toLocaleLowerCase('pt-BR');
        const lb = cidadeLabel(b, ufFallback).toLocaleLowerCase('pt-BR');
        return la.localeCompare(lb, 'pt-BR', { sensitivity: 'base' });
      });

      sorted.forEach(c => {
        const opt = new Option(cidadeLabel(c, ufFallback), c.id);
        const isOccupied = Boolean(c.ocupado);
        const distName   = c.distribuidor_nome || null;

        if (isOccupied && !allowOccupied) {
          opt.disabled = true;
          opt.text = `${cidadeLabel(c, ufFallback)} ${distName ? `(ocupada por ${distName})` : '(ocupada)'}`;
        }

        cidadeSelect.add(opt);
      });

      cidadeSelect.disabled = false;
      cidadeSelect.classList.remove('bg-gray-50');
    }


    async function carregarCidadesPorDistribuidor(distribuidorId, selectedCidadeId = null) {
      resetCidadeSelect('-- Carregando... --');
      try {
        const resp = await fetch(`/admin/cidades/por-distribuidor/${distribuidorId}`);
        if (!resp.ok) throw new Error(`HTTP ${resp.status}`);
        const cidades = await resp.json();
        rebuildCidadeOptions(cidades, { allowOccupied: true, ufFallback: null });
        if (selectedCidadeId) cidadeSelect.value = String(selectedCidadeId);
      } catch (e) {
        console.error(e);
        resetCidadeSelect('Falha ao carregar cidades');
      }
    }

    async function carregarCidadesPorUF(uf, selectedCidadeId = null) {
      resetCidadeSelect('-- Carregando... --');
      try {
        const resp = await fetch(`/admin/cidades/por-uf/${encodeURIComponent(uf)}?with_occupancy=1`);
        if (!resp.ok) throw new Error(`HTTP ${resp.status}`);
        const cidades = await resp.json();

        const hasDistribuidor = Boolean(distribuidorSelect.value);
        const hasGestor       = Boolean(gestorSelect.value);

        // ✅ gestor pode escolher qualquer cidade (mesmo ocupada)
        const allowOccupied = hasDistribuidor || hasGestor;

        rebuildCidadeOptions(cidades, { allowOccupied, ufFallback: uf });

        if (selectedCidadeId) cidadeSelect.value = String(selectedCidadeId);
      } catch (e) {
        console.error(e);
        resetCidadeSelect('Falha ao carregar cidades');
      }
    }


    distribuidorSelect.addEventListener('change', async function () {
      const distId = this.value || null;
      const uf = stateSelect.value || null;

      if (distId) {
        await carregarCidadesPorDistribuidor(distId, null);
      } else if (uf) {
        await carregarCidadesPorUF(uf, null);
      } else {
        resetCidadeSelect('-- Selecione gestor, distribuidor ou UF --');
      }
    });

gestorSelect.addEventListener('change', async function () {
  const uf = stateSelect.value || null;
  const distId = distribuidorSelect.value || null;

  if (distId) {
    await carregarCidadesPorDistribuidor(distId, null);
  } else if (uf) {
    await carregarCidadesPorUF(uf, null);
  } else {
    resetCidadeSelect('-- Selecione gestor, distribuidor ou UF --');
  }
});
    stateSelect.addEventListener('change', async function () {
      const uf = this.value || null;
      if (!uf) {
        resetCidadeSelect('-- Selecione gestor, distribuidor ou UF --');
        return;
      }
      if (distribuidorSelect.value) {
        await carregarCidadesPorDistribuidor(distribuidorSelect.value, null);
      } else {
        await carregarCidadesPorUF(uf, null);
      }
    });

    document.addEventListener('DOMContentLoaded', async () => {
      const OLD_CIDADE       = @json(old('cidade_id'));
      const OLD_DISTRIBUIDOR = @json(old('distribuidor_id'));
      const OLD_STATE        = @json(old('state'));

      if (OLD_DISTRIBUIDOR) {
        await carregarCidadesPorDistribuidor(OLD_DISTRIBUIDOR, OLD_CIDADE);
      } else if (OLD_STATE) {
        await carregarCidadesPorUF(OLD_STATE, OLD_CIDADE);
      } else {
        resetCidadeSelect('-- Selecione gestor, distribuidor ou UF --');
      }
    });
  </script>

    {{-- ===== LÓGICA: ADICIONAR COLEÇÃO (usa adicionarProdutoBase sem recalcular a cada item) ===== --}}
  <script>
    const modalColecao      = document.getElementById('colecao-modal');
    const btnAbrirColecao   = document.getElementById('btn-add-colecao');
    const btnConfirmColecao = document.getElementById('confirm-add-colecao');
    const colecaoSelect     = document.getElementById('colecao_select');
    const colecaoFeedback   = document.getElementById('colecao-feedback');
    const colecaoQtd        = document.getElementById('colecao_qtd');
    const colecaoDesc       = document.getElementById('colecao_desc');

    function showLoading() {
      const overlay = document.getElementById('loading-overlay');
      if (overlay) overlay.classList.remove('hidden');
    }

    function hideLoading() {
      const overlay = document.getElementById('loading-overlay');
      if (overlay) overlay.classList.add('hidden');
    }

    function openColecaoModal() {
      modalColecao.classList.remove('hidden');
      colecaoFeedback.classList.add('hidden');
      colecaoFeedback.textContent = '';
      colecaoFeedback.className = 'hidden text-sm';
    }

    function closeColecaoModal() {
      modalColecao.classList.add('hidden');
      colecaoFeedback.classList.add('hidden');
      colecaoFeedback.textContent = '';
      colecaoFeedback.className = 'hidden text-sm';
      colecaoSelect.value = '';
    }

    modalColecao.addEventListener('click', (e) => {
      if (e.target.hasAttribute('data-close')) closeColecaoModal();
    });

    document.querySelectorAll('#colecao-modal [data-close]').forEach(btn => {
      btn.addEventListener('click', closeColecaoModal);
    });

    btnAbrirColecao.addEventListener('click', openColecaoModal);

    function getColecaoNomeById(id) {
      const c = (ALL_COLECOES || []).find(cc => String(cc.id) === String(id));
      return c ? (c.nome || c.codigo || c.titulo || `Coleção #${c.id}`) : `Coleção #${id}`;
    }

    function produtosDaColecao(colecaoId) {
      return ALL_PRODUCTS.filter(p => String(p.colecao_id || '') === String(colecaoId));
    }

    btnConfirmColecao.addEventListener('click', () => {
      const cid = colecaoSelect.value;
      if (!cid) {
        colecaoFeedback.className = 'text-sm text-red-600';
        colecaoFeedback.textContent = 'Selecione uma coleção.';
        colecaoFeedback.classList.remove('hidden');
        return;
      }

      const qtd  = Math.max(1, parseInt(colecaoQtd.value || '1', 10));
      const desc = Math.max(0, Math.min(100, parseFloat(colecaoDesc.value || '0')));

      const jaSelecionados = new Set(getSelectedProductIds().map(String));
      const lista = produtosDaColecao(cid);

      if (!lista.length) {
        colecaoFeedback.className = 'text-sm text-amber-600';
        colecaoFeedback.textContent = 'Nenhum produto encontrado para esta coleção.';
        colecaoFeedback.classList.remove('hidden');
        return;
      }

      btnConfirmColecao.disabled = true;
      btnConfirmColecao.classList.add('opacity-60', 'cursor-not-allowed');
      showLoading();

      setTimeout(() => {
        let adicionados = 0;

        try {
          lista.forEach(p => {
            const pid = String(p.id);
            if (!jaSelecionados.has(pid)) {
              adicionarProdutoBase(
                { id: p.id, quantidade: qtd, desconto: desc },
                { withCalc: false, withRefresh: false }
              );
              jaSelecionados.add(pid);
              adicionados++;
            }
          });

          refreshAllProductSelects();
          calcAll();
        } catch (e) {
          console.error('Erro ao adicionar coleção:', e);
          alert('Ocorreu um erro ao adicionar a coleção. Veja o console do navegador para mais detalhes.');
        } finally {
          hideLoading();
          btnConfirmColecao.disabled = false;
          btnConfirmColecao.classList.remove('opacity-60', 'cursor-not-allowed');
          closeColecaoModal();
        }

        if (adicionados === 0) {
          alert('Todos os produtos desta coleção já estão adicionados.');
        } else {
          console.log(`${adicionados} produto(s) adicionados de "${getColecaoNomeById(cid)}" (qtd ${qtd}, desc ${desc}%).`);
        }
      }, 30);
    });
  </script>


</x-app-layout>
