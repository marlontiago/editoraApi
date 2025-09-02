<x-app-layout>
    <div>
        <h2 class="font-semibold text-2xl ml-4 mt-4 mb-4 text-gray-800 leading-tight">
            {{ __('Relatórios Financeiros') }}
        </h2>

        @php
            $paramsBase = request()->except('status'); // preserva data_inicio, data_fim, tipo, id, etc.

            function moeda_br($v) { return 'R$ ' . number_format((float)$v, 2, ',', '.'); }
            function status_badge($s) {
                $map = [
                    'pago' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                    'aguardando_pagamento' => 'bg-amber-100 text-amber-800 border-amber-200',
                    'faturada' => 'bg-sky-100 text-sky-800 border-sky-200',
                    'emitida' => 'bg-blue-100 text-blue-800 border-blue-200',
                ];
                $cls = $map[$s] ?? 'bg-gray-100 text-gray-700 border-gray-200';
                return '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium border '.$cls.'">'.e(str_replace('_',' ',$s)).'</span>';
            }
        @endphp

        {{-- Cards topo (preservam as datas e demais filtros) --}}
        <div class="flex flex-wrap">
            <a href="{{ route('admin.relatorios.index', array_merge($paramsBase, ['status' => 'pago'])) }}"
               class="bg-emerald-500 text-white p-4 m-2 rounded-lg w-full sm:w-[calc(33.333%-1rem)] cursor-pointer hover:opacity-90 shadow">
                <h1 class="text-sm uppercase tracking-wide">Notas pagas</h1>
                <p class="text-3xl font-bold mt-2">{{ $notasPagas->count() }}</p>
            </a>

            <a href="{{ route('admin.relatorios.index', array_merge($paramsBase, ['status' => 'aguardando_pagamento'])) }}"
               class="bg-amber-500 text-white p-4 m-2 rounded-lg w-full sm:w-[calc(33.333%-1rem)] cursor-pointer hover:opacity-90 shadow">
                <h1 class="text-sm uppercase tracking-wide">Aguardando pagamento</h1>
                <p class="text-3xl font-bold mt-2">{{ $notasAPagar->count() }}</p>
            </a>

            <a href="{{ route('admin.relatorios.index', array_merge($paramsBase, ['status' => 'emitida'])) }}"
               class="bg-blue-500 text-white p-4 m-2 rounded-lg w-full sm:w-[calc(33.333%-1rem)] cursor-pointer hover:opacity-90 shadow">
                <h1 class="text-sm uppercase tracking-wide">Notas emitidas</h1>
                <p class="text-3xl font-bold mt-2">{{ $notasEmitidas->count() }}</p>
            </a>
        </div>

        {{-- Filtros unificados: usuário + período + status --}}
        <div class="mt-6 px-4">
            <form id="filtros" method="GET" action="{{ route('admin.relatorios.index') }}" class="flex flex-wrap items-end gap-4 mb-4 bg-white p-4 rounded-xl border shadow-sm">
                <input type="hidden" name="status" id="status" value="{{ $statusFiltro ?? '' }}">

                <div>
                    <label class="block text-xs text-gray-500 mb-1">Cliente</label>
                    <select class="min-w-[220px] rounded-xl border-gray-200 shadow-sm"
                            name="cliente_select" id="select-cliente" data-tipo="cliente">
                        <option value="">--Selecione um Cliente--</option>
                        @foreach ($clientes as $cli)
                            <option value="{{ $cli->id }}" @selected(($filtroTipo ?? '')==='cliente' && (int)($filtroId ?? 0)===$cli->id)>
                                {{ $cli->razao_social }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs text-gray-500 mb-1">Gestor</label>
                    <select class="min-w-[220px] rounded-xl border-gray-200 shadow-sm"
                            name="gestor_select" id="select-gestor" data-tipo="gestor">
                        <option value="">--Selecione um Gestor--</option>
                        @foreach ($gestores as $ges)
                            <option value="{{ $ges->id }}" @selected(($filtroTipo ?? '')==='gestor' && (int)($filtroId ?? 0)===$ges->id)>
                                {{ $ges->razao_social }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs text-gray-500 mb-1">Distribuidor</label>
                    <select class="min-w-[220px] rounded-xl border-gray-200 shadow-sm"
                            name="distribuidor_select" id="select-distribuidor" data-tipo="distribuidor">
                        <option value="">--Selecione um Distribuidor--</option>
                        @foreach ($distribuidores as $dis)
                            <option value="{{ $dis->id }}" @selected(($filtroTipo ?? '')==='distribuidor' && (int)($filtroId ?? 0)===$dis->id)>
                                {{ $dis->razao_social }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <input type="hidden" name="tipo" id="tipo" value="{{ $filtroTipo ?? '' }}">
                <input type="hidden" name="id" id="id" value="{{ $filtroId ?? '' }}">

                <div>
                    <label class="block text-xs text-gray-500 mb-1">Data inicial</label>
                    <input type="date" name="data_inicio" value="{{ $dataInicio }}" class="rounded-xl border-gray-200 shadow-sm">
                </div>

                <div>
                    <label class="block text-xs text-gray-500 mb-1">Data final</label>
                    <input type="date" name="data_fim" value="{{ $dataFim }}" class="rounded-xl border-gray-200 shadow-sm">
                </div>

                <button class="px-4 py-2 rounded-lg bg-gray-900 text-white hover:bg-gray-800">
                    Aplicar filtros
                </button>

                @if($dataInicio || $dataFim || $filtroTipo || $filtroId || $statusFiltro)
                    <a href="{{ route('admin.relatorios.index') }}" class="px-3 py-2 text-sm text-blue-700 hover:underline">
                        Limpar tudo
                    </a>
                @endif
            </form>
        </div>

        {{-- Chips de filtros ativos --}}
        <div class="px-4">
            <div class="flex flex-wrap gap-2">
                @if($statusFiltro)
                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-gray-100 border text-gray-700 text-xs">
                        Status: <strong class="ml-1">{{ str_replace('_',' ', $statusFiltro) }}</strong>
                    </span>
                @endif
                @if($dataInicio && $dataFim)
                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-gray-100 border text-gray-700 text-xs">
                        Período: <strong class="ml-1">{{ \Carbon\Carbon::parse($dataInicio)->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($dataFim)->format('d/m/Y') }}</strong>
                    </span>
                @endif
                @if($filtroTipo && $filtroId)
                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-gray-100 border text-gray-700 text-xs">
                        Filtro: <strong class="ml-1">{{ ucfirst($filtroTipo) }} #{{ $filtroId }}</strong>
                    </span>
                @endif
            </div>
        </div>

        {{-- =======================
             BLOCOS DE RESULTADO
           ======================= --}}

        {{-- Por USUÁRIO --}}
        <div class="mt-6 px-4">
            @if(isset($pedidos) && $pedidos->count())
                {{-- Resumo do resultado --}}
                <div class="grid md:grid-cols-3 gap-4 mb-3">
                    <div class="bg-white border rounded-xl p-4 shadow-sm">
                        <div class="text-xs text-gray-500">Total líquido do período</div>
                        <div class="text-2xl font-semibold mt-1">{{ moeda_br($resumoUsuario['valor_liquido']) }}</div>
                    </div>
                    <div class="bg-white border rounded-xl p-4 shadow-sm">
                        <div class="text-xs text-gray-500">Total de comissões</div>
                        <div class="text-2xl font-semibold mt-1">{{ moeda_br($resumoUsuario['total_comissoes']) }}</div>
                    </div>
                    <div class="bg-white border rounded-xl p-4 shadow-sm">
                        <div class="text-xs text-gray-500">Pedidos no resultado</div>
                        <div class="text-2xl font-semibold mt-1">{{ $resumoUsuario['qtd'] }}</div>
                    </div>
                </div>

                <div class="flex justify-end mb-2">
                    <a href="{{ route('admin.relatorios.index', array_merge(request()->all(), ['export' => 'pdf'])) }}"
                       class="inline-flex items-center gap-2 px-3 py-2 text-sm bg-gray-900 text-white rounded-lg hover:bg-gray-800">
                        Exportar PDF
                    </a>
                </div>

                <div class="overflow-x-auto rounded-xl border border-gray-200 shadow-sm">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 text-gray-700 sticky top-0 z-10">
                            <tr>
                                <th class="px-4 py-3 text-left">#</th>
                                <th class="px-4 py-3 text-left">Cliente</th>
                                <th class="px-4 py-3 text-left">Gestor</th>
                                <th class="px-4 py-3 text-left">Distribuidor</th>
                                <th class="px-4 py-3 text-left">Data Pagamento</th>
                                <th class="px-4 py-3 text-right">Valor Líquido Pago</th>
                                <th class="px-4 py-3 text-left">Financeiro</th>
                                <th class="px-4 py-3 text-right">Comissão</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($pedidos as $p)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-2">#{{ $p->id }}</td>
                                    <td class="px-4 py-2">{{ $p->cliente->razao_social ?? '—' }}</td>
                                    <td class="px-4 py-2">{{ $p->gestor->razao_social ?? '—' }}</td>
                                    <td class="px-4 py-2">{{ $p->distribuidor->razao_social ?? '—' }}</td>
                                    @php
                                    $pgto = optional($p->notaFiscal)->pagamentos ? $p->notaFiscal->pagamentos->sortByDesc('data_pagamento')->first() : null;
                                    @endphp
                                    <td class="px-4 py-2">{{ $pgto && $pgto->data_pagamento ? \Carbon\Carbon::parse($pgto->data_pagamento)->format('d/m/Y') : '-'}}</td>
                                    <td class="px-4 py-2 text-right">{{ moeda_br($p->valor_liquido_pago_total ?? 0) }}</td>
                                    <td class="px-4 py-2">{!! $p->notaFiscal? status_badge($p->notaFiscal->status_financeiro ?? '-') : '—' !!}</td>
                                    <td class="px-4 py-2 text-right font-medium">{{ moeda_br($p->comissao_do_filtro ?? 0) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50">
                            <tr>
                                <td colspan="4" class="px-4 py-3 text-right font-semibold">Totais:</td>
                                <td class="px-4 py-3 text-right font-semibold">{{ moeda_br($resumoUsuario['valor_liquido']) }}</td>
                                <td class="px-4 py-3"></td>
                                <td class="px-4 py-3 text-right font-bold">{{ moeda_br($resumoUsuario['total_comissoes']) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @elseif(isset($filtroTipo, $filtroId) && $filtroTipo && $filtroId)
                <div class="mt-4 text-gray-600">Nenhum pedido encontrado para o filtro.</div>
            @endif
        </div>

        {{-- Por STATUS / ou somente PERÍODO --}}
        @if( ($statusFiltro && isset($pedidoStatus)) || ($dataInicio && $dataFim && isset($pedidoStatus)) )
            <div class="mt-10 px-4">
                @if($pedidoStatus->count())
                    {{-- Resumo do resultado --}}
                    <div class="grid md:grid-cols-3 gap-4 mb-3">
                        <div class="bg-white border rounded-xl p-4 shadow-sm">
                            <div class="text-xs text-gray-500">Pedidos no resultado</div>
                            <div class="text-2xl font-semibold mt-1">{{ $resumoStatus['qtd'] }}</div>
                        </div>
                        <div class="bg-white border rounded-xl p-4 shadow-sm">
                            <div class="text-xs text-gray-500">Soma do valor da nota</div>
                            <div class="text-2xl font-semibold mt-1">{{ moeda_br($resumoStatus['valor_total']) }}</div>
                        </div>
                        <div class="bg-white border rounded-xl p-4 shadow-sm">
                            <div class="text-xs text-gray-500">Status</div>
                            <div class="text-base mt-1">
                                @if($statusFiltro)
                                    {!! status_badge($statusFiltro) !!}
                                @else
                                    <span class="text-gray-600">—</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 mb-3">
                        @if($statusFiltro)
                            <a class="text-blue-700 hover:underline text-sm"
                               href="{{ route('admin.relatorios.index', request()->except('status')) }}">
                                Limpar status
                            </a>
                        @endif
                        <div class="ml-auto">
                            <a href="{{ route('admin.relatorios.index', array_merge(request()->all(), ['export' => 'pdf'])) }}"
                               class="inline-flex items-center gap-2 px-3 py-2 text-sm bg-gray-900 text-white rounded-lg hover:bg-gray-800">
                                Exportar PDF
                            </a>
                        </div>
                    </div>

                    <div class="overflow-x-auto rounded-xl border border-gray-200 shadow-sm">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50 text-gray-700 sticky top-0 z-10">
                                <tr>
                                    <th class="px-4 py-3 text-left">#</th>
                                    <th class="px-4 py-3 text-left">Cliente</th>
                                    <th class="px-4 py-3 text-left">Gestor</th>
                                    <th class="px-4 py-3 text-left">Distribuidor</th>
                                    <th class="px-4 py-3 text-left">Data Pagamento</th>
                                    <th class="px-4 py-3 text-right">Valor da Nota</th>
                                    <th class="px-4 py-3 text-left">Financeiro</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($pedidoStatus as $p)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-2">#{{ $p->id }}</td>
                                        <td class="px-4 py-2">{{ $p->cliente->razao_social ?? '—' }}</td>
                                        <td class="px-4 py-2">{{ $p->gestor->razao_social ?? '—' }}</td>
                                        <td class="px-4 py-2">{{ $p->distribuidor->razao_social ?? '—' }}</td>
                                        @php
                                        $pgto = optional($p->notaFiscal)->pagamentos ? $p->notaFiscal->pagamentos->sortByDesc('data_pagamento')->first() : null;
                                        @endphp
                                        <td class="px-4 py-2">{{ $pgto && $pgto->data_pagamento ? \Carbon\Carbon::parse($pgto->data_pagamento)->format('d/m/Y') : '-'}}</td>
                                        <td class="px-4 py-2 text-right">{{ moeda_br(optional($p->notaFiscal)->valor_total ?? 0) }}</td>
                                        <td class="px-4 py-2">{!! $p->notaFiscal? status_badge($p->notaFiscal->status_financeiro ?? '-') : '—' !!}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-gray-50">
                                <tr>
                                    <td colspan="4" class="px-4 py-3 text-right font-semibold">Total:</td>
                                    <td class="px-4 py-3 text-right font-bold">{{ moeda_br($resumoStatus['valor_total']) }}</td>
                                    <td class="px-4 py-3"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @else
                    <div class="text-gray-600">Nenhum registro encontrado para os filtros aplicados.</div>
                @endif
            </div>
        @endif
    </div>

    {{-- Auto-submit dos selects (usa o mesmo <form> "filtros") --}}
    <script>
    (function() {
        const form = document.getElementById('filtros');
        const tipoInput = document.getElementById('tipo');
        const idInput   = document.getElementById('id');

        const selects = [
            document.getElementById('select-cliente'),
            document.getElementById('select-gestor'),
            document.getElementById('select-distribuidor'),
        ];

        function clearOthers(except) {
            selects.forEach(sel => { if (sel !== except) sel.selectedIndex = 0; });
        }

        selects.forEach(sel => {
            sel.addEventListener('change', () => {
                const tipo = sel.dataset.tipo;
                const val  = sel.value;

                if (!val) {
                    if (tipoInput.value === tipo) {
                        tipoInput.value = '';
                        idInput.value   = '';
                    }
                    return;
                }

                clearOthers(sel);
                tipoInput.value = tipo;
                idInput.value   = val;
                form.submit();
            });
        });
    })();
    </script>
</x-app-layout>
