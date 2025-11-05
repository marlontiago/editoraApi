<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-gray-800">
            Detalhes do Pedido #{{ $pedido->id }}
        </h2>
    </x-slot>

    <div class="p-6 space-y-8 max-w-7xl mx-auto">

        {{-- Mensagens de feedback --}}
        @if (session('error'))
            <div class="bg-red-50 text-red-800 border border-red-200 px-4 py-3 rounded-md shadow-sm">
                {{ session('error') }}
            </div>
        @endif
        @if (session('success'))
            <div class="bg-green-50 text-green-800 border border-green-200 px-4 py-3 rounded-md shadow-sm">
                {{ session('success') }}
            </div>
        @endif

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

            // Defaults para evitar "Undefined variable"
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
        @endphp

        {{-- Ações --}}
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('admin.pedidos.index') }}"
               class="inline-flex items-center px-4 py-2 rounded-md border border-gray-300 text-gray-700 hover:bg-black hover:text-white">
                ← Voltar
            </a>

            @if($pedido->status !== 'cancelado' && $pedido->status !== 'finalizado')
                <a href="{{ route('admin.pedidos.edit', $pedido) }}"
                class="inline-flex items-center px-4 py-2 rounded-md bg-yellow-500 text-white hover:bg-yellow-600">
                    Editar
                </a>
            @endif

            <a href="{{ route('admin.pedidos.exportar', ['pedido' => $pedido->id, 'tipo' => 'relatorio']) }}"
               class="inline-flex items-center px-4 py-2 rounded-md bg-gray-700 text-white hover:bg-gray-800">
                Exportar Relatório
            </a>

            <a href="{{ route('admin.pedidos.exportar', ['pedido' => $pedido->id, 'tipo' => 'orcamento']) }}"
               class="inline-flex items-center px-4 py-2 rounded-md bg-green-600 text-white hover:bg-green-700">
                Exportar Orçamento
            </a>
            @if($notaAtual)
    <a href="{{ route('admin.notas.show', ['nota' => $notaAtual]) }}"
       class="inline-flex items-center px-4 py-2 rounded-md bg-blue-500 text-white hover:bg-blue-600">
        Ver Nota
    </a>
@endif

            {{-- Botões de Nota Fiscal --}}
@if($pedido->status !== 'cancelado')
    @if(!empty($temNotaFaturada) && $temNotaFaturada)
        {{-- Já faturada: não mostrar mais "Emitir" --}}
        <span class="inline-flex items-center px-3 py-2 rounded-md bg-green-100 text-green-800 border border-green-200">
            Nota faturada
        </span>

    @elseif(!empty($notaEmitida))
        {{-- Existe nota emitida (ainda não faturada) --}}
        {{-- ===== Faturar com Modal de Tipo ===== --}}
<div x-data="{ openFat:false, modo:'normal' }">
    <button type="button"
            @click="openFat = true; modo = 'normal';"
            class="inline-flex items-center px-4 py-2 rounded-md bg-emerald-600 text-white hover:bg-emerald-700">
        Faturar Nota
    </button>

    {{-- Modal --}}
    <div x-show="openFat" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4">
        {{-- backdrop --}}
        <div class="absolute inset-0 bg-black/40" @click="openFat=false"></div>

        {{-- card --}}
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
                            <div class="text-sm text-gray-600">Baixa o estoque e define financeiro para <strong>aguardando_pagamento</strong> (fluxo atual).</div>
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
                            class="px-4 py-2 rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50"
                            @click="openFat=false">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="px-4 py-2 rounded-md bg-emerald-600 text-white hover:bg-emerald-700">
                        Confirmar faturamento
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

        <form action="{{ route('admin.pedidos.emitir-nota', $pedido) }}" method="POST"
              onsubmit="return confirm('Emitir NOVA nota com os dados atuais do pedido? A nota emitida será cancelada.');">
            @csrf
            <input type="hidden" name="substituir" value="1">
            <button class="inline-flex items-center px-4 py-2 rounded-md bg-indigo-600 text-white hover:bg-indigo-700">
                Nova Pré Visualização de Nota
            </button>
        </form>

    @else
        {{-- Ainda não existe nota --}}
        <form action="{{ route('admin.pedidos.emitir-nota', $pedido) }}" method="POST"
              onsubmit="return confirm('Emitir nota interna para este pedido?');">
            @csrf
            <button class="inline-flex items-center px-4 py-2 rounded-md bg-indigo-600 text-white hover:bg-indigo-700">
               Pré Visualização de Nota
            </button>
        </form>
    @endif
