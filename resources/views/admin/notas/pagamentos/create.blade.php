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

        @php
            $valorBruto   = (float)($nota->pedido->valor_bruto ?? 0);
            $valorTotal   = (float)($nota->pedido->valor_total ?? 0);
            $jaPago       = (float) $nota->pagamentos()->sum('valor_pago'); // soma dos pagamentos (base)
            $faltaReceber = max(0, $valorTotal - $jaPago);
        @endphp

        <form method="POST" action="{{ route('admin.notas.pagamentos.store', $nota) }}" class="bg-white shadow rounded-lg p-6 space-y-5" id="formPagamento">
            @csrf

            {{-- Valores principais --}}
            <div class="space-y-1">
                <h1 class="text-sm font-medium text-gray-700">Valor bruto: {{ number_format($valorBruto, 2, ',', '.') }}</h1>
                <h1 class="text-sm font-medium text-gray-700">Valor com descontos: {{ number_format($valorTotal, 2, ',', '.') }}</h1>
                <h1 class="text-sm font-medium text-red-600">Falta receber: {{ number_format($faltaReceber, 2, ',', '.') }}</h1>
            </div>

            {{-- Dados principais --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Data do Pagamento</label>
                    <input type="date" name="data_pagamento" value="{{ old('data_pagamento') }}" class="mt-1 w-full border rounded px-3 py-2">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Valor Pago (base p/ retenções)</label>
                    <input type="number" step="0.01" min="0" name="valor_pago" value="{{ old('valor_pago') }}" class="mt-1 w-full border rounded px-3 py-2" required>
                </div>
            </div>

            {{-- Retenções (em %) — INSS e CSLL removidos --}}
            <h3 class="text-md font-semibold mt-2">Retenções</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @foreach (['irrf','iss','pis','cofins','outros'] as $k)
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ strtoupper($k) }} (%)</label>
                        <input type="number" step="0.01" min="0" max="100" name="ret_{{ $k }}" value="{{ old('ret_'.$k) }}" class="mt-1 w-full border rounded px-3 py-2">
                    </div>
                @endforeach
            </div>

            {{-- Adesão à ata / Advogado --}}
            <div class="border-t pt-4">
                <label class="inline-flex items-center gap-2">
                    <input type="checkbox" name="adesao_ata" value="1" id="chkAdesao" @checked(old('adesao_ata'))>
                    <span class="text-sm font-medium text-gray-700">Adesão à ata</span>
                </label>

                <div id="boxAdesao" class="mt-3 grid grid-cols-1 md:grid-cols-3 gap-4 {{ old('adesao_ata') ? '' : 'hidden' }}">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Advogado</label>
                        <select name="advogado_id" id="advogado_id" class="mt-1 w-full border rounded px-3 py-2">
                            <option value="">— Selecione —</option>
                            @foreach($advogados as $a)
                                <option value="{{ $a->id }}"
                                        data-perc="{{ (float)($a->percentual_vendas ?? 0) }}"
                                        @selected(old('advogado_id')==$a->id)>
                                    {{ $a->nome }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">% Comissão Advogado</label>
                        <input type="number" step="0.01" min="0" max="100"
                               name="perc_comissao_advogado" id="perc_comissao_advogado"
                               value="{{ old('perc_comissao_advogado') }}"
                               class="mt-1 w-full border rounded px-3 py-2">
                    </div>
                    <div></div>
                </div>
            </div>

            {{-- Diretor Comercial --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Diretor Comercial</label>
                    <select name="diretor_id" id="diretor_id" class="mt-1 w-full border rounded px-3 py-2">
                        <option value="">— Selecione —</option>
                        @foreach($diretores as $d)
                            <option value="{{ $d->id }}"
                                    data-perc="{{ (float)($d->percentual_vendas ?? 0) }}"
                                    @selected(old('diretor_id')==$d->id)>
                                {{ $d->nome }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">% Comissão Diretor</label>
                    <input type="number" step="0.01" min="0" max="100"
                           name="perc_comissao_diretor" id="perc_comissao_diretor"
                           value="{{ old('perc_comissao_diretor') }}"
                           class="mt-1 w-full border rounded px-3 py-2">
                </div>
            </div>

            {{-- Observações --}}
            <div>
                <label class="block text-sm font-medium text-gray-700">Observações</label>
                <textarea name="observacoes" rows="3" class="mt-1 w-full border rounded px-3 py-2">{{ old('observacoes') }}</textarea>
            </div>

            {{-- Preview (JS) --}}
            <div class="bg-gray-50 border rounded p-4 text-sm space-y-2" id="previewBox">
                <div class="font-semibold">Retenções (em % e em R$)</div>

                <ul class="space-y-1">
                    <li class="flex items-center justify-between">
                        <span>IRRF (<span id="lblRetIrrf">0,00</span>%)</span>
                        <span id="valRetIrrf">R$ 0,00</span>
                    </li>
                    <li class="flex items-center justify-between">
                        <span>ISS (<span id="lblRetIss">0,00</span>%)</span>
                        <span id="valRetIss">R$ 0,00</span>
                    </li>
                    <li class="flex items-center justify-between">
                        <span>PIS (<span id="lblRetPis">0,00</span>%)</span>
                        <span id="valRetPis">R$ 0,00</span>
                    </li>
                    <li class="flex items-center justify-between">
                        <span>COFINS (<span id="lblRetCofins">0,00</span>%)</span>
                        <span id="valRetCofins">R$ 0,00</span>
                    </li>
                    <li class="flex items-center justify-between">
                        <span>OUTROS (<span id="lblRetOutros">0,00</span>%)</span>
                        <span id="valRetOutros">R$ 0,00</span>
                    </li>
                </ul>

                <div class="pt-1 flex items-center justify-between border-t mt-2 pt-2">
                    <strong>Total de Retenções:</strong>
                    <span id="prevRet">R$ 0,00</span>
                </div>

                <div class="flex items-center justify-between">
                    <strong>Valor Líquido:</strong>
                    <span id="prevLiq">R$ 0,00</span>
                </div>

                <div class="flex items-center justify-between">
                    <strong>Faltará após este pagamento:</strong>
                    <span id="prevFaltara">R$ 0,00</span>
                </div>

                <div class="pt-2 font-semibold">Comissões sobre o Líquido</div>
                <div class="flex items-center justify-between">
                    <span>Gestor: {{ optional($nota->pedido->gestor)->razao_social ?? '—' }} ({{ number_format($percGestor ?? 0, 2, ',', '.') }}%)</span>
                    <span id="prevGest">R$ 0,00</span>
                </div>
                <div class="flex items-center justify-between">
                    <span>Distribuidor: {{ optional($nota->pedido->distribuidor)->razao_social ?? 'Sem distribuidor' }} ({{ number_format($percDistribuidor ?? 0, 2, ',', '.') }}%)</span>
                    <span id="prevDist">R$ 0,00</span>
                </div>
                <div class="flex items-center justify-between">
                    <span>Advogado: <span id="lblNomeAdv">—</span> (<span id="lblPercAdv">{{ number_format(old('perc_comissao_advogado', 0), 2, ',', '.') }}</span>%)</span>
                    <span id="prevAdv">R$ 0,00</span>
                </div>
                <div class="flex items-center justify-between">
                    <span>Diretor: <span id="lblNomeDir">—</span> (<span id="lblPercDir">{{ number_format(old('perc_comissao_diretor', 0), 2, ',', '.') }}</span>%)</span>
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
        // Percentuais automáticos do pedido
        const PERC_GESTOR       = Number(@json($percGestor ?? 0));
        const PERC_DISTRIBUIDOR = Number(@json($percDistribuidor ?? 0));

        // Totais (para preview "faltará")
        const VALOR_TOTAL = Number(@json($valorTotal));
        const JA_PAGO     = Number(@json($jaPago));

        const form       = document.getElementById('formPagamento');
        const chkAdesao  = document.getElementById('chkAdesao');
        const boxAdesao  = document.getElementById('boxAdesao');
        const lblPercAdv = document.getElementById('lblPercAdv');
        const lblPercDir = document.getElementById('lblPercDir');

        const selAdv = document.getElementById('advogado_id');
        const inpAdv = document.getElementById('perc_comissao_advogado');
        const selDir = document.getElementById('diretor_id');
        const inpDir = document.getElementById('perc_comissao_diretor');

        // labels (%) e valores (R$) das retenções — INSS e CSLL removidos
        const retMap = {
            irrf:   { lbl: document.getElementById('lblRetIrrf'),   val: document.getElementById('valRetIrrf') },
            iss:    { lbl: document.getElementById('lblRetIss'),    val: document.getElementById('valRetIss') },
            pis:    { lbl: document.getElementById('lblRetPis'),    val: document.getElementById('valRetPis') },
            cofins: { lbl: document.getElementById('lblRetCofins'), val: document.getElementById('valRetCofins') },
            outros: { lbl: document.getElementById('lblRetOutros'), val: document.getElementById('valRetOutros') },
        };

        const money = (n) => {
            const v = isFinite(n) ? Number(n) : 0;
            return 'R$ ' + v.toFixed(2).replace('.', ',');
        };

        const pctLabel = (n) => {
            const v = isFinite(n) ? Number(n) : 0;
            return v.toFixed(2).replace('.', ',');
        };

        const getSelectText = (selectEl) => {
            if (!selectEl) return '';
            const opt = selectEl.options[selectEl.selectedIndex];
            return opt ? (opt.text || '').trim() : '';
        };

        function val(name) {
            const el = form.querySelector(`[name="${name}"]`);
            if (!el) return 0;
            const raw = (el.value || '').toString().replace(',', '.').trim();
            return parseFloat(raw) || 0;
        }

        function autoPercFromSelect(selectEl, inputEl) {
            if (!selectEl || !inputEl) return;
            const opt = selectEl.options[selectEl.selectedIndex];
            if (!opt) return;
            const perc = parseFloat(opt.getAttribute('data-perc') || '0') || 0;
            // só preenche se o campo está vazio
            if (!inputEl.value) {
                inputEl.value = perc.toFixed(2);
            }
        }

        function recalc() {
            const valorPago = val('valor_pago');

            // Retenções informadas em % -> converte para R$
            const pIrrf   = val('ret_irrf');
            const pIss    = val('ret_iss');
            const pPis    = val('ret_pis');
            const pCofins = val('ret_cofins');
            const pOutros = val('ret_outros');

            const vIRRF   = valorPago * (pIrrf   / 100);
            const vISS    = valorPago * (pIss    / 100);
            const vPIS    = valorPago * (pPis    / 100);
            const vCOFINS = valorPago * (pCofins / 100);
            const vOUTROS = valorPago * (pOutros / 100);

            const totalRet = vIRRF + vISS + vPIS + vCOFINS + vOUTROS;
            const liquido  = Math.max(0, valorPago - totalRet);

            // Atualiza labels das retenções
            retMap.irrf.lbl.textContent   = pctLabel(pIrrf);   retMap.irrf.val.textContent   = money(vIRRF);
            retMap.iss.lbl.textContent    = pctLabel(pIss);    retMap.iss.val.textContent    = money(vISS);
            retMap.pis.lbl.textContent    = pctLabel(pPis);    retMap.pis.val.textContent    = money(vPIS);
            retMap.cofins.lbl.textContent = pctLabel(pCofins); retMap.cofins.val.textContent = money(vCOFINS);
            retMap.outros.lbl.textContent = pctLabel(pOutros); retMap.outros.val.textContent = money(vOUTROS);

            // Preview: faltará após este pagamento (considera base "valor_pago" para quitar o total)
            const faltara = Math.max(0, (VALOR_TOTAL - JA_PAGO) - valorPago);
            const faltaraEl = document.getElementById('prevFaltara');
            if (faltaraEl) faltaraEl.textContent = money(faltara);

            // Comissões automáticas (gestor/distribuidor)
            const gest = liquido * (PERC_GESTOR / 100);
            const dist = liquido * (PERC_DISTRIBUIDOR / 100);

            // Comissões variáveis (advogado/diretor) — sobre o LÍQUIDO
            let adv = 0;
            if (chkAdesao && chkAdesao.checked) {
                // nome do advogado
                const nomeAdv = getSelectText(selAdv) || '—';
                const lblNomeAdv = document.getElementById('lblNomeAdv');
                if (lblNomeAdv) lblNomeAdv.textContent = nomeAdv;

                // % e valor
                adv = liquido * (val('perc_comissao_advogado') / 100);
                if (lblPercAdv) lblPercAdv.textContent = pctLabel(val('perc_comissao_advogado') || 0);
            } else {
                const lblNomeAdv = document.getElementById('lblNomeAdv');
                if (lblNomeAdv) lblNomeAdv.textContent = '—';
                if (lblPercAdv) lblPercAdv.textContent = pctLabel(0);
            }

            // Diretor: nome, %, valor
            const nomeDir = getSelectText(selDir) || '—';
            const lblNomeDir = document.getElementById('lblNomeDir');
            if (lblNomeDir) lblNomeDir.textContent = nomeDir;

            const dir = liquido * (val('perc_comissao_diretor') / 100);
            if (lblPercDir) lblPercDir.textContent = pctLabel(val('perc_comissao_diretor') || 0);

            // Atualiza totals
            document.getElementById('prevRet').textContent  = money(totalRet);
            document.getElementById('prevLiq').textContent  = money(liquido);
            document.getElementById('prevGest').textContent = money(gest);
            document.getElementById('prevDist').textContent = money(dist);
            document.getElementById('prevAdv').textContent  = money(adv);
            document.getElementById('prevDir').textContent  = money(dir);
        }

        // Eventos
        chkAdesao?.addEventListener('change', () => {
            boxAdesao.classList.toggle('hidden', !chkAdesao.checked);
            recalc();
        });
        selAdv?.addEventListener('change', () => { autoPercFromSelect(selAdv, inpAdv); recalc(); });
        selDir?.addEventListener('change', () => { autoPercFromSelect(selDir, inpDir); recalc(); });
        form.addEventListener('input', recalc);

        document.addEventListener('DOMContentLoaded', () => {
            autoPercFromSelect(selAdv, inpAdv);
            autoPercFromSelect(selDir, inpDir);
            recalc();
        });
    </script>
</x-app-layout>
