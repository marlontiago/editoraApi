<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-gray-800">
            Nota #{{ $nota->numero ?? $nota->id }} — {{ strtoupper($nota->status) }}
        </h2>
    </x-slot>

    <div class="p-6 space-y-6 max-w-5xl mx-auto">
        @if (session('success'))
            <div class="bg-green-50 text-green-800 border border-green-200 px-4 py-3 rounded-md">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="bg-red-50 text-red-800 border border-red-200 px-4 py-3 rounded-md">
                {{ session('error') }}
            </div>
        @endif

        {{-- Ações topo --}}
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('admin.pedidos.show', $nota->pedido_id) }}"
               class="inline-flex items-center px-4 py-2 rounded-md border border-gray-300 text-gray-700 hover:bg-black hover:text-white">
                ← Voltar ao Pedido
            </a>

            <a href="{{ route('admin.notas.pdf', $nota) }}" target="_blank"
               class="inline-flex items-center px-4 py-2 rounded-md bg-red-600 text-white hover:bg-red-700">
                📄 Exportar PDF
            </a>

            @if ($nota->status === 'faturada')
                <a href="{{ route('admin.notas.pagamentos.create', $nota) }}"
                class="inline-flex items-center px-3 py-2 rounded-md bg-emerald-600 text-white hover:bg-emerald-700">
                    Registrar Pagamento
                </a>
            @endif

            {{-- Botão para ver detalhes do pagamento (se existir) --}}
            @if(!empty($pagamentoAtual))
                <a href="{{ route('admin.notas.pagamentos.show', [$nota, $pagamentoAtual]) }}"
                class="inline-flex items-center px-4 py-2 rounded-md bg-indigo-600 text-white hover:bg-indigo-700">
                    Detalhes do Pagamento
                </a>
            @endif

            {{-- Faturar --}}

            @if($nota->status === 'emitida')
                <form action="{{ route('admin.notas.faturar', $nota) }}" method="POST"
                      onsubmit="return confirm('Faturar nota? Isto baixará o estoque.');">
                    @csrf
                    <button class="inline-flex items-center px-4 py-2 rounded-md bg-emerald-600 text-white hover:bg-emerald-700">
                        Faturar Nota
                    </button>
                </form>
            @elseif($nota->status === 'faturada')
                <span class="inline-flex items-center px-3 py-1 rounded-full border bg-emerald-50 text-emerald-800 border-emerald-200">
                    Nota Faturada
                </span>
            @endif
        </div>

        {{-- Cabeçalho --}}
        <div class="bg-white p-6 rounded-lg shadow border">
            <h3 class="text-lg font-semibold mb-4">Cabeçalho</h3>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div><strong>Número:</strong> {{ $nota->numero ?? '-' }}</div>
                <div><strong>Série:</strong> {{ $nota->serie ?? '-' }}</div>

                <div><strong>Status:</strong> {{ ucfirst($nota->status) }}</div>
                <div><strong>Ambiente:</strong> {{ strtoupper($nota->ambiente ?? 'INTERNO') }}</div>

                <div><strong>Emitida em:</strong> {{ optional($nota->emitida_em)->format('d/m/Y H:i') }}</div>
                <div><strong>Faturada em:</strong> {{ optional($nota->faturada_em)->format('d/m/Y H:i') ?? '-' }}</div>

                <div><strong>Pedido:</strong>
                    <a class="underline text-blue-600" href="{{ route('admin.pedidos.show', $nota->pedido_id) }}">
                        #{{ $nota->pedido_id }}
                    </a>
                </div>
                <div><strong>Cliente:</strong> {{ $nota->pedido?->cliente?->razao_social ?? '-' }}</div>
            </div>
        </div>

        {{-- Itens --}}
        <div class="bg-white p-6 rounded-lg shadow border">
            <h3 class="text-lg font-semibold mb-4">Itens</h3>
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left border-b bg-gray-50 text-gray-700">
                        <th class="py-2 px-2">Produto</th>
                        <th class="py-2 px-2 text-right">Qtd</th>
                        <th class="py-2 px-2 text-right">Unitário</th>
                        <th class="py-2 px-2 text-right">Desc. %</th>
                        <th class="py-2 px-2 text-right">Subtotal</th>
                        <th class="py-2 px-2 text-right">Peso (kg)</th>
                        <th class="py-2 px-2 text-center">Caixas</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($nota->itens as $item)
                    <tr class="border-b">
                        <td class="py-2 px-2">{{ $item->descricao_produto ?? $item->produto?->nome }}</td>
                        <td class="py-2 px-2 text-right">{{ $item->quantidade }}</td>
                        <td class="py-2 px-2 text-right">R$ {{ number_format($item->preco_unitario, 2, ',', '.') }}</td>
                        <td class="py-2 px-2 text-right">{{ number_format($item->desconto_aplicado, 2, ',', '.') }}%</td>
                        <td class="py-2 px-2 text-right">R$ {{ number_format($item->subtotal, 2, ',', '.') }}</td>
                        <td class="py-2 px-2 text-right">{{ number_format($item->peso_total_produto, 3, ',', '.') }}</td>
                        <td class="py-2 px-2 text-center">{{ $item->caixas }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        {{-- Totais --}}
        <div class="bg-white p-6 rounded-lg shadow border">
            <h3 class="text-lg font-semibold mb-4">Totais</h3>
            <div class="grid grid-cols-3 gap-4 text-sm">
                <div><strong>Valor bruto:</strong> R$ {{ number_format($nota->valor_bruto, 2, ',', '.') }}</div>
                <div><strong>Desconto total:</strong> R$ {{ number_format($nota->desconto_total, 2, ',', '.') }}</div>
                <div><strong>Valor total:</strong> <span class="font-semibold">R$ {{ number_format($nota->valor_total, 2, ',', '.') }}</span></div>
                <div><strong>Peso total:</strong> {{ number_format($nota->peso_total, 3, ',', '.') }} kg</div>
                <div><strong>Total de caixas:</strong> {{ $nota->total_caixas }}</div>
            </div>
        </div>

        {{-- Observações --}}
        <div class="bg-white p-6 rounded-lg shadow border">
            <h3 class="text-lg font-semibold mb-2">Observações</h3>
            @php
                $obs = $nota->observacoes
                    ?? ($nota->pedido_snapshot['observacoes'] ?? null)
                    ?? ($nota->pedido?->observacoes ?? null);
            @endphp
            <div class="text-sm text-gray-800 whitespace-pre-wrap">
                {{ $obs ?: '—' }}
            </div>
        </div>

        @if($nota->pagamentos->count())
            <div class="mt-6">
                <h4 class="font-semibold text-gray-800 mb-2">Pagamentos</h4>
                <div class="overflow-x-auto rounded border">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left">Data</th>
                                <th class="px-3 py-2 text-right">Valor Pago</th>
                                <th class="px-3 py-2 text-right">Retenções</th>
                                <th class="px-3 py-2 text-right">Líquido</th>
                                <th class="px-3 py-2 text-right">Com. Adv.</th>
                                <th class="px-3 py-2 text-right">Com. Dir.</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($nota->pagamentos as $pg)
                            <tr class="border-t">
                                <td class="px-3 py-2">{{ optional($pg->data_pagamento)->format('d/m/Y') ?: '-' }}</td>
                                <td class="px-3 py-2 text-right">R$ {{ number_format($pg->valor_pago, 2, ',', '.') }}</td>
                                <td class="px-3 py-2 text-right">
                                    R$ {{ number_format($pg->total_retencoes, 2, ',', '.') }}
                                </td>
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
