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
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-white p-4 rounded shadow">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Data</label>
                    <input type="date" name="data"
                        value="{{ old('data', \Carbon\Carbon::parse($pedido->data)->format('Y-m-d')) }}"
                        class="mt-1 w-full border rounded px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Status</label>
                    @php
                        $statuses = ['em_andamento' => 'Em andamento', 'finalizado' => 'Finalizado', 'cancelado' => 'Cancelado'];
                    @endphp
                    <select name="status" class="mt-1 w-full border rounded px-3 py-2">
                        @foreach ($statuses as $val => $label)
                            <option value="{{ $val }}" @selected(old('status', $pedido->status) === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Gestor</label>
                    <select name="gestor_id" class="mt-1 w-full border rounded px-3 py-2">
                        <option value="">— Selecionar —</option>
                        @foreach ($gestores as $g)
                            <option value="{{ $g->id }}" @selected(old('gestor_id', $pedido->gestor_id) == $g->id)>
                                {{ $g->razao_social }} @if($g->user) ({{ $g->user->name }}) @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Distribuidor</label>
                    <select name="distribuidor_id" class="mt-1 w-full border rounded px-3 py-2">
                        <option value="">— Selecionar —</option>
                        @foreach ($distribuidores as $d)
                            <option value="{{ $d->id }}" @selected(old('distribuidor_id', $pedido->distribuidor_id) == $d->id)>
                                {{ $d->razao_social }} @if($d->user) ({{ $d->user->name }}) @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Desconto (%)</label>
                    <input type="number" name="desconto" min="0" max="100" step="0.01"
                        value="{{ old('desconto', $pedido->desconto) }}"
                        class="mt-1 w-full border rounded px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Cidades</label>
                    <select name="cidades[]" multiple class="mt-1 w-full border rounded px-3 py-2 h-36">
                        @php
                            $cidadesSelecionadas = old('cidades', $pedido->cidades->pluck('id')->all());
                        @endphp
                        @foreach ($cidades as $c)
                            <option value="{{ $c->id }}" @selected(in_array($c->id, $cidadesSelecionadas))>
                                {{ $c->name }}
                            </option>
                        @endforeach
                    </select>
                    <small class="text-gray-500">Segure CTRL (ou CMD no Mac) para selecionar múltiplas.</small>
                </div>
            </div>

            <!-- Produtos -->
            <div class="bg-white p-4 rounded shadow">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-lg font-semibold">Produtos do Pedido</h3>
                    <button type="button" id="btnAddRow"
                        class="bg-green-600 text-white px-3 py-1 rounded hover:bg-green-700">
                        + Adicionar produto
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-left border-collapse" id="tabelaProdutos">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-3 py-2">Produto</th>
                                <th class="px-3 py-2" style="width: 120px;">Quantidade</th>
                                <th class="px-3 py-2" style="width: 60px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- Linhas existentes (itens atuais) --}}
                            @foreach ($pedido->produtos as $p)
                                <tr class="border-b" data-index="{{ $loop->index }}">
                                    <td class="px-3 py-2">
                                        <input type="hidden" name="produtos[{{ $loop->index }}][id]" value="{{ $p->id }}" class="inputId">
                                        <div class="font-medium">{{ $p->nome }}</div>
                                        <div class="text-xs text-gray-500">
                                            Preço atual: R$ {{ number_format($p->preco, 2, ',', '.') }}
                                        </div>
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="number" min="0" class="w-28 border rounded px-2 py-1 inputQtd"
                                            name="produtos[{{ $loop->index }}][quantidade]"
                                            value="{{ old('produtos.'.$loop->index.'.quantidade', $p->pivot->quantidade) }}">
                                    </td>
                                    <td class="px-3 py-2 text-right">
                                        <button type="button" class="text-red-600 hover:underline btnRemoveRow">Remover</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <p class="text-sm text-gray-500 mt-2">
                    Dica: para <strong>remover</strong> um item, clique em “Remover” (a linha sai do envio).
                </p>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('admin.pedidos.show', $pedido) }}" class="px-4 py-2 rounded border">Cancelar</a>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    Salvar alterações
                </button>
            </div>
        </form>
    </div>

    {{-- Template oculto para nova linha (usa __INDEX__ que será substituído no JS) --}}
    <template id="rowTemplate">
        <tr class="border-b" data-index="__INDEX__">
            <td class="px-3 py-2">
                <select class="w-full border rounded px-2 py-1 produtoSelect">
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
            <td class="px-3 py-2">
                <input type="number" min="1" class="w-28 border rounded px-2 py-1 inputQtd"
                       data-name="produtos[__INDEX__][quantidade]" value="1">
            </td>
            <td class="px-3 py-2 text-right">
                <button type="button" class="text-red-600 hover:underline btnRemoveRow">Remover</button>
            </td>
        </tr>
    </template>

    <script>
        (function () {
            const form = document.getElementById('formPedidoEdit');
            const tabelaBody = document.getElementById('tabelaProdutos').querySelector('tbody');
            const tpl = document.getElementById('rowTemplate');
            const btnAdd = document.getElementById('btnAddRow');

            // começa do número de linhas existentes
            let rowIndex = {{ $pedido->produtos->count() }};

            // Adicionar uma nova linha com names indexados
            btnAdd.addEventListener('click', () => {
                const idx = rowIndex++;
                const frag = tpl.content.cloneNode(true);
                // substitui __INDEX__ nos atributos data-name e data-index
                frag.querySelectorAll('[data-name]').forEach(el => {
                    const name = el.getAttribute('data-name').replace('__INDEX__', idx);
                    el.setAttribute('name', name);
                    el.removeAttribute('data-name');
                });
                const tr = frag.querySelector('tr');
                tr.setAttribute('data-index', idx);
                tabelaBody.appendChild(frag);
            });

            // Delegação: remover linha
            document.addEventListener('click', (e) => {
                if (e.target.classList.contains('btnRemoveRow')) {
                    e.preventDefault();
                    const tr = e.target.closest('tr');
                    tr.remove();
                }
            });

            // Ao escolher o produto no select, copiar o ID para o input hidden na mesma linha
            document.addEventListener('change', (e) => {
                if (e.target.classList.contains('produtoSelect')) {
                    const tr = e.target.closest('tr');
                    const inputId = tr.querySelector('.inputId');
                    inputId.value = e.target.value || '';
                }
            });

            // Antes de enviar, remove linhas sem produto ou quantidade <= 0
            form.addEventListener('submit', function () {
                const rows = Array.from(tabelaBody.querySelectorAll('tr'));
                rows.forEach(row => {
                    const id = row.querySelector('.inputId')?.value;
                    const qtdInput = row.querySelector('.inputQtd');
                    const qtd = qtdInput ? parseInt(qtdInput.value, 10) : 0;
                    if (!id || !qtd || qtd <= 0) {
                        row.remove();
                    }
                });
            });
        })();
    </script>
</x-app-layout>
