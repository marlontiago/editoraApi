<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Editar Gestor</h2>
    </x-slot>

    <div class="p-6 mx-auto max-w-6xl">
        {{-- Resumo de validação --}}
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

        <form method="POST" action="{{ route('admin.gestores.update', $gestor) }}" enctype="multipart/form-data"
              class="bg-white shadow rounded-lg p-6 grid grid-cols-12 gap-4">
            @csrf
            @method('PUT')

            {{-- ====== Bloco: Dados do Gestor ====== --}}
            <div class="col-span-12">
                <h3 class="text-sm font-semibold text-gray-700">Dados do gestor</h3>
                <div class="mt-3 grid grid-cols-12 gap-4">
                    <div class="col-span-12 md:col-span-6">
                        <label for="razao_social" class="block text-sm font-medium text-gray-700">Razão Social <span class="text-red-600">*</span></label>
                        <input type="text" id="razao_social" name="razao_social" value="{{ old('razao_social', $gestor->razao_social) }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                    </div>

                    <div class="col-span-12 md:col-span-6">
                        <label for="representante_legal" class="block text-sm font-medium text-gray-700">Representante Legal <span class="text-red-600">*</span></label>
                        <input type="text" id="representante_legal" name="representante_legal" value="{{ old('representante_legal', $gestor->representante_legal) }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                    </div>

                    <div class="col-span-12 md:col-span-6">
                        <label for="cnpj" class="block text-sm font-medium text-gray-700">CNPJ <span class="text-red-600">*</span></label>
                        <input type="text" id="cnpj" name="cnpj" value="{{ old('cnpj', $gestor->cnpj) }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                    </div>

                    <div class="col-span-12 md:col-span-3">
                        <label for="cpf" class="block text-sm font-medium text-gray-700">CPF <span class="text-red-600">*</span></label>
                        <input type="text" id="cpf" name="cpf" value="{{ old('cpf', $gestor->cpf) }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                    </div>

                    <div class="col-span-12 md:col-span-3">
                        <label for="rg" class="block text-sm font-medium text-gray-700">RG <span class="text-red-600">*</span></label>
                        <input type="text" id="rg" name="rg" value="{{ old('rg', $gestor->rg) }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                    </div>

                    <div class="col-span-12 md:col-span-6">
                        <label for="telefone" class="block text-sm font-medium text-gray-700">Telefone</label>
                        <input type="text" id="telefone" name="telefone" value="{{ old('telefone', $gestor->telefone) }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div class="col-span-12 md:col-span-6">
                        <label for="email" class="block text-sm font-medium text-gray-700">E-mail (preencha se quiser alterar)</label>
                        <input type="email" id="email" name="email" value="{{ old('email', $gestor->user->email) }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div class="col-span-12">
                        <label for="endereco_completo" class="block text-sm font-medium text-gray-700">Endereço Completo</label>
                        <input type="text" id="endereco_completo" name="endereco_completo" value="{{ old('endereco_completo', $gestor->endereco_completo) }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
            </div>

            {{-- ====== Bloco: Localização & Cidades ====== --}}
            <div class="col-span-12">
                <h3 class="text-sm font-semibold text-gray-700">Localização & cidades</h3>
                <div class="mt-3 grid grid-cols-12 gap-4">
                    <div class="col-span-12 md:col-span-4">
                        <label for="estado_uf" class="block text-sm font-medium text-gray-700">UF do gestor</label>
                        @php
                            $ufs = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'];
                        @endphp
                        <select id="estado_uf" name="estado_uf"
                                class="mt-1 block w-full rounded-md border-gray-300 bg-white shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Selecione</option>
                            @foreach($ufs as $uf)
                                <option value="{{ $uf }}" {{ old('estado_uf', $gestor->estado_uf) === $uf ? 'selected' : '' }}>
                                    {{ $uf }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    
                </div>
            </div>

            {{-- ====== Bloco: Comercial ====== --}}
            <div class="col-span-12">
                <h3 class="text-sm font-semibold text-gray-700">Condições comerciais</h3>
                <div class="mt-3 grid grid-cols-12 gap-4">
                    <div class="col-span-12 md:col-span-5">
                        <label for="percentual_vendas" class="block text-sm font-medium text-gray-700">Percentual sobre vendas <span class="text-red-600">*</span></label>
                        <div class="mt-1 flex rounded-md shadow-sm">
                            <input type="number" id="percentual_vendas" name="percentual_vendas" step="0.01" min="0" max="100"
                                   value="{{ old('percentual_vendas', $gestor->percentual_vendas) }}"
                                   class="flex-1 rounded-l-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                            <span class="inline-flex items-center rounded-r-md border border-l-0 border-gray-300 bg-gray-50 px-3 text-sm text-gray-600">%</span>
                        </div>
                    </div>

                    <div class="col-span-12 md:col-span-4">
                        <label for="vencimento_contrato" class="block text-sm font-medium text-gray-700">Vencimento do contrato</label>
                        <input type="date" id="vencimento_contrato" name="vencimento_contrato"
                               value="{{ old('vencimento_contrato', $gestor->vencimento_contrato ? $gestor->vencimento_contrato->format('Y-m-d') : '') }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div class="col-span-12 md:col-span-3">
                        <label class="block text-sm font-medium text-gray-700">Contrato assinado</label>
                        <div class="mt-2 flex items-center gap-3">
                            <input type="hidden" name="contrato_assinado" value="0">
                            <input type="checkbox" id="contrato_assinado" name="contrato_assinado" value="1"
                                   class="rounded border-gray-300"
                                   {{ old('contrato_assinado', $gestor->contrato_assinado) ? 'checked' : '' }}>
                            <span class="text-sm text-gray-700">Sim</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ====== Bloco: Contrato (PDF) ====== --}}
            <div class="col-span-12">
                <h3 class="text-sm font-semibold text-gray-700">Contrato</h3>
                <div class="mt-3 grid grid-cols-12 gap-4">
                    <div class="col-span-12 md:col-span-8">
                        <label for="contrato" class="block text-sm font-medium text-gray-700">Anexar novo contrato (PDF)</label>
                        <input type="file" id="contrato" name="contrato" accept=".pdf,application/pdf"
                               class="mt-1 block w-full rounded-md border-gray-300 text-sm file:mr-4 file:rounded-md file:border-0 file:bg-gray-100 file:px-4 file:py-2 file:text-gray-700 hover:file:bg-gray-200">
                        <p class="mt-1 text-xs text-gray-500">PDF até 2MB.</p>

                        @if($gestor->contrato)
                            <p class="mt-2 text-sm text-blue-600">
                                <a href="{{ asset('storage/' . $gestor->contrato) }}" target="_blank" class="underline">Ver contrato atual</a>
                            </p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- ====== Ações ====== --}}
            <div class="col-span-12 flex items-center justify-end gap-3 pt-2">
                <a href="{{ route('admin.gestores.index') }}"
                   class="inline-flex h-10 items-center rounded-md border px-4 text-sm hover:bg-gray-50">
                    Cancelar
                </a>
                <button type="submit"
                        class="inline-flex h-10 items-center rounded-md bg-blue-600 px-5 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    Atualizar
                </button>
            </div>
        </form>
    </div>

    {{-- JS: filtra cidades conforme UF selecionada --}}
    <script>
      (function () {
        const ufSelect = document.getElementById('estado_uf');
        const citiesSelect = document.getElementById('cities');

        // cacheia todas as opções originais
        const allOptions = Array.from(citiesSelect.querySelectorAll('option'))
          .map(opt => ({ value: opt.value, text: opt.textContent, state: opt.dataset.state, selected: opt.selected }));

        function renderByUF(uf) {
          const selected = new Set(
            @json(collect(old('cities', $gestor->cities->pluck('id')->all()))->map(fn($i)=>(string)$i)->all())
          );

          citiesSelect.innerHTML = '';

          let filtered = allOptions;
          if (uf) {
            filtered = allOptions.filter(o => (o.state || '').toUpperCase() === uf.toUpperCase());
          }

          for (const o of filtered) {
            const opt = document.createElement('option');
            opt.value = o.value;
            opt.textContent = o.text;
            if (selected.has(String(o.value))) opt.selected = true;
            opt.dataset.state = o.state || '';
            citiesSelect.appendChild(opt);
          }
        }

        // inicializa com a UF atual
        renderByUF(ufSelect.value || '');

        ufSelect.addEventListener('change', e => renderByUF(e.target.value));
      })();
    </script>
</x-app-layout>
