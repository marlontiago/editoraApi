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

        {{-- Informações principais --}}
        <div class="bg-white p-6 rounded-lg shadow border border-gray-100">
            <h3 class="text-lg font-semibold mb-4 text-gray-700">Informações Gerais</h3>

            @php
                $status = $pedido->status;
                $statusMap = [
                    'em_andamento' => ['Em andamento', 'bg-yellow-100 text-yellow-800 border-yellow-200'],
                    'finalizado'   => ['Finalizado',   'bg-green-100  text-green-800  border-green-200'],
                    'cancelado'    => ['Cancelado',    'bg-red-100    text-red-800    border-red-200'],
                ];
                [$statusLabel, $statusClasses] = $statusMap[$status] ?? [ucfirst(str_replace('_',' ',$status)), 'bg-gray-100 text-gray-800 border-gray-200'];
            @endphp

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
                </div>

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
                                <td class="px-4 py-2">{{ $produto->nome }}</td>
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

                        // Percentual total ponderado pelo valor bruto dos itens
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

            {{-- RESUMO: Produtos com desconto --}}
            @php
                $produtosComDesc = $pedido->produtos->filter(function ($p) {
                    $q  = (int) ($p->pivot->quantidade ?? 0);
                    $pu = (float) ($p->pivot->preco_unitario ?? 0);
                    $bruto = $pu * $q;
                    $sub   = (float) ($p->pivot->subtotal ?? 0);
                    return ($bruto - $sub) > 0.00001;
                });
            @endphp

            @if($produtosComDesc->count())
                <div class="mt-4 rounded-md border border-green-200 bg-green-50 p-4">
                    <h4 class="text-sm font-semibold text-green-800 mb-2">Produtos com desconto</h4>
                    <ul class="list-disc pl-5 space-y-1 text-sm text-green-900">
                        @foreach($produtosComDesc as $p)
                            @php
                                $q  = (int) ($p->pivot->quantidade ?? 0);
                                $pu = (float) ($p->pivot->preco_unitario ?? 0);
                                $bruto = $pu * $q;
                                $sub   = (float) ($p->pivot->subtotal ?? 0);
                                $val   = max(0, $bruto - $sub);
                                $pct   = $bruto > 0 ? ($val / $bruto) * 100 : 0;
                            @endphp
                            <li>
                                <span class="font-medium">{{ $p->nome }}</span> — desconto de
                                <strong>R$ {{ number_format($val, 2, ',', '.') }}</strong>
                                ({{ number_format($pct, 2, ',', '.') }}%)
                            </li>
                        @endforeach
                    </ul>
                    <p class="px-1 pt-2 text-xs text-gray-500">
                        Obs.: o percentual de desconto do pedido é ponderado pelo valor bruto de cada item:
                        Σ(Brutoᵢ × %Descᵢ) ÷ Σ(Brutoᵢ).
                    </p>
                </div>
            @endif
        </div>

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
                            // $log->detalhes pode ser string ou array/JSON dependendo da sua implementação
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

        {{-- Ações --}}
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('admin.pedidos.index') }}"
               class="inline-flex items-center px-4 py-2 rounded-md border border-gray-300 text-gray-700 hover:bg-black hover:text-white">
                ← Voltar
            </a>

            <a href="{{ route('admin.pedidos.edit', $pedido) }}"
               class="inline-flex items-center px-4 py-2 rounded-md bg-yellow-500 text-white hover:bg-yellow-600">
                Editar
            </a>

            <a href="{{ route('admin.pedidos.exportar', ['pedido' => $pedido->id, 'tipo' => 'relatorio']) }}"
               class="inline-flex items-center px-4 py-2 rounded-md bg-gray-700 text-white hover:bg-gray-800">
                Exportar Relatório
            </a>

            <a href="{{ route('admin.pedidos.exportar', ['pedido' => $pedido->id, 'tipo' => 'orcamento']) }}"
               class="inline-flex items-center px-4 py-2 rounded-md bg-green-600 text-white hover:bg-green-700">
                Exportar Orçamento
            </a>
        </div>

    </div>
</x-app-layout>
