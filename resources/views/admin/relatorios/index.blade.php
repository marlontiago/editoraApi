<x-app-layout>
    <div>
        <h2 class="font-semibold text-2xl ml-4 mt-4 mb-4 text-gray-800 leading-tight">
            {{ __('Relatórios Financeiros') }}
        </h2>

        @php
            $paramsBase = request()->except('status');
            function moeda_br($v) { return 'R$ ' . number_format((float)$v, 2, ',', '.'); }
            function status_badge($s) {
                $map = [
                    'pago'                 => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                    'pago_parcial'         => 'bg-sky-100 text-sky-800 border-sky-200',
                    'aguardando_pagamento' => 'bg-amber-100 text-amber-800 border-amber-200',
                    'faturada'             => 'bg-sky-100 text-sky-800 border-sky-200',
                    'emitida'              => 'bg-blue-100 text-blue-800 border-blue-200',
                ];
                $cls = $map[$s] ?? 'bg-gray-100 text-gray-700 border-gray-200';
                return '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium border '.$cls.'">'.e(str_replace('_',' ',$s)).'</span>';
            }
        @endphp

        {{-- 1) CARDS DO TOPO --}}
        <div class="flex flex-wrap">
            <a href="{{ route('admin.relatorios.index', array_merge($paramsBase, ['status' => 'pago'])) }}"
               class="bg-emerald-500 text-white p-4 m-2 rounded-lg w-full sm:w-[calc(33.333%-1rem)] cursor-pointer hover:opacity-90 shadow">
                <h1 class="text-sm uppercase tracking-wide">Notas pagas</h1>
                <p class="text-3xl font-bold mt-2">{{ $notasPagas->count() }}</p>
            </a>

            <a href="{{ route('admin.relatorios.index', array_merge($paramsBase, ['status' => 'aguardando_pagamento'])) }}"
               class="bg-amber-500 text-white p-4 m-2 rounded-lg w-full sm:w-[calc(33.333%-1rem)] cursor-pointer hover:opacity-90 shadow">
                <h1 class="text-sm uppercase tracking-wide">Aguardando / Parcial</h1>
                <p class="text-3xl font-bold mt-2">{{ $notasAPagar->count() }}</p>
            </a>

            <a href="{{ route('admin.relatorios.index', array_merge($paramsBase, ['status' => 'emitida'])) }}"
               class="bg-blue-500 text-white p-4 m-2 rounded-lg w-full sm:w-[calc(33.333%-1rem)] cursor-pointer hover:opacity-90 shadow">
                <h1 class="text-sm uppercase tracking-wide">Notas emitidas</h1>
                <p class="text-3xl font-bold mt-2">{{ $notasEmitidas->count() }}</p>
            </a>
        </div>

        {{-- 2) FILTROS --}}
        <div class="mt-6 px-4">
            <form id="filtros" method="GET" action="{{ route('admin.relatorios.index') }}"
                class="flex flex-wrap items-end gap-3 mb-4 bg-white p-4 rounded-xl border shadow-sm">

                <input type="hidden" name="status" id="status" value="{{ $statusFiltro ?? '' }}">
                {{-- adicionados para não quebrar o JS de auto-submit (retrocompat) --}}
                <input type="hidden" name="tipo" id="tipo" value="{{ $filtroTipo ?? '' }}">
                <input type="hidden" name="id" id="id" value="{{ $filtroId ?? '' }}">

                {{-- filtros entidade principal --}}
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Cliente</label>
                    <select class="w-[150px] md:w-[180px] rounded-xl border-gray-200 shadow-sm"
                            name="cliente_select" id="select-cliente" data-tipo="cliente">
                        <option value="">--Cliente--</option>
                        @foreach ($clientes as $cli)
                            <option value="{{ $cli->id }}" @selected(($filtroTipo ?? '')==='cliente' && (int)($filtroId ?? 0)===$cli->id)>
                                {{ $cli->razao_social }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs text-gray-500 mb-1">Gestor</label>
                    <select class="w-[150px] md:w-[180px] rounded-xl border-gray-200 shadow-sm"
                            name="gestor_select" id="select-gestor" data-tipo="gestor">
                        <option value="">--Gestor--</option>
                        @foreach ($gestores as $ges)
                            <option value="{{ $ges->id }}" @selected(($filtroTipo ?? '')==='gestor' && (int)($filtroId ?? 0)===$ges->id)>
                                {{ $ges->razao_social }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs text-gray-500 mb-1">Distribuidor</label>
                    <select class="w-[150px] md:w-[180px] rounded-xl border-gray-200 shadow-sm"
                            name="distribuidor_select" id="select-distribuidor" data-tipo="distribuidor">
                        <option value="">--Distribuidor--</option>
                        @foreach ($distribuidores as $dis)
                            <option value="{{ $dis->id }}" @selected(($filtroTipo ?? '')==='distribuidor' && (int)($filtroId ?? 0)===$dis->id)>
                                {{ $dis->razao_social }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- filtros adicionais --}}
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Despesas Adicionais</label>
                    <select name="advogado_id" class="w-[150px] md:w-[180px] rounded-xl border-gray-200 shadow-sm">
                        <option value="">--Todos--</option>
                        @foreach ($advogados as $a)
                            <option value="{{ $a->id }}" @selected((int)($advogadoId ?? 0)===$a->id)>{{ $a->nome }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs text-gray-500 mb-1">Comercial</label>
                    <select name="diretor_id" class="w-[150px] md:w-[180px] rounded-xl border-gray-200 shadow-sm">
                        <option value="">--Todos--</option>
                        @foreach ($diretores as $d)
                            <option value="{{ $d->id }}" @selected((int)($diretorId ?? 0)===$d->id)>{{ $d->nome }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs text-gray-500 mb-1">UF</label>
                    <select name="uf" id="select-uf"
                            class="w-[100px] md:w-[120px] rounded-xl border-gray-200 shadow-sm"
                            onchange="this.form.submit()">
                        <option value="">-- UF --</option>
                        @foreach ($ufsOptions as $uf)
                            <option value="{{ $uf }}" @selected(($ufSelecionada ?? '') === $uf)>{{ $uf }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs text-gray-500 mb-1">Cidade</label>
                    <select name="cidade_id" class="w-[150px] md:w-[180px] rounded-xl border-gray-200 shadow-sm">
                        <option value="">--Todas--</option>
                        @foreach ($cidadesOptions as $c)
                            <option value="{{ $c->id }}" @selected((int)($cidadeId ?? 0)===$c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- período --}}
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Data inicial</label>
                    <input type="date" name="data_inicio" value="{{ $dataInicio }}"
                        class="w-[140px] rounded-xl border-gray-200 shadow-sm">
                </div>

                <div>
                    <label class="block text-xs text-gray-500 mb-1">Data final</label>
                    <input type="date" name="data_fim" value="{{ $dataFim }}"
                        class="w-[140px] rounded-xl border-gray-200 shadow-sm">
                </div>

                <button class="px-3 py-2 rounded-lg bg-gray-900 text-white hover:bg-gray-800 text-sm">
                    Aplicar
                </button>

                @if($dataInicio || $dataFim || $filtroTipo || $filtroId || $statusFiltro || $advogadoId || $diretorId || $cidadeId || ($ufSelecionada ?? false))
                    <a href="{{ route('admin.relatorios.index') }}" class="px-2 py-2 text-sm text-blue-700 hover:underline">
                        Limpar
                    </a>
                @endif
            </form>
        </div>

        {{-- chips --}}
        <div class="px-4">
            <div class="flex flex-wrap gap-2 text-xs">
                @if($statusFiltro)
                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-gray-100 border text-gray-700">
                        Status: <strong class="ml-1">{{ str_replace('_',' ', $statusFiltro) }}</strong>
                    </span>
                @endif
                @if($dataInicio && $dataFim)
                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-gray-100 border text-gray-700">
                        Período: <strong class="ml-1">{{ \Carbon\Carbon::parse($dataInicio)->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($dataFim)->format('d/m/Y') }}</strong>
                    </span>
                @endif
                @if($filtroTipo && $filtroId)
                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-gray-100 border text-gray-700">
                        Filtro: <strong class="ml-1">{{ ucfirst($filtroTipo) }} #{{ $filtroId }}</strong>
                    </span>
                @endif
                @if($advogadoId)
                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-gray-100 border text-gray-700">
                        Advogado: <strong class="ml-1">#{{ $advogadoId }}</strong>
                    </span>
                @endif
                @if($diretorId)
                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-gray-100 border text-gray-700">
                        Diretor: <strong class="ml-1">#{{ $diretorId }}</strong>
                    </span>
                @endif
                @if($cidadeId)
                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-gray-100 border text-gray-700">
                        Cidade: <strong class="ml-1">#{{ $cidadeId }}</strong>
                    </span>
                @endif
                @if(!empty($ufSelecionada))
                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-gray-100 border text-gray-700">
                        UF: <strong class="ml-1">{{ $ufSelecionada }}</strong>
                    </span>
                @endif
            </div>
        </div>

        {{-- 3) TABELA (sempre lista as notas do recorte) --}}
        <div class="mt-6 px-4">
            <div class="flex justify-end mb-2">
                @if($notas->count())
                    <a href="{{ route('admin.relatorios.index', array_merge(request()->all(), ['export' => 'pdf'])) }}"
                       class="inline-flex items-center gap-2 px-3 py-2 text-sm bg-gray-900 text-white rounded-lg hover:bg-gray-800">
                        Exportar PDF
                    </a>
                @endif
            </div>

            @if($notas->count())
                <div class="overflow-x-auto rounded-xl border border-gray-200 shadow-sm">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 text-gray-700 sticky top-0 z-10">
                            <tr>
                                <th class="px-4 py-3 text-left"># Nota</th>
                                <th class="px-4 py-3 text-left">Pedido</th>
                                <th class="px-4 py-3 text-left">Cliente</th>
                                <th class="px-4 py-3 text-left">Gestor</th>
                                <th class="px-4 py-3 text-left">Distribuidor</th>
                                <th class="px-4 py-3 text-left">Cidades</th>
                                <th class="px-4 py-3 text-left">Emitida</th>
                                <th class="px-4 py-3 text-left">Faturada</th>
                                <th class="px-4 py-3 text-left">Financeiro</th>
                                <th class="px-4 py-3 text-right">Valor Nota</th>
                                <th class="px-4 py-3 text-right">Pago (Líquido)</th>
                                <th class="px-4 py-3 text-right">Retenções</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($notas as $n)
                                @php
                                    $pedido  = $n->pedido;
                                    $pgts    = $n->pagamentos ?? collect();
                                    if (!empty($dataInicio) && !empty($dataFim)) {
                                        $pgts = $pgts->filter(function ($pg) use ($dataInicio, $dataFim) {
                                            $d = \Carbon\Carbon::parse($pg->data_pagamento)->toDateString();
                                            return $d >= $dataInicio && $d <= $dataFim;
                                        });
                                    }
                                    $liquido   = (float) $pgts->sum('valor_liquido');
                                    $retencoes = 0.0;
                                    foreach ([
                                        'ret_irrf_valor','ret_iss_valor','ret_inss_valor',
                                        'ret_pis_valor','ret_cofins_valor','ret_csll_valor','ret_outros_valor'
                                    ] as $campoRet) {
                                        $retencoes += (float) $pgts->sum($campoRet);
                                    }
                                    $cidadesStr = $pedido && $pedido->cidades ? $pedido->cidades->pluck('name')->join(', ') : '—';
                                @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-2">#{{ $n->id }}</td>
                                    <td class="px-4 py-2">#{{ $pedido->id ?? '—' }}</td>
                                    <td class="px-4 py-2">{{ $pedido->cliente->razao_social ?? '—' }}</td>
                                    <td class="px-4 py-2">{{ $pedido->gestor->razao_social ?? '—' }}</td>
                                    <td class="px-4 py-2">{{ $pedido->distribuidor->razao_social ?? '—' }}</td>
                                    <td class="px-4 py-2">{{ $cidadesStr }}</td>
                                    <td class="px-4 py-2">{{ $n->emitida_em ? \Carbon\Carbon::parse($n->emitida_em)->format('d/m/Y') : '—' }}</td>
                                    <td class="px-4 py-2">{{ $n->faturada_em ? \Carbon\Carbon::parse($n->faturada_em)->format('d/m/Y') : '—' }}</td>
                                    <td class="px-4 py-2">{!! $n->status_financeiro ? status_badge($n->status_financeiro) : '—' !!}</td>
                                    <td class="px-4 py-2 text-right">{{ moeda_br($n->valor_total ?? 0) }}</td>
                                    <td class="px-4 py-2 text-right">{{ moeda_br($liquido) }}</td>
                                    <td class="px-4 py-2 text-right">{{ moeda_br($retencoes) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50">
                            <tr>
                                <td colspan="9" class="px-4 py-3 text-right font-semibold">Totais:</td>
                                <td class="px-4 py-3 text-right font-bold">{{ moeda_br($totais['total_bruto']) }}</td>
                                <td class="px-4 py-3 text-right font-bold">{{ moeda_br($totais['total_liquido_pago']) }}</td>
                                <td class="px-4 py-3 text-right font-bold">{{ moeda_br($totais['total_retencoes']) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @else
                <div class="text-gray-600">Nenhuma nota encontrada para os filtros aplicados.</div>
            @endif
        </div>

        {{-- 4) CARDS DE COMISSÕES (com mini-tabelas alinhadas) --}}
        <div class="mt-8 grid md:grid-cols-2 gap-4 px-4">
            {{-- Gestores --}}
            <div class="bg-white border rounded-xl p-4 shadow-sm">
                <div class="text-xs text-gray-500">Comissão Gestores — Total</div>
                <div class="text-2xl font-semibold mt-1">{{ moeda_br($totais['comissao_gestor']) }}</div>

                @foreach($gestoresBreak as $gid => $g)
                    <div class="mt-3">
                        <div class="flex items-center justify-between text-sm">
                            <div class="font-medium truncate">{{ $g['nome'] }}</div>
                            <div class="text-gray-600">qtd: <strong>{{ $g['qtd'] }}</strong> — % <strong>{{ number_format($g['perc'],2,',','.') }}</strong> — <strong>{{ moeda_br($g['total']) }}</strong></div>
                        </div>
                        @php $itens = $gestoresDetalhe[$gid] ?? []; @endphp
                        @if($itens)
                            <div class="mt-2 border rounded-lg overflow-hidden">
                                <div class="max-h-56 overflow-y-auto">
                                    <table class="w-full text-xs table-fixed">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-2 py-1 text-left w-24">#Nota</th>
                                                <th class="px-2 py-1 text-right w-28">Base</th>
                                                <th class="px-2 py-1 text-right w-16">% </th>
                                                <th class="px-2 py-1 text-right w-28">Comissão</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            @foreach($itens as $item)
                                                <tr class="hover:bg-gray-50">
                                                    <td class="px-2 py-1">#{{ $item['nota'] }}</td>
                                                    <td class="px-2 py-1 text-right">{{ moeda_br($item['base']) }}</td>
                                                    <td class="px-2 py-1 text-right">{{ number_format($item['perc'],2,',','.') }}%</td>
                                                    <td class="px-2 py-1 text-right font-medium">{{ moeda_br($item['comissao']) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            {{-- Distribuidores --}}
            <div class="bg-white border rounded-xl p-4 shadow-sm">
                <div class="text-xs text-gray-500">Comissão Distribuidores — Total</div>
                <div class="text-2xl font-semibold mt-1">{{ moeda_br($totais['comissao_distribuidor']) }}</div>

                @foreach($distsBreak as $did => $d)
                    <div class="mt-3">
                        <div class="flex items-center justify-between text-sm">
                            <div class="font-medium truncate">{{ $d['nome'] }}</div>
                            <div class="text-gray-600">qtd: <strong>{{ $d['qtd'] }}</strong> — % <strong>{{ number_format($d['perc'],2,',','.') }}</strong> — <strong>{{ moeda_br($d['total']) }}</strong></div>
                        </div>
                        @php $itens = $distsDetalhe[$did] ?? []; @endphp
                        @if($itens)
                            <div class="mt-2 border rounded-lg overflow-hidden">
                                <div class="max-h-56 overflow-y-auto">
                                    <table class="w-full text-xs table-fixed">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-2 py-1 text-left w-24">#Nota</th>
                                                <th class="px-2 py-1 text-right w-28">Base</th>
                                                <th class="px-2 py-1 text-right w-16">% </th>
                                                <th class="px-2 py-1 text-right w-28">Comissão</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            @foreach($itens as $item)
                                                <tr class="hover:bg-gray-50">
                                                    <td class="px-2 py-1">#{{ $item['nota'] }}</td>
                                                    <td class="px-2 py-1 text-right">{{ moeda_br($item['base']) }}</td>
                                                    <td class="px-2 py-1 text-right">{{ number_format($item['perc'],2,',','.') }}%</td>
                                                    <td class="px-2 py-1 text-right font-medium">{{ moeda_br($item['comissao']) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            {{-- Advogados --}}
            <div class="bg-white border rounded-xl p-4 shadow-sm">
                <div class="text-xs text-gray-500">Comissão Advogados — Total</div>
                <div class="text-2xl font-semibold mt-1">{{ moeda_br($totais['comissao_advogado']) }}</div>

                @foreach($advogadosBreak as $aid => $a)
                    <div class="mt-3">
                        <div class="flex items-center justify-between text-sm">
                            <div class="font-medium truncate">{{ $a['nome'] }}</div>
                            <div class="text-gray-600">
                                qtd: <strong>{{ $a['qtd'] }}</strong>
                                @if(!is_null($a['perc'])) — % <strong>{{ number_format($a['perc'],2,',','.') }}</strong>@endif
                                — <strong>{{ moeda_br($a['total']) }}</strong>
                            </div>
                        </div>
                        @php $itens = $advogadosDetalhe[$aid] ?? []; @endphp
                        @if($itens)
                            <div class="mt-2 border rounded-lg overflow-hidden">
                                <div class="max-h-56 overflow-y-auto">
                                    <table class="w-full text-xs table-fixed">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-2 py-1 text-left w-24">#Nota</th>
                                                <th class="px-2 py-1 text-right w-28">Base</th>
                                                <th class="px-2 py-1 text-right w-16">% </th>
                                                <th class="px-2 py-1 text-right w-28">Comissão</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            @foreach($itens as $item)
                                                <tr class="hover:bg-gray-50">
                                                    <td class="px-2 py-1">#{{ $item['nota'] }}</td>
                                                    <td class="px-2 py-1 text-right">{{ moeda_br($item['base']) }}</td>
                                                    <td class="px-2 py-1 text-right">{{ number_format($item['perc'],2,',','.') }}%</td>
                                                    <td class="px-2 py-1 text-right font-medium">{{ moeda_br($item['comissao']) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            {{-- Diretores --}}
            <div class="bg-white border rounded-xl p-4 shadow-sm">
                <div class="text-xs text-gray-500">Comissão Diretores — Total</div>
                <div class="text-2xl font-semibold mt-1">{{ moeda_br($totais['comissao_diretor']) }}</div>

                @foreach($diretoresBreak as $did => $d)
                    <div class="mt-3">
                        <div class="flex items-center justify-between text-sm">
                            <div class="font-medium truncate">{{ $d['nome'] }}</div>
                            <div class="text-gray-600">
                                qtd: <strong>{{ $d['qtd'] }}</strong>
                                @if(!is_null($d['perc'])) — % <strong>{{ number_format($d['perc'],2,',','.') }}</strong>@endif
                                — <strong>{{ moeda_br($d['total']) }}</strong>
                            </div>
                        </div>
                        @php $itens = $diretoresDetalhe[$did] ?? []; @endphp
                        @if($itens)
                            <div class="mt-2 border rounded-lg overflow-hidden">
                                <div class="max-h-56 overflow-y-auto">
                                    <table class="w-full text-xs table-fixed">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-2 py-1 text-left w-24">#Nota</th>
                                                <th class="px-2 py-1 text-right w-28">Base</th>
                                                <th class="px-2 py-1 text-right w-16">% </th>
                                                <th class="px-2 py-1 text-right w-28">Comissão</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            @foreach($itens as $item)
                                                <tr class="hover:bg-gray-50">
                                                    <td class="px-2 py-1">#{{ $item['nota'] }}</td>
                                                    <td class="px-2 py-1 text-right">{{ moeda_br($item['base']) }}</td>
                                                    <td class="px-2 py-1 text-right">{{ number_format($item['perc'],2,',','.') }}%</td>
                                                    <td class="px-2 py-1 text-right font-medium">{{ moeda_br($item['comissao']) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        {{-- 5) CARDS DE RETENÇÕES (detalhe por nota) --}}
        <div class="mt-8 px-4">
            <h3 class="font-semibold text-lg mb-3">Retenções por nota (detalhe)</h3>
            @if($notas->count())
                <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-4">
                    @foreach($notas as $n)
                        @php
                            $pgts = $n->pagamentos ?? collect();
                            if (!empty($dataInicio) && !empty($dataFim)) {
                                $pgts = $pgts->filter(function ($pg) use ($dataInicio, $dataFim) {
                                    $d = \Carbon\Carbon::parse($pg->data_pagamento)->toDateString();
                                    return $d >= $dataInicio && $d <= $dataFim;
                                });
                            }

                            $map = [
                                'IRRF'   => 'ret_irrf_valor',
                                'ISS'    => 'ret_iss_valor',
                                'INSS'   => 'ret_inss_valor',
                                'PIS'    => 'ret_pis_valor',
                                'COFINS' => 'ret_cofins_valor',
                                'CSLL'   => 'ret_csll_valor',
                                'Outros' => 'ret_outros_valor',
                            ];
                            $sumRet = [];
                            $totalRet = 0.0;
                            foreach ($map as $lbl => $col) {
                                $v = (float) $pgts->sum($col);
                                $sumRet[$lbl] = $v;
                                $totalRet += $v;
                            }
                        @endphp

                        <div class="bg-white border rounded-xl p-4 shadow-sm">
                            <div class="flex items-center justify-between">
                                <div class="font-semibold">Nota #{{ $n->id }}</div>
                                <div class="text-sm text-gray-500">{{ $n->emitida_em ? \Carbon\Carbon::parse($n->emitida_em)->format('d/m/Y') : '—' }}</div>
                            </div>
                            <div class="text-sm text-gray-600 mt-1">
                                Cliente: <strong>{{ optional($n->pedido->cliente)->razao_social ?? '—' }}</strong>
                            </div>

                            <div class="mt-3 border rounded-lg overflow-hidden">
                                <table class="w-full text-xs">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-2 py-1 text-left">Retenção</th>
                                            <th class="px-2 py-1 text-right">Valor</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @foreach($sumRet as $lbl => $val)
                                            @if($val > 0)
                                                <tr>
                                                    <td class="px-2 py-1">{{ $lbl }}</td>
                                                    <td class="px-2 py-1 text-right">{{ moeda_br($val) }}</td>
                                                </tr>
                                            @endif
                                        @endforeach
                                    </tbody>
                                    <tfoot class="bg-gray-50">
                                        <tr>
                                            <td class="px-2 py-1 text-right font-semibold">Total</td>
                                            <td class="px-2 py-1 text-right font-bold">{{ moeda_br($totalRet) }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-gray-600">Sem notas no resultado atual.</div>
            @endif
        </div>
    </div>

    {{-- Auto-submit dos selects "exclusivos" (cliente/gestor/distribuidor) --}}
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
