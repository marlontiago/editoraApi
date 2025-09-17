<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Editar Distribuidor</h2>
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

        <form action="{{ route('admin.distribuidores.update', $distribuidor) }}" method="POST" enctype="multipart/form-data"
              class="bg-white shadow rounded-lg p-6 grid grid-cols-12 gap-4">
            @csrf
            @method('PUT')

            {{-- ===== Gestor ===== --}}
            <div class="col-span-12 md:col-span-6">
                <label for="gestor_id" class="block text-sm font-medium text-gray-700">Gestor <span class="text-red-600">*</span></label>
                <select name="gestor_id" id="gestor_id"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                    <option value="">-- Selecione --</option>
                    @foreach($gestores as $gestor)
                        <option value="{{ $gestor->id }}" @selected(old('gestor_id', $distribuidor->gestor_id) == $gestor->id)>
                            {{ $gestor->razao_social }}
                        </option>
                    @endforeach
                </select>
                @error('gestor_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror

                <br>

                {{-- ===== UF para cidades ===== --}}
                @php
                    $ufs = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'];
                    // UF default do seletor de cidades: tenta old('uf_cidades') senão o endereço do distribuidor
                    $ufCidadesDefault = old('uf_cidades', $distribuidor->uf);
                @endphp
                <div class="col-span-12 md:col-span-3">
                    <label for="uf_cidades" class="block text-sm font-medium text-gray-700">UF de atuação</label>
                    <select id="uf_cidades" name="uf_cidades"
                            class="mt-1 block w-full rounded-md border-gray-300 bg-white shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">-- Selecione --</option>
                        @foreach($ufs as $uf)
                            <option value="{{ $uf }}" @selected($ufCidadesDefault === $uf)>{{ $uf }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Liste e selecione as cidades desta UF.</p>
                    @error('uf_cidades') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- ===== Cidades (múltipla seleção) ===== --}}
            <div class="col-span-12 md:col-span-6">
                <label class="block text-sm font-medium text-gray-700">Cidades de atuação</label>
                <select name="cities[]" id="cities" multiple size="10"
                        class="mt-1 block w-full rounded-md border-gray-300 bg-gray-50 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        disabled>
                    {{-- preenchido via JS --}}
                </select>
                <p class="mt-1 text-xs text-gray-500">Segure Ctrl/Cmd para múltipla seleção.</p>
                @error('cities') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- ===== Dados cadastrais ===== --}}
            <div class="col-span-12 md:col-span-6">
                <label for="razao_social" class="block text-sm font-medium text-gray-700">Razão Social <span class="text-red-600">*</span></label>
                <input type="text" id="razao_social" name="razao_social" value="{{ old('razao_social', $distribuidor->razao_social) }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                @error('razao_social') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="col-span-12 md:col-span-6">
                <label for="representante_legal" class="block text-sm font-medium text-gray-700">Representante Legal <span class="text-red-600">*</span></label>
                <input type="text" id="representante_legal" name="representante_legal" value="{{ old('representante_legal', $distribuidor->representante_legal) }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                @error('representante_legal') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="col-span-12 md:col-span-6">
                <label for="cnpj" class="block text-sm font-medium text-gray-700">CNPJ <span class="text-red-600">*</span></label>
                <input type="text" id="cnpj" name="cnpj" value="{{ old('cnpj', $distribuidor->cnpj) }}" maxlength="18"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                @error('cnpj') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="col-span-12 md:col-span-3">
                <label for="cpf" class="block text-sm font-medium text-gray-700">CPF <span class="text-red-600">*</span></label>
                <input type="text" id="cpf" name="cpf" value="{{ old('cpf', $distribuidor->cpf) }}" maxlength="14"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                @error('cpf') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="col-span-12 md:col-span-3">
                <label for="rg" class="block text-sm font-medium text-gray-700">RG <span class="text-red-600">*</span></label>
                <input type="text" id="rg" name="rg" value="{{ old('rg', $distribuidor->rg) }}" maxlength="30"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                @error('rg') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- ===== Credenciais do usuário (opcionais) ===== --}}
            <div class="col-span-12 md:col-span-6">
                <label for="email" class="block text-sm font-medium text-gray-700">E-mail (preencha para alterar)</label>
                <input type="email" id="email" name="email" value="{{ old('email', optional($distribuidor->user)->email) }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-12 md:col-span-6">
                <label for="password" class="block text-sm font-medium text-gray-700">Senha (deixe em branco para manter)</label>
                <input type="password" id="password" name="password"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- ===== Endereço ===== --}}
            <div class="col-span-12 md:col-span-6">
                <label for="endereco" class="block text-sm font-medium text-gray-700">Endereço</label>
                <input type="text" id="endereco" name="endereco" value="{{ old('endereco', $distribuidor->endereco) }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('endereco') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-12 md:col-span-3">
                <label for="numero" class="block text-sm font-medium text-gray-700">Número</label>
                <input type="text" id="numero" name="numero" value="{{ old('numero', $distribuidor->numero) }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('numero') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-12 md:col-span-3">
                <label for="complemento" class="block text-sm font-medium text-gray-700">Complemento</label>
                <input type="text" id="complemento" name="complemento" value="{{ old('complemento', $distribuidor->complemento) }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('complemento') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-12 md:col-span-4">
                <label for="bairro" class="block text-sm font-medium text-gray-700">Bairro</label>
                <input type="text" id="bairro" name="bairro" value="{{ old('bairro', $distribuidor->bairro) }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('bairro') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-12 md:col-span-5">
                <label for="cidade" class="block text-sm font-medium text-gray-700">Cidade</label>
                <input type="text" id="cidade" name="cidade" value="{{ old('cidade', $distribuidor->cidade) }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('cidade') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-12 md:col-span-1">
                <label for="uf" class="block text-sm font-medium text-gray-700">UF</label>
                <select id="uf" name="uf"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">--</option>
                    @foreach($ufs as $uf)
                        <option value="{{ $uf }}" @selected(old('uf', $distribuidor->uf) === $uf)>{{ $uf }}</option>
                    @endforeach
                </select>
                @error('uf') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-12 md:col-span-2">
                <label for="cep" class="block text-sm font-medium text-gray-700">CEP</label>
                <input type="text" id="cep" name="cep" value="{{ old('cep', $distribuidor->cep) }}" maxlength="9"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('cep') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- ===== Condições comerciais ===== --}}
            <div class="col-span-12 md:col-span-5">
                <label for="percentual_vendas" class="block text-sm font-medium text-gray-700">Percentual sobre vendas</label>
                <div class="mt-1 flex rounded-md shadow-sm">
                    <input type="number" id="percentual_vendas" name="percentual_vendas" step="0.01" min="0" max="100"
                           value="{{ old('percentual_vendas', $distribuidor->percentual_vendas) }}"
                           class="flex-1 rounded-l-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <span class="inline-flex items-center rounded-r-md border border-l-0 border-gray-300 bg-gray-50 px-3 text-sm text-gray-600">%</span>
                </div>
                @error('percentual_vendas') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- ===== Início + Validade (meses) (perto dos anexos) ===== --}}
            <div class="col-span-12 md:col-span-4">
                <label for="inicio_contrato" class="block text-sm font-medium text-gray-700">Início do contrato</label>
                <input type="date" id="inicio_contrato" name="inicio_contrato" value="{{ old('inicio_contrato') }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('inicio_contrato') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                <p class="mt-1 text-xs text-gray-500">Vencimento = início + validade (meses).</p>
            </div>
            <div class="col-span-12 md:col-span-2">
                <label for="validade_meses" class="block text-sm font-medium text-gray-700">Validade (meses)</label>
                <input type="number" id="validade_meses" name="validade_meses" value="{{ old('validade_meses') }}"
                       min="1" max="120" step="1"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('validade_meses') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- ===== Anexos (múltiplos) ===== --}}
            <div class="col-span-12">
                <h3 class="text-sm font-semibold text-gray-700 mb-2">Anexos existentes</h3>
                @if(isset($distribuidor->anexos) && $distribuidor->anexos->count())
                    <ul class="mb-4 list-disc pl-5 text-sm">
                        @foreach($distribuidor->anexos as $ax)
                            <li class="mb-1">
                                <span class="font-medium">{{ ucfirst($ax->tipo) }}</span>
                                @if($ax->descricao) — {{ $ax->descricao }} @endif
                                @if($ax->assinado)
                                    <span class="ml-2 inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700">assinado</span>
                                @endif
                                @if($ax->arquivo)
                                    — <a href="{{ asset('storage/'.$ax->arquivo) }}" target="_blank" class="text-blue-600 hover:underline">ver</a>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="mb-4 text-sm text-gray-500">Nenhum anexo cadastrado.</p>
                @endif

                <h3 class="text-sm font-semibold text-gray-700">Adicionar novos anexos (PDF)</h3>
            </div>

            <div x-data="{ itens: [{id: Date.now()}] }" class="col-span-12">
                <template x-for="(item, idx) in itens" :key="item.id">
                    <div class="grid grid-cols-12 gap-3 mb-3">
                        <div class="col-span-12 md:col-span-3">
                            <select :name="`contratos[${idx}][tipo]`"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="contrato">Contrato</option>
                                <option value="aditivo">Aditivo</option>
                                <option value="outro">Outro</option>
                            </select>
                        </div>
                        <div class="col-span-12 md:col-span-7">
                            <input type="file" accept="application/pdf" :name="`contratos[${idx}][arquivo]`"
                                   class="mt-1 block w-full rounded-md border-gray-300 text-sm file:mr-4 file:rounded-md file:border-0 file:bg-gray-100 file:px-4 file:py-2 file:text-gray-700 hover:file:bg-gray-200">
                        </div>
                        <div class="col-span-12 md:col-span-2">
                            <button type="button"
                                    class="inline-flex h-9 items-center mt-1 rounded-md border px-3 text-sm hover:bg-gray-50 w-full justify-center"
                                    @click="itens.splice(idx,1)" x-show="itens.length > 1">
                                Remover
                            </button>
                        </div>
                        <div class="col-span-12">
                            <input type="text" placeholder="Descrição (opcional)" :name="`contratos[${idx}][descricao]`"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <label class="inline-flex items-center text-sm mt-2">
                                <input type="checkbox" :name="`contratos[${idx}][assinado]`" value="1" class="rounded border-gray-300">
                                <span class="ml-2">Assinado</span>
                            </label>
                        </div>
                    </div>
                </template>

                <button type="button"
                        class="inline-flex h-9 items-center rounded-md border px-3 text-sm hover:bg-gray-50"
                        @click="itens.push({id: Date.now()})">
                    + Adicionar anexo
                </button>

                @error('contratos.*.arquivo') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- ===== Ações ===== --}}
            <div class="col-span-12 flex items-center justify-end gap-3 pt-2">
                <a href="{{ route('admin.distribuidores.index') }}"
                   class="inline-flex h-10 items-center rounded-md border px-4 text-sm hover:bg-gray-50">Cancelar</a>
                <button type="submit"
                        class="inline-flex h-10 items-center rounded-md bg-blue-600 px-5 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    Atualizar
                </button>
            </div>
        </form>
    </div>

    {{-- JS: carrega cidades por UF (marcando ocupadas) --}}
    <script>
        const ufSelect     = document.getElementById('uf_cidades');
        const citiesSelect = document.getElementById('cities');
        const BASE_CIDADES_UF = @json(url('/admin/cidades/por-uf'));

        // cidades já vinculadas (para pré-selecionar)
        const SELECTED = new Set(@json(collect(old('cities', $distribuidor->cities->pluck('id')->all()))->map(fn($i)=>(string)$i)));

        async function carregarCidadesPorUF(uf) {
            citiesSelect.innerHTML = '';
            citiesSelect.disabled  = true;
            if (!uf) return;

            try {
                const resp = await fetch(`${BASE_CIDADES_UF}/${encodeURIComponent(uf)}?with_occupancy=1`, {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });

                if (!resp.ok) throw new Error(`HTTP ${resp.status}`);
                if (!(resp.headers.get('content-type') || '').includes('application/json')) {
                    throw new Error('Resposta não é JSON');
                }

                const payload = await resp.json();
                const cidades = Array.isArray(payload) ? payload : (payload.data ?? []);

                for (const c of cidades) {
                    const opt = document.createElement('option');
                    opt.value = c.id;
                    if (c.occupied) {
                        const quem = c.distribuidor_name ? ` — ocupada por ${c.distribuidor_name}` : ' — já ocupada';
                        opt.textContent = `${c.name}${quem}`;
                        opt.disabled = true;
                        opt.classList.add('text-gray-500');
                    } else {
                        opt.textContent = c.name;
                        if (SELECTED.has(String(c.id))) opt.selected = true;
                    }
                    citiesSelect.appendChild(opt);
                }

                citiesSelect.disabled = cidades.length === 0;
            } catch (e) {
                citiesSelect.innerHTML = '';
                citiesSelect.disabled = true;
                console.error('[carregarCidadesPorUF] erro:', e);
                alert('Não foi possível carregar as cidades para a UF selecionada.');
            }
        }

        // Inicializa caso já tenha uma UF (old ou do endereço do distribuidor)
        @if ($ufCidadesDefault)
            carregarCidadesPorUF(@json($ufCidadesDefault));
        @endif

        ufSelect.addEventListener('change', e => carregarCidadesPorUF(e.target.value));
    </script>
</x-app-layout>
