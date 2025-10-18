<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">
                Editar Anexo — {{ strtoupper($anexo->tipo) }}
            </h2>

            <div class="flex gap-2">
                <a href="{{ route('admin.gestores.show', $gestor) }}"
                   class="inline-flex h-9 items-center rounded-md border px-3 text-sm hover:bg-gray-50">
                    Voltar
                </a>
            </div>
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto p-6">
        @if ($errors->any())
            <div class="mb-6 rounded-md border border-red-300 bg-red-50 p-4 text-red-800">
                <div class="font-semibold mb-2">Corrija os campos abaixo:</div>
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li class="text-sm">{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form id="form-anexo" action="{{ route('admin.gestores.anexos.update', [$gestor, $anexo]) }}"
              method="POST" enctype="multipart/form-data"
              class="bg-white shadow rounded-lg p-6 grid grid-cols-12 gap-4">
            @csrf
            @method('PUT')

            {{-- Tipo --}}
            <div class="col-span-12 md:col-span-6">
                <label class="block text-sm font-medium mb-1">Tipo</label>
                <select name="tipo" id="tipo" class="w-full rounded-md border-gray-300">
                    @foreach (['contrato','aditivo','contrato_cidade','outro'] as $opt)
                        <option value="{{ $opt }}" @selected(old('tipo', $anexo->tipo) === $opt)>
                            {{ ucfirst(str_replace('_', ' ', $opt)) }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Percentual --}}
            <div class="col-span-12 md:col-span-6">
                <label class="block text-sm font-medium mb-1">Percentual de Vendas (%)</label>
                <input type="number" step="0.01" name="percentual_vendas"
                       value="{{ old('percentual_vendas', $anexo->percentual_vendas) }}"
                       class="w-full rounded-md border-gray-300">
            </div>

            {{-- Descrição --}}
            <div class="col-span-12">
                <label class="block text-sm font-medium mb-1">Descrição</label>
                <input type="text" name="descricao"
                       value="{{ old('descricao', $anexo->descricao) }}"
                       class="w-full rounded-md border-gray-300">
            </div>

            {{-- Cidade (apenas para contrato_cidade) --}}
            <div class="col-span-12 md:col-span-6" id="wrap-cidade">
                <label class="block text-sm font-medium mb-1">Cidade (para contrato_cidade)</label>
                <select name="cidade_id" class="w-full rounded-md border-gray-300" id="cidade_id">
                    <option value="">Selecione...</option>
                    @foreach ($cidades as $c)
                        <option value="{{ $c->id }}"
                            @selected( (int)old('cidade_id', (int)$anexo->cidade_id) === (int)$c->id )>
                            {{ $c->name }} - {{ strtoupper($c->uf)  }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Início + Duração (meses) --}}
            <div class="col-span-12 md:col-span-6">
                <label class="block text-sm font-medium mb-1">Início do contrato</label>
                <input type="date" id="data_inicio" name="data_inicio"
                       value="{{ old('data_inicio', optional($anexo->data_assinatura)->format('Y-m-d')) }}"
                       class="w-full rounded-md border-gray-300">
                <p class="text-xs text-gray-500 mt-1">Informe a data inicial.</p>
            </div>

            <div class="col-span-12 md:col-span-6">
                <label class="block text-sm font-medium mb-1">Duração (meses)</label>
                <input type="number" id="duracao_meses" name="duracao_meses" min="1" max="240"
                       value="{{ old('duracao_meses') }}"
                       class="w-full rounded-md border-gray-300" placeholder="Ex.: 12">
                <p class="text-xs text-gray-500 mt-1">O sistema calcula o fim automaticamente.</p>
            </div>

            {{-- Preview de Fim (não editável) --}}
            <div class="col-span-12 md:col-span-6">
                <label class="block text-sm font-medium mb-1">Fim (calculado)</label>
                <input type="text" id="fim_preview" class="w-full rounded-md border-gray-200 bg-gray-50"
                       value="{{ optional($anexo->data_vencimento)->format('d/m/Y') }}" readonly>
            </div>

            {{-- Flags --}}
            <div class="col-span-12 md:col-span-3">
                <label class="block text-sm font-medium mb-1">Assinado?</label>
                <input type="checkbox" name="assinado" value="1" @checked(old('assinado', $anexo->assinado))>
            </div>

            <div class="col-span-12 md:col-span-3">
                <label class="block text-sm font-medium mb-1">Ativo?</label>
                <input type="checkbox" name="ativo" value="1" @checked(old('ativo', $anexo->ativo))>
            </div>

            {{-- Substituir arquivo --}}
            <div class="col-span-12">
                <label class="block text-sm font-medium mb-1">Substituir arquivo (PDF)</label>
                <input type="file" name="arquivo" accept="application/pdf"
                       class="w-full rounded-md border-gray-300">
                @if($anexo->arquivo)
                    <p class="text-xs text-gray-500 mt-1">
                        Arquivo atual:
                        <a class="text-blue-600 hover:underline" target="_blank" href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($anexo->arquivo) }}">ver PDF</a>
                    </p>
                @endif
            </div>

            <div class="col-span-12">
                <button type="submit"
                        class="inline-flex items-center rounded-md bg-blue-600 px-4 h-10 text-sm text-white hover:bg-blue-700">
                    Salvar alterações
                </button>
            </div>
        </form>
    </div>

    {{-- JS: esconde/mostra cidade e calcula FIM (preview) --}}
    <script>
        (function() {
            const tipoSel   = document.getElementById('tipo');
            const wrapCidade= document.getElementById('wrap-cidade');
            const cidadeSel = document.getElementById('cidade_id');

            const dataInicio= document.getElementById('data_inicio');
            const duracao   = document.getElementById('duracao_meses');
            const fimPrev   = document.getElementById('fim_preview');

            function toggleCidade() {
                const show = tipoSel.value === 'contrato_cidade';
                wrapCidade.style.display = show ? '' : 'none';
                cidadeSel.disabled = !show;
                if (!show) cidadeSel.value = '';
            }

            function addMonthsNoOverflow(date, months) {
                const d = new Date(date.getTime());
                const day = d.getDate();
                d.setMonth(d.getMonth() + months);
                if (d.getDate() < day) d.setDate(0);
                return d;
            }

            function fmtBR(d) {
                const yyyy = d.getFullYear();
                const mm = String(d.getMonth() + 1).padStart(2, '0');
                const dd = String(d.getDate()).padStart(2, '0');
                return `${dd}/${mm}/${yyyy}`;
            }

            function recalcFim() {
                if (!dataInicio.value || !duracao.value) {
                    if (fimPrev) fimPrev.value = '';
                    return;
                }
                const inicio = new Date(dataInicio.value + 'T00:00:00');
                const meses  = parseInt(duracao.value, 10);
                if (isNaN(meses) || meses <= 0) return;

                const fim = addMonthsNoOverflow(inicio, meses);
                if (fimPrev) fimPrev.value = fmtBR(fim);
            }

            tipoSel.addEventListener('change', toggleCidade);
            dataInicio.addEventListener('change', recalcFim);
            duracao.addEventListener('input', recalcFim);

            // Inicial
            toggleCidade();
            recalcFim();
        })();
    </script>
</x-app-layout>
