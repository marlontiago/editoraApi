<x-app-layout>
    @php
        $statusMap = [
            'em_andamento' => ['Em andamento', 'bg-yellow-100 text-yellow-800 border-yellow-200'],
            'pre_aprovado' => ['Pré-aprovado',  'bg-sky-100 text-sky-800 border-sky-200'],
            'finalizado'   => ['Finalizado',   'bg-green-100 text-green-800 border-green-200'],
            'cancelado'    => ['Cancelado',    'bg-red-100 text-red-800 border-red-200'],
        ];
        $notaStatusMap = [
            'emitida'   => ['Emitida',   'bg-blue-100 text-blue-800 border-blue-200'],
            'faturada'  => ['Faturada',  'bg-emerald-100 text-emerald-800 border-emerald-200'],
            'cancelada' => ['Cancelada', 'bg-red-100 text-red-800 border-red-200'],
        ];
        $notaFinMap = [
            'aguardando_pagamento' => ['Aguardando pagamento', 'bg-amber-100 text-amber-800 border-amber-200'],
            'pago'                  => ['Pago',                 'bg-green-100 text-green-800 border-green-200'],
            'simples_remessa'       => ['Simples remessa',     'bg-indigo-100 text-indigo-800 border-indigo-200'],
            'brinde'                => ['Brinde',              'bg-fuchsia-100 text-fuchsia-800 border-fuchsia-200'],
        ];

        $status = $pedido->status;
        [$statusLabel, $statusClasses] = $statusMap[$status]
            ?? [ucfirst(str_replace('_',' ',$status)), 'bg-gray-100 text-gray-800 border-gray-200'];

        // Defaults
        $notaLabel = null; $notaClasses = null;
        $notaFinLabel = null; $notaFinClasses = null;

        if (!empty($notaAtual)) {
            [$notaLabel, $notaClasses] = $notaStatusMap[$notaAtual->status]
                ?? [ucfirst($notaAtual->status), 'bg-gray-100 text-gray-800 border-gray-200'];

            $finKey = $notaAtual->status_financeiro;
            if ($finKey) {
                [$notaFinLabel, $notaFinClasses] = $notaFinMap[$finKey]
                    ?? [ucfirst(str_replace('_',' ',$finKey)), 'bg-gray-100 text-gray-800 border-gray-200'];
            }
        }

        // detectar se a PRÉVIA (nota emitida) está desatualizada em relação ao pedido
        $notaEmitidaEm = optional($notaEmitida)->emitida_em ?? optional($notaEmitida)->updated_at;
        $notaDesatualizada = $notaEmitida && $pedido->updated_at && $notaEmitidaEm && $pedido->updated_at->gt($notaEmitidaEm);
    @endphp

    <x-slot name="header">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
            {{-- Título / subtítulo --}}
            <div class="space-y-1">
                <h2 class="text-2xl font-bold text-gray-800 leading-tight">
                    Detalhes do Pedido #{{ $pedido->id }}
                </h2>
                <p class="text-sm text-gray-500">
                    Visualize itens, totais, status e ações do pedido.
                </p>                
            </div>

            {{-- Ações no HEADER (Voltar + botões) --}}
            <div class="flex flex-wrap items-center gap-2 lg:justify-end">
                {{-- Voltar --}}
                <a href="{{ route('admin.pedidos.index') }}"
                   class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 h-10 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Voltar
                </a>

                @if($pedido->status !== 'cancelado' && $pedido->status !== 'finalizado')
                    <a href="{{ route('admin.pedidos.edit', $pedido) }}"
                       class="inline-flex items-center gap-2 rounded-lg bg-yellow-500 px-4 h-10 text-sm font-semibold text-white hover:bg-yellow-600 transition">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 20h9"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4 12.5-12.5z"/>
                        </svg>
                        Editar
                    </a>
                @endif

                <a href="{{ route('admin.pedidos.exportar', ['pedido' => $pedido->id, 'tipo' => 'relatorio']) }}"
                   class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 h-10 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v12m0 0l4-4m-4 4l-4-4"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 17v3h16v-3"/>
                    </svg>
                    Exportar Relatório
                </a>

                <a href="{{ route('admin.pedidos.exportar', ['pedido' => $pedido->id, 'tipo' => 'orcamento']) }}"
                   class="inline-flex items-center gap-2 rounded-lg bg-gray-900 px-4 h-10 text-sm font-semibold text-white hover:bg-gray-800 transition">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 16h6"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 7h14M7 3h10a2 2 0 012 2v16H5V5a2 2 0 012-2z"/>
                    </svg>
                    Exportar Orçamento
                </a>

                @if($notaAtual)
                    <a href="{{ route('admin.notas.show', ['nota' => $notaAtual]) }}"
                       class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 h-10 text-sm font-semibold text-white hover:bg-blue-700 transition">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        Ver Nota
                    </a>
                @endif

                {{-- Botões de Nota Fiscal --}}
                @if($pedido->status !== 'cancelado')
                    @if(!empty($temNotaFaturada) && $temNotaFaturada)
                        <span class="inline-flex items-center rounded-lg border border-green-200 bg-green-50 px-3 h-10 text-sm font-semibold text-green-800">
                            Nota faturada
                        </span>

                    @elseif(!empty($notaEmitida))
                        @if($notaDesatualizada)
                            <span class="inline-flex items-center rounded-lg border border-amber-200 bg-amber-50 px-3 h-10 text-sm font-semibold text-amber-800">
                                Para faturar, reemitir pré-visualização
                            </span>

                            <form action="{{ route('admin.pedidos.emitir-nota', $pedido) }}" method="POST"
                                  onsubmit="return confirm('Reemitir a PRÉ-VISUALIZAÇÃO com os dados atuais do pedido? A prévia anterior será cancelada.');"
                                  class="inline-block">
                                @csrf
                                <input type="hidden" name="substituir" value="1">
                                <button class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 h-10 text-sm font-semibold text-white hover:bg-indigo-700 transition">
                                    Reemitir Pré-visualização
                                </button>
                            </form>
                        @else
                            <div x-data="{ openFat:false, modo:'normal' }" class="inline-block">
                                <button type="button"
                                        @click="openFat = true; modo = 'normal';"
                                        class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 h-10 text-sm font-semibold text-white hover:bg-emerald-700 transition">
                                    Faturar Nota
                                </button>

                                {{-- Modal --}}
                                <div x-show="openFat" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
                                    <div class="absolute inset-0 bg-black/40" @click="openFat=false"></div>

                                    <div class="relative bg-white w-full max-w-md rounded-xl shadow-lg border border-gray-200">
                                        <div class="p-4 border-b">
                                            <h4 class="text-lg font-semibold text-gray-800">Tipo de faturamento</h4>
                                            <p class="text-sm text-gray-600 mt-1">Escolha como essa nota será faturada.</p>
                                        </div>

                                        <form method="POST" action="{{ route('admin.notas.faturar', $notaEmitida) }}" class="p-4 space-y-4">
                                            @csrf
                                            <div class="space-y-2">
                                                <label class="flex items-start gap-3 p-3 rounded-lg border cursor-pointer hover:bg-gray-50">
                                                    <input type="radio" name="modo_faturamento" value="normal" x-model="modo" class="mt-1">
                                                    <div>
                                                        <div class="font-medium text-gray-900">Normal</div>
                                                        <div class="text-sm text-gray-600">Baixa o estoque e define financeiro para <strong>aguardando_pagamento</strong>.</div>
                                                    </div>
                                                </label>

                                                <label class="flex items-start gap-3 p-3 rounded-lg border cursor-pointer hover:bg-gray-50">
                                                    <input type="radio" name="modo_faturamento" value="simples_remessa" x-model="modo" class="mt-1">
                                                    <div>
                                                        <div class="font-medium text-gray-900">Simples Remessa</div>
                                                        <div class="text-sm text-gray-600">Não baixa o estoque e define financeiro como <strong>simples_remessa</strong>.</div>
                                                    </div>
                                                </label>

                                                <label class="flex items-start gap-3 p-3 rounded-lg border cursor-pointer hover:bg-gray-50">
                                                    <input type="radio" name="modo_faturamento" value="brinde" x-model="modo" class="mt-1">
                                                    <div>
                                                        <div class="font-medium text-gray-900">Brinde</div>
                                                        <div class="text-sm text-gray-600">Baixa o estoque e define financeiro como <strong>brinde</strong>.</div>
                                                    </div>
                                                </label>
                                            </div>

                                            <div class="flex items-center justify-end gap-2 pt-2 border-t">
                                                <button type="button"
                                                        class="px-4 py-2 rounded-lg border border-gray-200 text-gray-700 hover:bg-gray-50"
                                                        @click="openFat=false">
                                                    Cancelar
                                                </button>
                                                <button type="submit"
                                                        class="px-4 py-2 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700">
                                                    Confirmar faturamento
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <form action="{{ route('admin.pedidos.emitir-nota', $pedido) }}" method="POST"
                                  onsubmit="return confirm('Emitir NOVA pré-visualização com os dados atuais do pedido? A nota emitida será cancelada.');"
                                  class="inline-block">
                                @csrf
                                <input type="hidden" name="substituir" value="1">
                                <button class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 h-10 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition">
                                    Nova Pré-visualização
                                </button>
                            </form>
                        @endif
                    @else
                        <form action="{{ route('admin.pedidos.emitir-nota', $pedido) }}" method="POST"
                              onsubmit="return confirm('Emitir nota interna para este pedido?');">
                            @csrf
                            <button class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 h-10 text-sm font-semibold text-white hover:bg-indigo-700 transition">
                                Pré-visualização de Nota
                            </button>
                        </form>
                    @endif
                @endif
            </div>
        </div>
    </x-slot>

    <div class="p-6 space-y-8 max-w-7xl mx-auto">

        {{-- Mensagens de feedback --}}
        @if (session('error'))
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-800 shadow-sm">
                {{ session('error') }}
            </div>
        @endif
        @if (session('success'))
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-800 shadow-sm">
                {{ session('success') }}
            </div>
        @endif

        {{-- Informações principais --}}
        <div class="bg-white p-6 rounded-xl shadow-sm ring-1 ring-gray-100">
            <div class="flex items-center justify-between gap-4 mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Informações Gerais</h3>

                <div class="flex flex-wrap items-center gap-2 pt-1">
                    {{-- Status do Pedido --}}
                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full border {{ $statusClasses }}">
                        <span class="text-[11px] font-semibold uppercase opacity-70">Pedido</span>
                        <span class="opacity-70">•</span>
                        <span class="font-semibold">{{ $statusLabel }}</span>
                    </span>

                    {{-- Status Financeiro (da Nota) --}}
                    @if(!empty($notaAtual) && !empty($notaAtual->status_financeiro))
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
                    <div class="text-xs text-gray-500">Data</div>
                    <div class="font-medium">{{ \Carbon\Carbon::parse($pedido->data)->format('d/m/Y') }}</div>
                </div>

                <div>
                    <div class="text-xs text-gray-500">Cliente</div>
                    <div class="font-medium">{{ optional($pedido->cliente)->razao_social ?? optional($pedido->cliente)->nome ?? '-' }}</div>
                </div>

                <div>
                    <div class="text-xs text-gray-500">Gestor</div>
                    <div class="font-medium">
                        @if($pedido->gestor)
                            {{ $pedido->gestor->razao_social }}
                            @if($pedido->gestor->estado_uf)
                                <span class="text-xs text-gray-500">/ UF: {{ strtoupper($pedido->gestor->estado_uf) }}</span>
                            @endif
                        @else
                            -
                        @endif
                    </div>
                </div>

                <div>
                    <div class="text-xs text-gray-500">Distribuidor</div>
                    <div class="font-medium">
                        @if($pedido->distribuidor)
                            {{ $pedido->distribuidor->razao_social ?? '-' }}
                            @if(optional($pedido->distribuidor->user)->name)
                                <span class="text-xs text-gray-500">({{ $pedido->distribuidor->user->name }})</span>
                            @endif
                        @else
                            -
                        @endif
                    </div>
                </div>

                <div class="md:col-span-2">
                    <div class="text-xs text-gray-500">Cidades</div>
                    <div class="mt-1">
                        @forelse($pedido->cidades as $cidade)
                            <span class="inline-flex items-center gap-1 bg-gray-100 text-gray-700 text-xs px-2 py-1 rounded-full mr-1 mb-1 border border-gray-200">
                                {{ $cidade->name }}
                                @if($cidade->state)
                                    <span class="text-[10px] opacity-70">({{ strtoupper($cidade->state) }})</span>
                                @endif
                            </span>
                        @empty
                            <span class="text-gray-500">-</span>
                        @endforelse
                    </div>
                </div>

                @if(!empty($notaAtual))
                    <div class="md:col-span-2">
                        <div class="text-xs text-gray-500">Nota Fiscal</div>
                        <div class="mt-1 flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full border {{ $notaClasses }}">
                                {{ $notaLabel }}
                            </span>
                            <span class="text-gray-700">
                                Número: <span class="font-medium">{{ $notaAtual->numero ?? '-' }}</span>
                                @if($notaAtual->serie)
                                    • Série: <span class="font-medium">{{ $notaAtual->serie }}</span>
                                @endif
                                • Emitida em: <span class="font-medium">{{ optional($notaAtual->emitida_em)->format('d/m/Y H:i') ?? '-' }}</span>
                                @if($notaAtual->faturada_em)
                                    • Faturada em: <span class="font-medium">{{ optional($notaAtual->faturada_em)->format('d/m/Y H:i') }}</span>
                                @endif
                            </span>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Produtos --}}
        <div class="bg-white p-6 rounded-xl shadow-sm ring-1 ring-gray-100">
            <div class="flex items-center justify-between gap-4 mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Produtos do Pedido</h3>
                <div class="text-sm text-gray-500">
                    Itens: <span class="font-semibold text-gray-700">{{ $pedido->produtos->count() }}</span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full border border-gray-200 rounded-lg overflow-hidden text-sm">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="px-4 py-2 text-left">Produto</th>
                            <th class="px-4 py-2 text-center">Qtd</th>
                            <th class="px-4 py-2 text-right">Preço Unit.</th>
                            <th class="px-4 py-2 text-right">Desc. (%)</th>
                            <th class="px-4 py-2 text-right">Desconto (R$)</th>
                            <th class="px-4 py-2 text-right">Subtotal</th>
                            <th class="px-4 py-2 text-right">Peso Total (kg)</th>
                            <th class="px-4 py-2 text-center">Caixas</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pedido->produtos as $produto)
                            @php
                                $qtd          = (int) ($produto->pivot->quantidade ?? 0);
                                $precoUnit    = (float) ($produto->pivot->preco_unitario ?? 0);
                                $brutoItem    = $precoUnit * $qtd;
                                $subtotalItem = (float) ($produto->pivot->subtotal ?? 0);
                                $valorDesc    = max(0, $brutoItem - $subtotalItem);
                                $percDesc     = $brutoItem > 0 ? ($valorDesc / $brutoItem) * 100 : 0;
                                $pesoTotalProd= (float) ($produto->pivot->peso_total_produto ?? 0);
                                $caixas       = (int) ($produto->pivot->caixas ?? 0);
                            @endphp
                            <tr class="border-t">
                                <td class="px-4 py-2">
                                    <div class="font-medium text-gray-800">{{ $produto->titulo }}</div>
                                </td>
                                <td class="px-4 py-2 text-center">{{ $qtd }}</td>
                                <td class="px-4 py-2 text-right">R$ {{ number_format($precoUnit, 2, ',', '.') }}</td>
                                <td class="px-4 py-2 text-right">{{ number_format($percDesc, 2, ',', '.') }}%</td>
                                <td class="px-4 py-2 text-right">R$ {{ number_format($valorDesc, 2, ',', '.') }}</td>
                                <td class="px-4 py-2 text-right">R$ {{ number_format($subtotalItem, 2, ',', '.') }}</td>
                                <td class="px-4 py-2 text-right">{{ number_format($pesoTotalProd, 2, ',', '.') }}</td>
                                <td class="px-4 py-2 text-center">{{ $caixas }}</td>
                            </tr>
                        @endforeach
                    </tbody>

                    @php
                        $valorBruto = (float) ($pedido->valor_bruto ?? 0);
                        $valorTotal = (float) ($pedido->valor_total ?? 0);
                        $pesoTotal  = (float) ($pedido->peso_total ?? 0);
                        $totalCaixas= (int)   ($pedido->total_caixas ?? 0);

                        $totalDescontos = $pedido->produtos->sum(function ($p) {
                            $q  = (int) ($p->pivot->quantidade ?? 0);
                            $pu = (float) ($p->pivot->preco_unitario ?? 0);
                            $bruto = $pu * $q;
                            $sub   = (float) ($p->pivot->subtotal ?? 0);
                            return max(0, $bruto - $sub);
                        });

                        $percTotal = $valorBruto > 0 ? ($totalDescontos / $valorBruto) * 100 : 0;
                    @endphp

                    <tfoot class="bg-gray-50 font-semibold">
                        <tr class="border-t">
                            <td colspan="4" class="px-4 py-2 text-right">Valor Bruto:</td>
                            <td class="px-4 py-2 text-right">R$ {{ number_format($valorBruto, 2, ',', '.') }}</td>
                            <td colspan="3"></td>
                        </tr>
                        <tr>
                            <td colspan="4" class="px-4 py-2 text-right">Percentual de Desconto:</td>
                            <td class="px-4 py-2 text-right">{{ number_format($percTotal, 2, ',', '.') }}%</td>
                            <td colspan="3"></td>
                        </tr>
                        <tr>
                            <td colspan="4" class="px-4 py-2 text-right">Total de Descontos:</td>
                            <td class="px-4 py-2 text-right">R$ {{ number_format($totalDescontos, 2, ',', '.') }}</td>
                            <td colspan="3"></td>
                        </tr>
                        <tr>
                            <td colspan="4" class="px-4 py-2 text-right">Valor com Desconto:</td>
                            <td class="px-4 py-2 text-right">R$ {{ number_format($valorTotal, 2, ',', '.') }}</td>
                            <td colspan="3"></td>
                        </tr>
                        <tr>
                            <td colspan="4" class="px-4 py-2 text-right">Peso Total:</td>
                            <td class="px-4 py-2 text-right">{{ number_format($pesoTotal, 2, ',', '.') }} kg</td>
                            <td colspan="3"></td>
                        </tr>
                        <tr>
                            <td colspan="4" class="px-4 py-2 text-right">Total de Caixas:</td>
                            <td class="px-4 py-2 text-right">{{ $totalCaixas }}</td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- Observações --}}
        @php $obs = trim((string) $pedido->observacoes); @endphp
        @if($obs !== '')
            <div class="bg-white p-6 rounded-xl shadow-sm ring-1 ring-gray-100">
                <h3 class="text-lg font-semibold text-gray-800">Observações</h3>
                <div class="mt-2 text-gray-800 whitespace-pre-line">
                    {{ $obs }}
                </div>
            </div>
        @endif

        {{-- Linha do tempo --}}
        <div class="bg-white p-6 rounded-xl shadow-sm ring-1 ring-gray-100">
            <h3 class="text-lg font-semibold mb-4 text-gray-800">Linha do Tempo</h3>

            <ul class="space-y-4">
                @forelse($pedido->logs as $log)
                    <li class="relative pl-6">
                        <span class="absolute left-0 top-2 h-3.5 w-3.5 bg-blue-600 rounded-full"></span>

                        <div class="text-xs text-gray-500">
                            {{ optional($log->created_at)->format('d/m/Y H:i') }}
                        </div>

                        <div class="font-semibold text-gray-800">
                            {{ $log->acao }}
                        </div>

                        @php
                            $linhas = [];
                            if (is_string($log->detalhes) && strlen($log->detalhes)) {
                                $linhas = explode(' | ', $log->detalhes);
                            } elseif (is_array($log->detalhes)) {
                                $linhas = array_map(
                                    fn($x) => is_string($x) ? $x : json_encode($x, JSON_UNESCAPED_UNICODE),
                                    $log->detalhes
                                );
                            }
                        @endphp

                        @if(!empty($linhas))
                            <div class="mt-1 space-y-1">
                                @foreach($linhas as $linha)
                                    <div class="text-sm text-gray-700">{{ $linha }}</div>
                                @endforeach
                            </div>
                        @endif

                        @if($log->user)
                            <div class="text-xs text-gray-400 mt-1">Por: {{ $log->user->name }}</div>
                        @endif
                    </li>
                @empty
                    <li class="text-gray-500">Nenhum registro até o momento.</li>
                @endforelse
            </ul>
        </div>

    </div>
</x-app-layout>
