<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">
            Registrar Pagamento — Nota #{{ $nota->id }}
        </h2>
    </x-slot>

    <div class="max-w-4xl mx-auto p-6 space-y-6">
        @if ($errors->any())
            <div class="rounded-md border border-red-300 bg-red-50 p-4 text-red-800">
                <ul class="list-disc pl-5 space-y-1">
                    @foreach($errors->all() as $e)
                        <li class="text-sm">{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.notas.pagamentos.store', $nota) }}" class="bg-white shadow rounded-lg p-6 space-y-5" id="formPagamento">
            @csrf

            {{-- Dados principais --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Data do Pagamento</label>
                    <input
                        type="date"
                        name="data_pagamento"
                        value="{{ old('data_pagamento') }}"
                        class="mt-1 w-full border rounded px-3 py-2"
                    >
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Valor Pago (base p/ retenções)</label>
                    <input
                        type="number" step="0.01" min="0"
                        name="valor_pago"
                        value="{{ old('valor_pago') }}"
                        class="mt-1 w-full border rounded px-3 py-2"
                        required
                    >
                </div>
            </div>

            {{-- Retenções (em %) --}}
            <h3 class="text-md font-semibold mt-2">Retenções</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">IRRF (%)</label>
                    <input type="number" step="0.01" min="0" max="100" name="ret_irrf" value="{{ old('ret_irrf') }}" class="mt-1 w-full border rounded px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">ISS (%)</label>
                    <input type="number" step="0.01" min="0" max="100" name="ret_iss" value="{{ old('ret_iss') }}" class="mt-1 w-full border rounded px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">INSS (%)</label>
                    <input type="number" step="0.01" min="0" max="100" name="ret_inss" value="{{ old('ret_inss') }}" class="mt-1 w-full border rounded px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">PIS (%)</label>
                    <input type="number" step="0.01" min="0" max="100" name="ret_pis" value="{{ old('ret_pis') }}" class="mt-1 w-full border rounded px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">COFINS (%)</label>
                    <input type="number" step="0.01" min="0" max="100" name="ret_cofins" value="{{ old('ret_cofins') }}" class="mt-1 w-full border rounded px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">CSLL (%)</label>
                    <input type="number" step="0.01" min="0" max="100" name="ret_csll" value="{{ old('ret_csll') }}" class="mt-1 w-full border rounded px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Outros (%)</label>
                    <input type="number" step="0.01" min="0" max="100" name="ret_outros" value="{{ old('ret_outros') }}" class="mt-1 w-full border rounded px-3 py-2">
                </div>
            </div>

            {{-- Adesão à ata --}}
            <div class="border-t pt-4">
                <label class="inline-flex items-center gap-2">
                    <input
                        type="checkbox"
                        name="adesao_ata"
                        value="1"
                        id="chkAdesao"
                        @checked(old('adesao_ata'))
                    >
                    <span class="text-sm font-medium text-gray-700">Adesão à ata</span>
                </label>

                <div id="boxAdesao" class="mt-3 grid grid-cols-1 md:grid-cols-3 gap-4 {{ old('adesao_ata') ? '' : 'hidden' }}">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Advogado</label>
                        <select name="advogado_id" class="mt-1 w-full border rounded px-3 py-2">
                            <option value="">— Selecione —</option>
                            @foreach($advogados as $u)
                                <option value="{{ $u->id }}" @selected(old('advogado_id')==$u->id)>{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">% Comissão Advogado</label>
                        <input
                            type="number" step="0.01" min="0" max="100"
                            name="perc_comissao_advogado"
                            value="{{ old('perc_comissao_advogado') }}"
                            class="mt-1 w-full border rounded px-3 py-2"
                        >
                    </div>
                    <div></div>
                </div>
            </div>

            {{-- Diretor Comercial --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Diretor Comercial</label>
                    <select name="diretor_id" class="mt-1 w-full border rounded px-3 py-2">
                        <option value="">— Selecione —</option>
                        @foreach($diretores as $u)
                            <option value="{{ $u->id }}" @selected(old('diretor_id')==$u->id)>{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">% Comissão Diretor</label>
                    <input
                        type="number" step="0.01" min="0" max="100"
                        name="perc_comissao_diretor"
                        value="{{ old('perc_comissao_diretor') }}"
                        class="mt-1 w-full border rounded px-3 py-2"
                    >
                </div>
            </div>

            {{-- Observações --}}
            <div>
                <label class="block text-sm font-medium text-gray-700">Observações</label>
                <textarea
                    name="observacoes"
                    rows="3"
                    class="mt-1 w-full border rounded px-3 py-2"
                >{{ old('observacoes') }}</textarea>
            </div>

            {{-- Preview (JS) --}}
            <div class="bg-gray-50 border rounded p-4 text-sm space-y-1" id="previewBox">
                <div><strong>Total de Retenções:</strong> <span id="prevRet">R$ 0,00</span></div>
                <div><strong>Valor Líquido:</strong> <span id="prevLiq">R$ 0,00</span></div>

                <div class="pt-2"><strong>Comissões sobre o Líquido</strong></div>
                <div>
                    <span>Gestor ({{ number_format($percGestor ?? 0, 2, ',', '.') }}%):</span>
                    <span id="prevGest">R$ 0,00</span>
                </div>
                <div>
                    <span>Distribuidor ({{ number_format($percDistribuidor ?? 0, 2, ',', '.') }}%):</span>
                    <span id="prevDist">R$ 0,00</span>
                </div>
                <div>
                    <span>Advogado (<span id="lblPercAdv">{{ number_format(old('perc_comissao_advogado', 0), 2, ',', '.') }}</span>%):</span>
                    <span id="prevAdv">R$ 0,00</span>
                </div>
                <div>
                    <span>Diretor (<span id="lblPercDir">{{ number_format(old('perc_comissao_diretor', 0), 2, ',', '.') }}</span>%):</span>
                    <span id="prevDir">R$ 0,00</span>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <a href="{{ route('admin.notas.show', $nota) }}" class="px-4 py-2 rounded border hover:bg-gray-50">Cancelar</a>
                <button type="submit" class="px-4 py-2 rounded bg-emerald-600 text-white hover:bg-emerald-700">Salvar Pagamento</button>
            </div>
        </form>
    </div>

    {{-- Scripts da página --}}
    <script>
        // Percentuais automáticos vindos do controller
        const PERC_GESTOR       = Number(@json($percGestor ?? 0));
        const PERC_DISTRIBUIDOR = Number(@json($percDistribuidor ?? 0));

        const form       = document.getElementById('formPagamento');
        const chkAdesao  = document.getElementById('chkAdesao');
        const boxAdesao  = document.getElementById('boxAdesao');
        const lblPercAdv = document.getElementById('lblPercAdv');
        const lblPercDir = document.getElementById('lblPercDir');

        const money = (n) => {
            const v = isFinite(n) ? Number(n) : 0;
            return 'R$ ' + v.toFixed(2).replace('.', ',');
        };

        function val(name) {
            const el = form.querySelector(`[name="${name}"]`);
            if (!el) return 0;
            const raw = (el.value || '').toString().replace(',', '.').trim();
            return parseFloat(raw) || 0;
        }

        function recalc() {
            const valorPago = val('valor_pago');

            // Retenções informadas em % -> converte para R$
            const vIRRF   = valorPago * (val('ret_irrf')   / 100);
            const vISS    = valorPago * (val('ret_iss')    / 100);
            const vINSS   = valorPago * (val('ret_inss')   / 100);
            const vPIS    = valorPago * (val('ret_pis')    / 100);
            const vCOFINS = valorPago * (val('ret_cofins') / 100);
            const vCSLL   = valorPago * (val('ret_csll')   / 100);
            const vOUTROS = valorPago * (val('ret_outros') / 100);

            const totalRet = vIRRF + vISS + vINSS + vPIS + vCOFINS + vCSLL + vOUTROS;
            const liquido  = Math.max(0, valorPago - totalRet);

            // Comissões automáticas do pedido (gestor/distribuidor)
            const gest = liquido * (PERC_GESTOR / 100);
            const dist = liquido * (PERC_DISTRIBUIDOR / 100);

            // Comissões variáveis (formulário)
            let adv = 0;
            if (chkAdesao && chkAdesao.checked) {
                adv = liquido * (val('perc_comissao_advogado') / 100);
                if (lblPercAdv) lblPercAdv.textContent = (val('perc_comissao_advogado') || 0).toFixed(2).replace('.', ',');
            } else {
                if (lblPercAdv) lblPercAdv.textContent = (0).toFixed(2).replace('.', ',');
            }

            const dir = liquido * (val('perc_comissao_diretor') / 100);
            if (lblPercDir) lblPercDir.textContent = (val('perc_comissao_diretor') || 0).toFixed(2).replace('.', ',');

            // Atualiza preview
            document.getElementById('prevRet').textContent  = money(totalRet);
            document.getElementById('prevLiq').textContent  = money(liquido);
            document.getElementById('prevGest').textContent = money(gest);
            document.getElementById('prevDist').textContent = money(dist);
            document.getElementById('prevAdv').textContent  = money(adv);
            document.getElementById('prevDir').textContent  = money(dir);
        }

        chkAdesao?.addEventListener('change', () => {
            boxAdesao.classList.toggle('hidden', !chkAdesao.checked);
            recalc();
        });
        form.addEventListener('input', recalc);
        document.addEventListener('DOMContentLoaded', recalc);
    </script>
</x-app-layout>