@endif
</div>


        {{-- Informações principais --}}
        <div class="bg-white p-6 rounded-lg shadow border border-gray-100">
            <h3 class="text-lg font-semibold mb-4 text-gray-700">Informações Gerais</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-700">
                <div>
                    <strong>Data:</strong>
                    {{ \Carbon\Carbon::parse($pedido->data)->format('d/m/Y') }}
                </div>

                <div class="flex items-center gap-2">
                    <strong>Status:</strong>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full border {{ $statusClasses }}">
                        {{ $statusLabel }}
                    </span>

                    {{-- novo: badge financeiro ao lado do status, quando houver nota e status_financeiro --}}
                    @if(!empty($notaAtual) && !empty($notaAtual->status_financeiro))
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full border {{ $notaFinClasses }}">
                            {{ $notaFinLabel }}
                        </span>
                    @endif
                </div>

                {{-- Nota Fiscal (mostra a mais recente se existir) --}}
                @if(!empty($notaAtual))
                    <div class="md:col-span-2">
                        <strong>Nota Fiscal:</strong>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full border {{ $notaClasses }}">
                            {{ $notaLabel }}
                        </span>
                        <span class="ml-2 text-gray-700">
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
                @endif

                <div class="md:col-span-2">
                    <strong>Cidades:</strong>
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

                <div>
                    <strong>Cliente:</strong>
                    {{ optional($pedido->cliente)->razao_social ?? optional($pedido->cliente)->nome ?? '-' }}
                </div>

                <div>
                    <strong>Gestor:</strong>
                    @if($pedido->gestor)
                        {{ $pedido->gestor->razao_social }}
                        @if($pedido->gestor->estado_uf)
                            <span class="text-xs text-gray-500">/ UF: {{ strtoupper($pedido->gestor->estado_uf) }}</span>
                        @endif
                    @else
                        -
                    @endif
                </div>

                <div>
                    <strong>Distribuidor:</strong>
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
        </div>

        {{-- Produtos --}}
        <div class="bg-white p-6 rounded-lg shadow border border-gray-100">
            <h3 class="text-lg font-semibold mb-4 text-gray-700">Produtos do Pedido</h3>

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
                                <td class="px-4 py-2">{{ $produto->titulo }}</td>
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

                    <tfoot class="bg-gray-50 font-medium">
                        <tr>
                            <td colspan="4" class="px-4 py-2 text-right">Valor Bruto:</td>
                            <td class="px-4 py-2 text-right">R$ {{ number_format($valorBruto, 2, ',', '.') }}</td>
                            <td colspan="2"></td>
                        </tr>
                        <tr>
                            <td colspan="4" class="px-4 py-2 text-right">Percentual de Desconto:</td>
                            <td class="px-4 py-2 text-right">{{ number_format($percTotal, 2, ',', '.') }}%</td>
                            <td colspan="2"></td>
                        </tr>
                        <tr>
                            <td colspan="4" class="px-4 py-2 text-right">Total de Descontos:</td>
                            <td class="px-4 py-2 text-right">R$ {{ number_format($totalDescontos, 2, ',', '.') }}</td>
                            <td colspan="2"></td>
                        </tr>
                        <tr>
                            <td colspan="4" class="px-4 py-2 text-right">Valor com Desconto:</td>
                            <td class="px-4 py-2 text-right">R$ {{ number_format($valorTotal, 2, ',', '.') }}</td>
                            <td colspan="2"></td>
                        </tr>
                        <tr>
                            <td colspan="4" class="px-4 py-2 text-right">Peso Total:</td>
                            <td class="px-4 py-2 text-right">{{ number_format($pesoTotal, 2, ',', '.') }} kg</td>
                            <td colspan="2"></td>
                        </tr>
                        <tr>
                            <td colspan="4" class="px-4 py-2 text-right">Total de Caixas:</td>
                            <td class="px-4 py-2 text-right">{{ $totalCaixas }}</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- Observações (aparece se houver) --}}
                @php $obs = trim((string) $pedido->observacoes); @endphp
                @if($obs !== '')
                    <div class="bg-white p-6 rounded-lg shadow border border-gray-100">
                        <strong>Observações</strong>
                        <div class="mt-1 text-gray-800 whitespace-pre-line">
                            # {{ $obs }}
                        </div>
                    </div>
                @endif

        {{-- Linha do tempo --}}
        <div class="bg-white p-6 rounded-lg shadow border border-gray-100">
            <h3 class="text-lg font-semibold mb-4 text-gray-700">Linha do Tempo</h3>
            <ul class="space-y-4">
                @forelse($pedido->logs as $log)
                    <li class="relative pl-6">
                        <span class="absolute left-0 top-2 h-4 w-4 bg-blue-500 rounded-full"></span>
                        <div class="text-xs text-gray-500">{{ optional($log->created_at)->format('d/m/Y H:i') }}</div>
                        <div class="font-medium">{{ $log->acao }}</div>

                        @php
                            $linhas = [];
                            if (is_string($log->detalhes) && strlen($log->detalhes)) {
                                $linhas = explode(' | ', $log->detalhes);
                            } elseif (is_array($log->detalhes)) {
                                $linhas = array_map(fn($x) => is_string($x) ? $x : json_encode($x, JSON_UNESCAPED_UNICODE), $log->detalhes);
                            }
                        @endphp

                        @if(!empty($linhas))
                            @foreach($linhas as $linha)
                                <div class="text-sm text-gray-700">{{ $linha }}</div>
                            @endforeach
                        @endif

                        @if($log->user)
                            <div class="text-xs text-gray-400">Por: {{ $log->user->name }}</div>
                        @endif
                    </li>
                @empty
                    <li class="text-gray-500">Nenhum registro até o momento.</li>
                @endforelse
            </ul>
        </div>

    </div>
</x-app-layout>
