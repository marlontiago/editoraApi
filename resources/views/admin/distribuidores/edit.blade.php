<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Editar Distribuidor</h2>
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

        <form action="{{ route('admin.distribuidores.update', $distribuidor->id) }}" method="POST" enctype="multipart/form-data"
              class="bg-white shadow rounded-lg p-6 grid grid-cols-12 gap-4">
            @csrf
            @method('PUT')

            {{-- ====== Conta de acesso ====== --}}
            <div class="col-span-12">
                <h3 class="text-sm font-semibold text-gray-700">Conta de acesso</h3>
                <div class="mt-3 grid grid-cols-12 gap-4">
                    <div class="col-span-12 md:col-span-6">
                        <label class="block text-sm font-medium text-gray-700">Nome de Usuário <span class="text-red-600">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $distribuidor->user->name) }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                    </div>

                    <div class="col-span-12 md:col-span-6">
                        <label class="block text-sm font-medium text-gray-700">E-mail <span class="text-red-600">*</span></label>
                        <input type="email" name="email" value="{{ old('email', $distribuidor->user->email) }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                    </div>

                    <div class="col-span-12 md:col-span-6">
                        <label class="block text-sm font-medium text-gray-700">Senha (opcional)</label>
                        <input type="password" name="password"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div class="col-span-12 md:col-span-6">
                        <label class="block text-sm font-medium text-gray-700">Confirmar senha</label>
                        <input type="password" name="password_confirmation"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
            </div>

            {{-- ====== Dados da empresa ====== --}}
            <div class="col-span-12">
                <h3 class="text-sm font-semibold text-gray-700">Dados da empresa</h3>
                <div class="mt-3 grid grid-cols-12 gap-4">
                    <div class="col-span-12 md:col-span-6">
                        <label class="block text-sm font-medium text-gray-700">Razão Social <span class="text-red-600">*</span></label>
                        <input type="text" name="razao_social" value="{{ old('razao_social', $distribuidor->razao_social) }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                    </div>

                    <div class="col-span-12 md:col-span-6">
                        <label class="block text-sm font-medium text-gray-700">CNPJ <span class="text-red-600">*</span></label>
                        <input type="text" name="cnpj" value="{{ old('cnpj', $distribuidor->cnpj) }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                    </div>

                    <div class="col-span-12 md:col-span-6">
                        <label class="block text-sm font-medium text-gray-700">Representante Legal <span class="text-red-600">*</span></label>
                        <input type="text" name="representante_legal" value="{{ old('representante_legal', $distribuidor->representante_legal) }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                    </div>

                    <div class="col-span-12 md:col-span-3">
                        <label class="block text-sm font-medium text-gray-700">CPF <span class="text-red-600">*</span></label>
                        <input type="text" name="cpf" value="{{ old('cpf', $distribuidor->cpf) }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                    </div>

                    <div class="col-span-12 md:col-span-3">
                        <label class="block text-sm font-medium text-gray-700">RG <span class="text-red-600">*</span></label>
                        <input type="text" name="rg" value="{{ old('rg', $distribuidor->rg) }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                    </div>

                    <div class="col-span-12 md:col-span-6">
                        <label class="block text-sm font-medium text-gray-700">Telefone</label>
                        <input type="text" name="telefone" value="{{ old('telefone', $distribuidor->telefone) }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div class="col-span-12 md:col-span-6">
                        <label class="block text-sm font-medium text-gray-700">Endereço Completo</label>
                        <input type="text" name="endereco_completo" value="{{ old('endereco_completo', $distribuidor->endereco_completo) }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
            </div>

            {{-- ====== Condições comerciais ====== --}}
            <div class="col-span-12">
                <h3 class="text-sm font-semibold text-gray-700">Condições comerciais</h3>
                <div class="mt-3 grid grid-cols-12 gap-4">
                    <div class="col-span-12 md:col-span-5">
                        <label class="block text-sm font-medium text-gray-700">Percentual sobre vendas <span class="text-red-600">*</span></label>
                        <div class="mt-1 flex rounded-md shadow-sm">
                            <input type="number" name="percentual_vendas" step="0.01" min="0" max="100"
                                   value="{{ old('percentual_vendas', $distribuidor->percentual_vendas) }}"
                                   class="flex-1 rounded-l-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                            <span class="inline-flex items-center rounded-r-md border border-l-0 border-gray-300 bg-gray-50 px-3 text-sm text-gray-600">%</span>
                        </div>
                    </div>

                    <div class="col-span-12 md:col-span-4">
                        <label class="block text-sm font-medium text-gray-700">Vencimento do contrato</label>
                        <input type="date" name="vencimento_contrato"
                               value="{{ old('vencimento_contrato', optional($distribuidor->vencimento_contrato)->format('Y-m-d')) }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div class="col-span-12 md:col-span-3">
                        <label class="block text-sm font-medium text-gray-700">Contrato assinado?</label>
                        <div class="mt-2 flex items-center gap-3">
                            <input type="hidden" name="contrato_assinado" value="0">
                            <input type="checkbox" name="contrato_assinado" value="1"
                                   class="rounded border-gray-300" {{ old('contrato_assinado', $distribuidor->contrato_assinado) ? 'checked' : '' }}>
                            <span class="text-sm text-gray-700">Sim</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ====== Contrato (PDF) ====== --}}
            <div class="col-span-12">
                <h3 class="text-sm font-semibold text-gray-700">Contrato</h3>
                <div class="mt-3 grid grid-cols-12 gap-4">
                    <div class="col-span-12 md:col-span-8">
                        <label class="block text-sm font-medium text-gray-700">Substituir contrato (PDF)</label>
                        <input type="file" name="contrato" accept=".pdf,application/pdf"
                               class="mt-1 block w-full rounded-md border-gray-300 text-sm file:mr-4 file:rounded-md file:border-0 file:bg-gray-100 file:px-4 file:py-2 file:text-gray-700 hover:file:bg-gray-200">
                        <p class="mt-1 text-xs text-gray-500">PDF até 2MB.</p>

                        @if($distribuidor->contrato)
                            <p class="mt-2 text-sm text-blue-600">
                                <a href="{{ Storage::url($distribuidor->contrato) }}" target="_blank" class="underline">Ver contrato atual</a>
                            </p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- ====== Vínculos (Gestor & Cidades) ====== --}}
            <div class="col-span-12">
                <h3 class="text-sm font-semibold text-gray-700">Vínculos</h3>
                <div class="mt-3 grid grid-cols-12 gap-4">
                    <div class="col-span-12 md:col-span-4">
                        <label class="block text-sm font-medium text-gray-700">Gestor</label>

                        {{-- mantém o vínculo para o submit --}}
                        <input type="hidden" id="gestor_id" name="gestor_id" value="{{ old('gestor_id', $distribuidor->gestor_id) }}">

                        <div class="mt-1 rounded-md border bg-gray-50 p-3">
                            <div class="font-medium">{{ $distribuidor->gestor?->razao_social }}</div>
                            <div class="text-sm text-gray-600">
                                UF: {{ $distribuidor->gestor?->estado_uf ?? '—' }}
                            </div>
                        </div>
                    </div>

                    <div class="col-span-12 md:col-span-8">
                        <label class="block text-sm font-medium text-gray-700">Cidades de atuação (da UF do gestor)</label>
                        <select id="cities" name="cities[]" multiple size="10"
                                class="mt-1 block w-full rounded-md border-gray-300 bg-gray-50 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            {{-- preenchido via JS --}}
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Segure Ctrl/Cmd para múltipla seleção. Cidades em cinza estão ocupadas por outro distribuidor.</p>
                    </div>
                </div>
            </div>

            {{-- ====== Ações ====== --}}
            <div class="col-span-12 flex items-center justify-end gap-3 pt-2">
                <a href="{{ route('admin.distribuidores.index') }}"
                   class="inline-flex h-10 items-center rounded-md border px-4 text-sm hover:bg-gray-50">
                    Cancelar
                </a>
                <button type="submit"
                        class="inline-flex h-10 items-center rounded-md bg-green-600 px-5 text-sm font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                    Atualizar
                </button>
            </div>
        </form>
    </div>

    {{-- JS: carrega cidades por gestor e marca ocupadas (com nome). Mantém as do próprio distribuidor habilitadas. --}}
    <script>
        const citiesSelect = document.getElementById('cities');

        async function carregarCidades(gestorId) {
            citiesSelect.innerHTML = '';
            citiesSelect.disabled = true;
            if (!gestorId) return;

            try {
            const resp = await fetch(`/admin/gestores/${gestorId}/cidades?format=json`, {
                credentials: 'same-origin',
                headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!resp.ok) throw new Error(`HTTP ${resp.status}`);
            const payload = await resp.json();
            const cidades = Array.isArray(payload) ? payload : (payload.data ?? []);

            const selected = @json($selectedCities ?? old('cities', []));

            for (const c of cidades) {
                const opt = document.createElement('option');
                opt.value = c.id;

                if (c.occupied) {
                const quem = c.distribuidor_name ? ` — ocupada por ${c.distribuidor_name}` : ' — já ocupada';
                opt.textContent = `${c.name}${quem}`;
                if (!selected.includes(String(c.id)) && !selected.includes(Number(c.id))) {
                    opt.disabled = true;
                    opt.classList.add('text-gray-500');
                } else {
                    opt.selected = true; 
                }
                } else {
                opt.textContent = c.name;
                if (selected.includes(String(c.id)) || selected.includes(Number(c.id))) {
                    opt.selected = true;
                }
                }

                citiesSelect.appendChild(opt);
            }

            citiesSelect.disabled = cidades.length === 0;
            } catch (e) {
            console.error('[carregarCidades] erro:', e);
            citiesSelect.innerHTML = '';
            citiesSelect.disabled = true;
            alert('Não foi possível carregar as cidades para o gestor selecionado.');
            }
        }

        
        document.addEventListener('DOMContentLoaded', () => {
            const hiddenGestor = document.getElementById('gestor_id'); // é o input hidden
            const gestorId = hiddenGestor?.value || '{{ $distribuidor->gestor_id }}';
            carregarCidades(gestorId);
        });
    </script>
</x-app-layout>
