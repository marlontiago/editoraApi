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

  {{-- ===== DADOS PARA JS (sem @json com arrow function) ===== --}}
  <script>
    const ALL_PRODUCTS = {!! $produtos
        ->map(function ($p) {
          return [
            'id'     => $p->id,
            'titulo' => $p->titulo,
            'preco'  => (float) ($p->preco ?? 0),
          ];
        })
        ->values()
        ->toJson() !!};

    const OLD_PRODUTOS     = @json(old('produtos', []));
    const OLD_DISTRIBUIDOR = @json(old('distribuidor_id'));
    const OLD_GESTOR       = @json(old('gestor_id'));
    const OLD_CIDADE       = @json(old('cidade_id'));
  </script>

  {{-- ===== PRODUTOS (sem duplicados + desconto + cálculos em tempo real) ===== --}}
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

        <div class="col-span-12">
          <div class="mt-2 rounded-md bg-white border p-3 text-sm text-gray-700 space-y-1 calc-area">
            <div><span class="font-medium">Preço unit.:</span> <span class="unit-price">R$ 0,00</span></div>
            <div><span class="font-medium">Unit. c/ desconto:</span> <span class="unit-disc">R$ 0,00</span></div>
            <div><span class="font-medium">Subtotal:</span> <span class="line-total">R$ 0,00</span></div>
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

      const unitSpan  = row.querySelector('.unit-price');
      const unitDSpan = row.querySelector('.unit-disc');
      const totalSpan = row.querySelector('.line-total');

      const pid   = sel?.value || '';
      const prod  = getProductById(pid);
      const qtd   = Math.max(1, parseInt(qEl?.value || '1', 10));
      const desc  = Math.max(0, Math.min(100, parseFloat(dEl?.value || '0')));

      const precoUnit = prod ? Number(prod.preco || 0) : 0;
      const precoDesc = precoUnit * (1 - (desc / 100));
      const subtotal  = precoDesc * qtd;

      unitSpan.textContent  = fmtBRL.format(precoUnit);
      unitDSpan.textContent = fmtBRL.format(precoDesc);
      totalSpan.textContent = fmtBRL.format(subtotal);
    }
    function calcAll() {
      container.querySelectorAll('.produto').forEach(calcRow);
    }

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
      }
    });

    document.addEventListener('DOMContentLoaded', () => {
      if (Array.isArray(OLD_PRODUTOS) && OLD_PRODUTOS.length) {
        OLD_PRODUTOS.forEach(p => adicionarProduto(p));
      } else {
        adicionarProduto();
      }
      calcAll();
    });
  </script>

  {{-- ===== GESTOR / DISTRIBUIDOR ===== --}}
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

  // Renderiza opções de cidade; se vier "ocupado = true" e não houver distribuidor,
  // desabilita a opção e mostra rótulo "(ocupada por ...)".
  function rebuildCidadeOptions(cidades, { allowOccupied = false } = {}) {
    cidadeSelect.innerHTML = '';
    cidadeSelect.add(new Option('-- Selecione --', ''));
    cidades.forEach(c => {
      const opt = new Option(c.name, c.id);
      const isOccupied = Boolean(c.ocupado);
      const distName   = c.distribuidor_nome || null;

      if (isOccupied && !allowOccupied) {
        opt.disabled = true;
        opt.text = `${c.name} ${distName ? `(ocupada por ${distName})` : '(ocupada)'}`;
      }
      cidadeSelect.add(opt);
    });
    cidadeSelect.disabled = false;
    cidadeSelect.classList.remove('bg-gray-50');
  }

  // Cidades do distribuidor (prioridade máxima quando tiver distribuidor)
  async function carregarCidadesPorDistribuidor(distribuidorId, selectedCidadeId = null) {
    resetCidadeSelect('-- Carregando... --');
    try {
      const resp = await fetch(`/admin/cidades/por-distribuidor/${distribuidorId}`);
      if (!resp.ok) throw new Error(`HTTP ${resp.status}`);
      const cidades = await resp.json(); // [{id,name}]
      rebuildCidadeOptions(cidades, { allowOccupied: true }); // não vem flag ocupado aqui
      if (selectedCidadeId) cidadeSelect.value = String(selectedCidadeId);
    } catch (e) {
      console.error(e);
      resetCidadeSelect('Falha ao carregar cidades');
    }
  }

  // Cidades por UF (com ocupação); se não há distribuidor, desabilita ocupadas
  async function carregarCidadesPorUF(uf, selectedCidadeId = null) {
    resetCidadeSelect('-- Carregando... --');
    try {
      const resp = await fetch(`/admin/cidades/por-uf/${encodeURIComponent(uf)}?with_occupancy=1`);
      if (!resp.ok) throw new Error(`HTTP ${resp.status}`);
      const cidades = await resp.json(); // [{id,name,ocupado,distribuidor_id,distribuidor_nome}]
      const hasDistribuidor = Boolean(distribuidorSelect.value);

      // se NÃO há distribuidor, não permite selecionar ocupadas
      rebuildCidadeOptions(cidades, { allowOccupied: hasDistribuidor });
      if (selectedCidadeId) cidadeSelect.value = String(selectedCidadeId);
    } catch (e) {
      console.error(e);
      resetCidadeSelect('Falha ao carregar cidades');
    }
  }

  // === Eventos ===

  // 1) Distribuidor mudou: se há distribuidor, lista só as dele; se tirou, volta a obedecer a UF
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

  // 2) Gestor NÃO trava mais a UF — apenas ignora aqui. (mantemos caso queira usar em outro fluxo)
  gestorSelect.addEventListener('change', function () {
    // intencionalmente vazio: UF é totalmente livre agora
  });

  // 3) UF mudou: se há distribuidor, mantemos as cidades do distribuidor (prioridade);
  // do contrário, carregamos cidades da UF (com ocupação)
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

  // Estado inicial (old form)
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

</x-app-layout>
