<x-app-layout>
    <x-slot name="header">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">

        {{-- Título --}}
        <div class="space-y-1">
            <h2 class="text-2xl font-bold text-gray-800 leading-tight">
                Nota #{{ $nota->numero ?? $nota->id }} — {{ strtoupper($nota->status) }}
            </h2>
            <p class="text-sm text-gray-500">
                Visualize dados, itens, totais e financeiro da nota.
            </p>
        </div>

        {{-- Ações --}}
        <div class="flex flex-wrap gap-2 justify-start lg:justify-end">

            {{-- Voltar --}}
            <a href="{{ route('admin.pedidos.show', $nota->pedido_id) }}"
               class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 h-10 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition">
                ← Voltar ao Pedido
            </a>

            {{-- Detalhes do pagamento --}}
            @if(!empty($pagamentoAtual))
                <a href="{{ route('admin.notas.pagamentos.show', [$nota, $pagamentoAtual]) }}"
                   class="inline-flex items-center px-4 h-10 rounded-lg bg-indigo-600 text-sm font-semibold text-white hover:bg-indigo-700">
                    Detalhes do Pagamento
                </a>
            @endif

            {{-- Faturar / Faturada --}}
            @if($nota->status === 'emitida')
                <form action="{{ route('admin.notas.faturar', $nota) }}" method="POST"
                      onsubmit="return confirm('Faturar nota? Isto baixará o estoque.');">
                    @csrf
                    <button class="inline-flex items-center px-4 h-10 rounded-lg bg-emerald-600 text-sm font-semibold text-white hover:bg-emerald-700">
                        Faturar Nota
                    </button>
                </form>
            @elseif($nota->status === 'faturada')
                <span class="inline-flex items-center px-3 h-10 rounded-lg border border-emerald-200 bg-emerald-50 text-sm font-semibold text-emerald-800">
                    Nota Faturada
                </span>
            @endif

            {{-- Exportar PDF --}}
            <a href="{{ route('admin.notas.pdf', $nota) }}" target="_blank"
               class="inline-flex items-center px-4 h-10 rounded-lg bg-red-600 text-sm font-semibold text-white hover:bg-red-700">
                PDF
            </a>

            {{-- Registrar pagamento --}}
            @if ($nota->status_financeiro === 'aguardando_pagamento' || $nota->status_financeiro === 'pago_parcial')
                <a href="{{ route('admin.notas.pagamentos.create', $nota) }}"
                   class="inline-flex items-center px-4 h-10 rounded-lg bg-emerald-600 text-sm font-semibold text-white hover:bg-emerald-700">
                    Registrar Pagamento
                </a>
            @endif

            {{-- PlugNotas --}}
            @if ($nota->status_financeiro === 'aguardando_pagamento')
                <form action="{{ route('admin.notas.plug.emitir', $nota) }}" method="POST"
                      onsubmit="return confirm('Enviar esta NF para a SEFAZ (PlugNotas)?');">
                    @csrf
                    <button class="inline-flex items-center px-4 h-10 rounded-lg bg-indigo-600 text-sm font-semibold text-white hover:bg-indigo-700"
                            {{ $nota->plugnotas_id ? 'disabled' : '' }}>
                        Emitir NF-e
                    </button>
                </form>

                <a href="{{ route('admin.notas.plug.consultar', $nota) }}"
                   class="inline-flex items-center px-4 h-10 rounded-lg bg-gray-800 text-sm font-semibold text-white hover:bg-gray-900
                          {{ $nota->plugnotas_id ? '' : 'pointer-events-none opacity-50' }}">
                    Consultar Status
                </a>
            @endif

            @php
                $pnHasId  = !empty($nota->plugnotas_id);
                $pnStatus = strtoupper(trim((string) $nota->plugnotas_status));
                $pnDone   = in_array($pnStatus, ['CONCLUIDO','AUTORIZADO','AUTORIZADA','APROVADO','APROVADA']);
            @endphp

            @if($pnHasId)
                <a href="{{ route('admin.notas.plug.pdf', $nota) }}" target="_blank"
                   class="inline-flex items-center px-4 h-10 rounded-lg bg-green-600 text-sm font-semibold text-white hover:bg-green-700
                          {{ $pnDone ? '' : 'opacity-50 pointer-events-none' }}">
                    DANFE
                </a>

                <a href="{{ route('admin.notas.plug.xml', $nota) }}" target="_blank"
                   class="inline-flex items-center px-4 h-10 rounded-lg bg-emerald-600 text-sm font-semibold text-white hover:bg-emerald-700
                          {{ $pnDone ? '' : 'opacity-50 pointer-events-none' }}">
                    XML
                </a>
            @endif

        </div>
    </div>
