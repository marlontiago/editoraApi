<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Novo Distribuidor</h2>
    </x-slot>

    <div class="max-w-6xl mx-auto p-6">
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

        <form action="{{ route('admin.distribuidores.store') }}" method="POST" enctype="multipart/form-data"
              class="bg-white shadow rounded-lg p-6 grid grid-cols-12 gap-4">
            @csrf

            {{-- Gestor --}}
            <div class="col-span-12 md:col-span-6">
                <label for="gestor_id" class="block text-sm font-medium text-gray-700">Gestor <span class="text-red-600">*</span></label>
                <select name="gestor_id" id="gestor_id"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                    <option value="">-- Selecione --</option>
                    @foreach($gestores as $gestor)
                        <option value="{{ $gestor->id }}" @selected(old('gestor_id') == $gestor->id)>
                            {{ $gestor->razao_social }}
                        </option>
                    @endforeach
                </select>
                @error('gestor_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Cidades de atuação (múltipla seleção) --}}
            <div class="col-span-12 md:col-span-6">
                <label class="block text-sm font-medium text-gray-700">Cidades de atuação (UF do Gestor)</label>
                <select name="cities[]" id="cities" multiple size="10"
                        class="mt-1 block w-full rounded-md border-gray-300 bg-gray-50 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        disabled>
                    {{-- preenchido via JS --}}
                </select>
                <p class="mt-1 text-xs text-gray-500">Segure Ctrl/Cmd para múltipla seleção.</p>
                @error('cities') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Email --}}
            <div class="col-span-12 md:col-span-6">
                <label for="email" class="block text-sm font-medium text-gray-700">E-mail <span class="text-red-600">*</span></label>
                <input type="email" id="email" name="email" value="{{ old('email') }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Senha / Confirmar --}}
            <div class="col-span-12 md:col-span-3">
                <label for="password" class="block text-sm font-medium text-gray-700">Senha <span class="text-red-600">*</span></label>
                <input type="password" id="password" name="password"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-12 md:col-span-3">
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirmar Senha <span class="text-red-600">*</span></label>
                <input type="password" id="password_confirmation" name="password_confirmation"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
            </div>

            {{-- Razão Social --}}
            <div class="col-span-12 md:col-span-6">
                <label for="razao_social" class="block text-sm font-medium text-gray-700">Razão Social <span class="text-red-600">*</span></label>
                <input type="text" id="razao_social" name="razao_social" value="{{ old('razao_social') }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                @error('razao_social') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- CNPJ / Representante / CPF / RG --}}
            <div class="col-span-12 md:col-span-6">
                <label for="cnpj" class="block text-sm font-medium text-gray-700">CNPJ <span class="text-red-600">*</span></label>
                <input type="text" id="cnpj" name="cnpj" value="{{ old('cnpj') }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                @error('cnpj') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="col-span-12 md:col-span-6">
                <label for="representante_legal" class="block text-sm font-medium text-gray-700">Representante Legal <span class="text-red-600">*</span></label>
                <input type="text" id="representante_legal" name="representante_legal" value="{{ old('representante_legal') }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                @error('representante_legal') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="col-span-12 md:col-span-3">
                <label for="cpf" class="block text-sm font-medium text-gray-700">CPF <span class="text-red-600">*</span></label>
                <input type="text" id="cpf" name="cpf" value="{{ old('cpf') }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                @error('cpf') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="col-span-12 md:col-span-3">
                <label for="rg" class="block text-sm font-medium text-gray-700">RG <span class="text-red-600">*</span></label>
                <input type="text" id="rg" name="rg" value="{{ old('rg') }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                @error('rg') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Telefone / Endereço --}}
            <div class="col-span-12 md:col-span-6">
                <label for="telefone" class="block text-sm font-medium text-gray-700">Telefone</label>
                <input type="text" id="telefone" name="telefone" value="{{ old('telefone') }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('telefone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="col-span-12 md:col-span-6">
                <label for="endereco_completo" class="block text-sm font-medium text-gray-700">Endereço Completo</label>
                <input type="text" id="endereco_completo" name="endereco_completo" value="{{ old('endereco_completo') }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('endereco_completo') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Percentual / Vencimento --}}
            <div class="col-span-12 md:col-span-5">
                <label for="percentual_vendas" class="block text-sm font-medium text-gray-700">Percentual sobre vendas <span class="text-red-600">*</span></label>
                <div class="mt-1 flex rounded-md shadow-sm">
                    <input type="number" id="percentual_vendas" name="percentual_vendas" step="0.01" min="0" max="100"
                           value="{{ old('percentual_vendas') }}"
                           class="flex-1 rounded-l-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                    <span class="inline-flex items-center rounded-r-md border border-l-0 border-gray-300 bg-gray-50 px-3 text-sm text-gray-600">%</span>
                </div>
                @error('percentual_vendas') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="col-span-12 md:col-span-4">
                <label for="vencimento_contrato" class="block text-sm font-medium text-gray-700">Vencimento do contrato</label>
                <input type="date" id="vencimento_contrato" name="vencimento_contrato" value="{{ old('vencimento_contrato') }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('vencimento_contrato') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Contrato assinado (toggle simples) --}}
            <div class="col-span-12 md:col-span-3">
                <label class="block text-sm font-medium text-gray-700">Contrato assinado</label>
                <div class="mt-2 flex items-center gap-3">
                    <input type="hidden" name="contrato_assinado" value="0">
                    <input type="checkbox" id="contrato_assinado" name="contrato_assinado" value="1" class="rounded border-gray-300"
                           {{ old('contrato_assinado') ? 'checked' : '' }}>
                    <span class="text-sm text-gray-700">Sim</span>
                </div>
                @error('contrato_assinado') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Anexar contrato (PDF) --}}
            <div class="col-span-12">
                <label for="contrato" class="block text-sm font-medium text-gray-700">Anexar contrato (PDF)</label>
                <input type="file" id="contrato" name="contrato" accept=".pdf,application/pdf"
                       class="mt-1 block w-full rounded-md border-gray-300 text-sm file:mr-4 file:rounded-md file:border-0 file:bg-gray-100 file:px-4 file:py-2 file:text-gray-700 hover:file:bg-gray-200">
                <p class="mt-1 text-xs text-gray-500">PDF até 5MB.</p>
                @error('contrato') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Ações --}}
            <div class="col-span-12 flex items-center justify-end gap-3 pt-2">
                <a href="{{ route('admin.distribuidores.index') }}"
                   class="inline-flex h-10 items-center rounded-md border px-4 text-sm hover:bg-gray-50">
                    Cancelar
                </a>
                <button type="submit"
                        class="inline-flex h-10 items-center rounded-md bg-blue-600 px-5 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    Salvar
                </button>
            </div>
        </form>
    </div>

    {{-- JS: carrega cidades por gestor (mantido) --}}
    <script>
      const gestorSelect = document.getElementById('gestor_id');
      const citiesSelect = document.getElementById('cities');

      async function carregarCidades(gestorId) {
        citiesSelect.innerHTML = '';
        citiesSelect.disabled = true;
        if (!gestorId) return;

        try {
          const resp = await fetch(`/admin/gestores/${gestorId}/cidades`, { credentials: 'same-origin' });
          if (!resp.ok) throw new Error(`HTTP ${resp.status}`);

          const ct = resp.headers.get('content-type') || '';
          if (!ct.includes('application/json')) throw new Error('Resposta não é JSON');

          const cidades = await resp.json();

          const oldCities = @json(old('cities', []));
          for (const c of cidades) {
            const opt = document.createElement('option');
            opt.value = c.id;
            opt.textContent = c.name;
            if (oldCities.includes(String(c.id)) || oldCities.includes(Number(c.id))) {
              opt.selected = true;
            }
            citiesSelect.appendChild(opt);
          }
          citiesSelect.disabled = cidades.length === 0;
        } catch (e) {
          // feedback simples
          citiesSelect.innerHTML = '';
          citiesSelect.disabled = true;
          alert('Não foi possível carregar as cidades para o gestor selecionado.');
          console.error(e);
        }
      }

      gestorSelect.addEventListener('change', e => carregarCidades(e.target.value));

      @if (old('gestor_id'))
        carregarCidades(@json((int) old('gestor_id')));
      @endif
    </script>
</x-app-layout>
