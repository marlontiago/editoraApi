<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-semibold text-gray-800">
            Pagamento da Nota #{{ $nota->id }} — Recibo #{{ $pagamento->id }}
        </h2>
    </x-slot>

    <div class="max-w-7xl mx-auto p-6 space-y-8">

        @if (session('success'))
            <div class="rounded-md border border-green-300 bg-green-50 p-4 text-green-800">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="rounded-md border border-red-300 bg-red-50 p-4 text-red-800">
                {{ session('error') }}
            </div>
        @endif

        {{-- Ações --}}
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('admin.notas.show', $nota) }}"
               class="inline-flex items-center px-4 py-2 rounded-md border border-gray-300 text-gray-700 hover:bg-black hover:text-white">
                ← Voltar para a Nota
            </a>
        </div>

        {{-- Resumo do Pedido / Nota --}}
        <div class="bg-white p-6 rounded-lg shadow border border-gray-100">
            <h3 class="text-lg font-semibold mb-4 text-gray-700">Resumo do Pedido / Nota</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-800">
                <div>
                    <strong>Cliente:</strong>
                    {{ optional($pedido->cliente)->razao_social ?? optional($pedido->cliente)->nome ?? '-' }}
                </div>
                <div>
                    <strong>Data Pagamento:</strong>
                    {{ optional($pagamento->data_pagamento)->format('d/m/Y') ?? '-' }}
                </div>
                <div>
                    <strong>Gestor:</strong>
                    {{ optional($pedido->gestor)->razao_social ?? '-' }}
                    @if($percGestor>0) <span class="text-xs text-gray-500">({{ number_format($percGestor,2,',','.') }}%)</span> @endif
                </div>
                <div>
                    <strong>Distribuidor:</strong>
                    {{ optional($pedido->distribuidor)->razao_social ?? '-' }}
                    @if($percDistribuidor>0) <span class="text-xs text-gray-500">({{ number_format($percDistribuidor,2,',','.') }}%)</span> @endif
                </div>
                <div class="md:col-span-2">
                    <strong>Cidade:</strong>
                    @forelse($pedido->cidades as $cidade)
                        <span class="inline-flex items-center gap-1 bg-gray-100 text-gray-700 text-xs px-2 py-1 rounded-full mr-1 mb-1 border border-gray-200">
                            {{ $cidade->name }}@if($cidade->state)<span class="opacity-70">/{{ strtoupper($cidade->state) }}</span>@endif
                        </span>
                    @empty
                        <span class="text-gray-500">-</span>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Produtos do Pedido --}}
        <div class="bg-white p-6 rounded-lg shadow border border-gray-100">
            <h3 class="text-lg font-semibold mb-4 text-gray-700">Produtos</h3>

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
                        @foreach ($pedido->produtos as $p)
                            @php
                                $qtd        = (int) ($p->pivot->quantidade ?? 0);
                                $unit       = (float) ($p->pivot->preco_unitario ?? 0);
                                $brutoItem  = $qtd * $unit;
                                $subtotal   = (float) ($p->pivot->subtotal ?? 0);
                                $descValor  = max(0, $brutoItem - $subtotal);
                                $descPerc   = $brutoItem > 0 ? ($descValor / $brutoItem) * 100 : 0;
                                $peso       = (float) ($p->pivot->peso_total_produto ?? 0);
                                $caixas     = (int) ($p->pivot->caixas ?? 0);
                            @endphp
                            <tr class="border-t">
                                <td class="px-4 py-2">{{ $p->nome }}</td>
                                <td class="px-4 py-2 text-center">{{ $qtd }}</td>
                                <td class="px-4 py-2 text-right">R$ {{ number_format($unit, 2, ',', '.') }}</td>
                                <td class="px-4 py-2 text-right">{{ number_format($descPerc, 2, ',', '.') }}%</td>
                                <td class="px-4 py-2 text-right">R$ {{ number_format($descValor, 2, ',', '.') }}</td>
                                <td class="px-4 py-2 text-right">R$ {{ number_format($subtotal, 2, ',', '.') }}</td>
                                <td class="px-4 py-2 text-right">{{ number_format($peso, 2, ',', '.') }}</td>
                                <td class="px-4 py-2 text-center">{{ $caixas }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50 font-medium">
                        <tr>
                            <td colspan="4" class="px-4 py-2 text-right">Valor Bruto dos Produtos:</td>
                            <td class="px-4 py-2 text-right">R$ {{ number_format($valorBrutoPedido, 2, ',', '.') }}</td>
                            <td colspan="3"></td>
                        </tr>
                        <tr>
                            <td colspan="4" class="px-4 py-2 text-right">Total de Descontos:</td>
                            <td class="px-4 py-2 text-right">R$ {{ number_format($totalDescontosPedido, 2, ',', '.') }}</td>
                            <td colspan="3"></td>
                        </tr>
                        <tr>
                            <td colspan="4" class="px-4 py-2 text-right">Valor com Desconto:</td>
                            <td class="px-4 py-2 text-right">R$ {{ number_format($valorComDescontoPedido, 2, ',', '.') }}</td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- Pagamento / Retenções --}}
        <div class="bg-white p-6 rounded-lg shadow border border-gray-100">
            <h3 class="text-lg font-semibold mb-4 text-gray-700">Pagamento e Retenções</h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <div>
                    <div class="text-gray-600">Valor Pago</div>
                    <div class="text-lg font-semibold">R$ {{ number_format($valorPago, 2, ',', '.') }}</div>
                </div>
                <div>
                    <div class="text-gray-600">Total de Retenções</div>
                    <div class="text-lg font-semibold">R$ {{ number_format($totalRetencoes, 2, ',', '.') }}</div>
                </div>
                <div>
                    <div class="text-gray-600">Valor Líquido</div>
                    <div class="text-lg font-semibold text-emerald-700">R$ {{ number_format($valorLiquido, 2, ',', '.') }}</div>
                </div>
            </div>

            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full border border-gray-200 rounded-lg overflow-hidden text-sm">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="px-4 py-2 text-left">Imposto</th>
                            <th class="px-4 py-2 text-right">Base</th>
                            <th class="px-4 py-2 text-right">Alíquota (%)</th>
                            <th class="px-4 py-2 text-right">Valor Retido (R$)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $linhas = [
                                ['IRRF',   $ret['irrf'],   $retValores['irrf']],
                                ['ISS',    $ret['iss'],    $retValores['iss']],
                                ['INSS',   $ret['inss'],   $retValores['inss']],
                                ['PIS',    $ret['pis'],    $retValores['pis']],
                                ['COFINS', $ret['cofins'], $retValores['cofins']],
                                ['CSLL',   $ret['csll'],   $retValores['csll']],
                                ['Outros', $ret['outros'], $retValores['outros']],
                            ];
                        @endphp
                        @foreach ($linhas as [$nome, $perc, $valor])
                            <tr class="border-t">
                                <td class="px-4 py-2">{{ $nome }}</td>
                                <td class="px-4 py-2 text-right">R$ {{ number_format($valorPago, 2, ',', '.') }}</td>
                                <td class="px-4 py-2 text-right">{{ number_format($perc, 2, ',', '.') }}%</td>
                                <td class="px-4 py-2 text-right">R$ {{ number_format($valor, 2, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50 font-medium">
                        <tr>
                            <td colspan="3" class="px-4 py-2 text-right">Total de Retenções:</td>
                            <td class="px-4 py-2 text-right">R$ {{ number_format($totalRetencoes, 2, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            @if(!empty($pagamento->observacoes))
                <div class="mt-4">
                    <div class="text-sm text-gray-600 font-medium">Observações</div>
                    <div class="mt-1 text-gray-800 whitespace-pre-line">
                        {{ $pagamento->observacoes }}
                    </div>
                </div>
            @endif
        </div>

        {{-- Comissões --}}
        <div class="bg-white p-6 rounded-lg shadow border border-gray-100">
            <h3 class="text-lg font-semibold mb-4 text-gray-700">Comissões sobre o Líquido</h3>

            <div class="overflow-x-auto">
                <table class="min-w-full border border-gray-200 rounded-lg overflow-hidden text-sm">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="px-4 py-2 text-left">Parte</th>
                            <th class="px-4 py-2 text-right">Percentual</th>
                            <th class="px-4 py-2 text-right">Valor (R$)</th>
                            <th class="px-4 py-2 text-left">Pessoa</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="border-t">
                            <td class="px-4 py-2">Gestor</td>
                            <td class="px-4 py-2 text-right">{{ number_format($percGestor, 2, ',', '.') }}%</td>
                            <td class="px-4 py-2 text-right">R$ {{ number_format($comissaoGestor, 2, ',', '.') }}</td>
                            <td class="px-4 py-2">{{ optional($pedido->gestor)->razao_social ?? '-' }}</td>
                        </tr>
                        <tr class="border-t">
                            <td class="px-4 py-2">Distribuidor</td>
                            <td class="px-4 py-2 text-right">{{ number_format($percDistribuidor, 2, ',', '.') }}%</td>
                            <td class="px-4 py-2 text-right">R$ {{ number_format($comissaoDistribuidor, 2, ',', '.') }}</td>
                            <td class="px-4 py-2">{{ optional($pedido->distribuidor)->razao_social ?? '-' }}</td>
                        </tr>
                        <tr class="border-t">
                            <td class="px-4 py-2">Advogado</td>
                            <td class="px-4 py-2 text-right">{{ number_format($percAdv, 2, ',', '.') }}%</td>
                            <td class="px-4 py-2 text-right">R$ {{ number_format($comissaoAdv, 2, ',', '.') }}</td>
                            <td class="px-4 py-2">{{ optional($pagamento->advogado)->name ?? '-' }}</td>
                        </tr>
                        <tr class="border-t">
                            <td class="px-4 py-2">Diretor Comercial</td>
                            <td class="px-4 py-2 text-right">{{ number_format($percDir, 2, ',', '.') }}%</td>
                            <td class="px-4 py-2 text-right">R$ {{ number_format($comissaoDir, 2, ',', '.') }}</td>
                            <td class="px-4 py-2">{{ optional($pagamento->diretor)->name ?? '-' }}</td>
                        </tr>
                    </tbody>
                    <tfoot class="bg-gray-50 font-medium">
                        @php
                            $somaComissoes = $comissaoGestor + $comissaoDistribuidor + $comissaoAdv + $comissaoDir;
                        @endphp
                        <tr>
                            <td colspan="2" class="px-4 py-2 text-right">Total de Comissões:</td>
                            <td class="px-4 py-2 text-right">R$ {{ number_format($somaComissoes, 2, ',', '.') }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

    </div>
</x-app-layout>