</x-slot>

    <div class="p-6 space-y-8 max-w-5xl mx-auto">

        {{-- Mensagens --}}
        @if (session('success'))
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-800 shadow-sm">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-800 shadow-sm">
                {{ session('error') }}
            </div>
        @endif

        {{-- Informações Gerais --}}
        <div class="bg-white p-6 rounded-xl shadow-sm ring-1 ring-gray-100">
            <div class="flex items-center justify-between gap-4 mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Informações Gerais</h3>

                <div class="flex flex-wrap items-center gap-2">
                    @php
                        $notaStatusMap = [
                            'emitida'   => 'bg-blue-100 text-blue-800 border-blue-200',
                            'faturada'  => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                            'cancelada' => 'bg-red-100 text-red-800 border-red-200',
                        ];
                        $notaStatusClasses = $notaStatusMap[$nota->status] ?? 'bg-gray-100 text-gray-800 border-gray-200';

                        $finKey         = $nota->status_financeiro;
                        $notaFinLabel   = null;
                        $notaFinClasses = null;

                        if ($finKey) {
                            $notaFinMap = [
                                'aguardando_pagamento' => ['Aguardando pagamento', 'bg-amber-100 text-amber-800 border-amber-200'],
                                'pago_parcial'         => ['Pago parcial',         'bg-blue-100 text-blue-800 border-blue-200'],
                                'pago'                 => ['Pago',                 'bg-green-100 text-green-800 border-green-200'],
                            ];
                            [$notaFinLabel, $notaFinClasses] = $notaFinMap[$finKey]
                                ?? [ucfirst(str_replace('_',' ',$finKey)), 'bg-gray-100 text-gray-800 border-gray-200'];
                        }

                        $valTotal = (float) $nota->valor_total;
                        $valPago  = (float) $nota->total_pago_bruto;
                        $pct = $valTotal > 0 ? min(100, floor(($valPago / $valTotal) * 100)) : 0;
                    @endphp

                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full border {{ $notaStatusClasses }}">
                        <span class="text-[11px] font-semibold uppercase opacity-70">Nota</span>
                        <span class="opacity-70">•</span>
                        <span class="font-semibold">{{ ucfirst($nota->status) }}</span>
                    </span>

                    @if($notaFinLabel)
                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full border {{ $notaFinClasses }}">
                            <span class="text-[11px] font-semibold uppercase opacity-70">Financeiro</span>
                            <span class="opacity-70">•</span>
                            <span class="font-semibold">{{ $notaFinLabel }}</span>
                        </span>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-700">
                <div>
                    <div class="text-xs text-gray-500">Número</div>
                    <div class="font-medium">{{ $nota->numero ?? '-' }}</div>
                </div>

                <div>
                    <div class="text-xs text-gray-500">Série</div>
                    <div class="font-medium">{{ $nota->serie ?? '-' }}</div>
                </div>

                @if($nota->status_financeiro === 'pago_parcial')
                    <div class="md:col-span-2">
                        <div class="w-full mt-1">
                            <div class="h-2 bg-gray-200 rounded">
                                <div class="h-2 rounded bg-blue-600" style="width: {{ $pct }}%"></div>
                            </div>
                            <div class="text-xs text-gray-600 mt-2">
                                Recebido: R$ {{ number_format($valPago, 2, ',', '.') }}
                                ({{ $pct }}%) de
                                R$ {{ number_format($valTotal, 2, ',', '.') }}
                            </div>
                        </div>
                    </div>
                @endif

                <div>
                    <div class="text-xs text-gray-500">Ambiente</div>
                    <div class="font-medium">{{ strtoupper($nota->ambiente ?? 'INTERNO') }}</div>
                </div>

                <div>
                    <div class="text-xs text-gray-500">Emitida em</div>
                    <div class="font-medium">{{ optional($nota->emitida_em)->format('d/m/Y H:i') }}</div>
                </div>

                <div>
                    <div class="text-xs text-gray-500">Faturada em</div>
                    <div class="font-medium">{{ optional($nota->faturada_em)->format('d/m/Y H:i') ?? '-' }}</div>
                </div>

                @if($nota->status_financeiro === 'pago' && $nota->pago_em)
                    <div>
                        <div class="text-xs text-gray-500">Pago em</div>
                        <div class="font-medium">{{ $nota->pago_em->format('d/m/Y H:i') }}</div>
                    </div>
                @endif

                <div>
                    <div class="text-xs text-gray-500">Pedido</div>
                    <div class="font-medium">
                        <a class="underline text-blue-600" href="{{ route('admin.pedidos.show', $nota->pedido_id) }}">
                            #{{ $nota->pedido_id }}
                        </a>
                    </div>
                </div>

                <div>
                    <div class="text-xs text-gray-500">Cliente</div>
                    <div class="font-medium">{{ $nota->pedido?->cliente?->razao_social ?? '-' }}</div>
                </div>
            </div>
        </div>

        {{-- Itens --}}
        <div class="bg-white p-6 rounded-xl shadow-sm ring-1 ring-gray-100">
            <div class="flex items-center justify-between gap-4 mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Itens</h3>
            </div>

            <div class="overflow-x-auto rounded-lg border border-gray-200">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-gray-700">
                        <tr class="text-left border-b">
                            <th class="py-2 px-3">Produto</th>
                            <th class="py-2 px-3 text-right">Qtd</th>
                            <th class="py-2 px-3 text-right">Unitário</th>
                            <th class="py-2 px-3 text-right">Desc. %</th>
                            <th class="py-2 px-3 text-right">Subtotal</th>
                            <th class="py-2 px-3 text-right">Peso (kg)</th>
                            <th class="py-2 px-3 text-center">Caixas</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-700">
                    @foreach($nota->itens as $item)
                        <tr class="border-t">
                            <td class="py-2 px-3">{{ $item->descricao_produto ?? $item->produto?->nome }}</td>
                            <td class="py-2 px-3 text-right">{{ $item->quantidade }}</td>
                            <td class="py-2 px-3 text-right">R$ {{ number_format($item->preco_unitario, 2, ',', '.') }}</td>
                            <td class="py-2 px-3 text-right">{{ number_format($item->desconto_aplicado, 2, ',', '.') }}%</td>
                            <td class="py-2 px-3 text-right">R$ {{ number_format($item->subtotal, 2, ',', '.') }}</td>
                            <td class="py-2 px-3 text-right">{{ number_format($item->peso_total_produto, 3, ',', '.') }}</td>
                            <td class="py-2 px-3 text-center">{{ $item->caixas }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Totais --}}
        <div class="bg-white p-6 rounded-xl shadow-sm ring-1 ring-gray-100">
            <h3 class="text-lg font-semibold mb-4 text-gray-800">Totais</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-gray-700">
                <div><strong>Valor bruto:</strong> R$ {{ number_format($nota->valor_bruto, 2, ',', '.') }}</div>
                <div><strong>Desconto total:</strong> R$ {{ number_format($nota->desconto_total, 2, ',', '.') }}</div>
                <div><strong>Valor total:</strong> <span class="font-semibold">R$ {{ number_format($nota->valor_total, 2, ',', '.') }}</span></div>
                <div><strong>Peso total:</strong> {{ number_format($nota->peso_total, 3, ',', '.') }} kg</div>
                <div><strong>Total de caixas:</strong> {{ $nota->total_caixas }}</div>
            </div>
        </div>

        {{-- Resumo Financeiro --}}
        <div class="bg-white p-6 rounded-xl shadow-sm ring-1 ring-gray-100">
            <h3 class="text-lg font-semibold mb-4 text-gray-800">Resumo Financeiro</h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-gray-700">
                <div>
                    <strong>Status financeiro:</strong>
                    @php
                        $finKey = $nota->status_financeiro;
                        $finBadge = match($finKey) {
                            'aguardando_pagamento' => 'bg-amber-100 text-amber-800 border-amber-200',
                            'pago_parcial'         => 'bg-blue-100 text-blue-800 border-blue-200',
                            'pago'                 => 'bg-green-100 text-green-800 border-green-200',
                            default                => 'bg-gray-100 text-gray-800 border-gray-200',
                        };
                    @endphp
                    <span class="inline-flex items-center px-2 py-0.5 rounded border {{ $finBadge }}">
                        {{ $finKey ? str_replace('_',' ', ucfirst($finKey)) : '—' }}
                    </span>
                </div>

                <div>
                    <strong>Recebido (bruto):</strong>
                    <span class="font-medium">R$ {{ number_format($nota->total_pago_bruto, 2, ',', '.') }}</span>
                </div>

                <div>
                    <strong>Recebido (líquido):</strong>
                    <span>R$ {{ number_format($nota->total_pago_liquido, 2, ',', '.') }}</span>
                </div>

                <div>
                    <strong>Saldo a receber:</strong>
                    <span class="font-semibold {{ $nota->saldo_pendente > 0 ? 'text-amber-700' : 'text-emerald-700' }}">
                        R$ {{ number_format($nota->saldo_pendente, 2, ',', '.') }}
                    </span>
                </div>

                @if($nota->status_financeiro === 'pago' && $nota->pago_em)
                    <div class="md:col-span-3">
                        <strong>Quitado em:</strong> {{ $nota->pago_em->format('d/m/Y H:i') }}
                    </div>
                @endif
            </div>

            @if($nota->status_financeiro === 'aguardando_pagamento' || $nota->status_financeiro === 'pago_parcial')
                <div class="mt-4">
                    <a href="{{ route('admin.notas.pagamentos.create', $nota) }}"
                       class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 h-10 text-sm font-semibold text-white hover:bg-emerald-700 transition">
                        Registrar pagamento
                    </a>
                </div>
            @endif
        </div>

        {{-- Observações --}}
        <div class="bg-white p-6 rounded-xl shadow-sm ring-1 ring-gray-100">
            <h3 class="text-lg font-semibold mb-2 text-gray-800">Observações</h3>
            @php
                $obs = $nota->observacoes
                    ?? ($nota->pedido_snapshot['observacoes'] ?? null)
                    ?? ($nota->pedido?->observacoes ?? null);
            @endphp
            <div class="text-sm text-gray-800 whitespace-pre-wrap">
                {{ $obs ?: '—' }}
            </div>
        </div>

        {{-- Pagamentos --}}
        @if($nota->pagamentos->count())
            <div class="bg-white p-6 rounded-xl shadow-sm ring-1 ring-gray-100">
                <h4 class="text-lg font-semibold text-gray-800 mb-4">Pagamentos</h4>

                <div class="overflow-x-auto rounded-lg border border-gray-200">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 text-gray-700">
                            <tr>
                                <th class="px-3 py-2 text-left">Data</th>
                                <th class="px-3 py-2 text-right">Valor Pago</th>
                                <th class="px-3 py-2 text-right">Retenções</th>
                                <th class="px-3 py-2 text-right">Líquido</th>
                                <th class="px-3 py-2 text-right">Com. Adv.</th>
                                <th class="px-3 py-2 text-right">Com. Dir.</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-700">
                        @foreach($nota->pagamentos as $pg)
                            <tr class="border-t">
                                <td class="px-3 py-2">{{ optional($pg->data_pagamento)->format('d/m/Y') ?: '-' }}</td>
                                <td class="px-3 py-2 text-right">R$ {{ number_format($pg->valor_pago, 2, ',', '.') }}</td>
                                <td class="px-3 py-2 text-right">R$ {{ number_format($pg->total_retencoes, 2, ',', '.') }}</td>
                                <td class="px-3 py-2 text-right">R$ {{ number_format($pg->valor_liquido, 2, ',', '.') }}</td>
                                <td class="px-3 py-2 text-right">R$ {{ number_format($pg->comissao_advogado, 2, ',', '.') }}</td>
                                <td class="px-3 py-2 text-right">R$ {{ number_format($pg->comissao_diretor, 2, ',', '.') }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

    </div>
</x-app-layout>
