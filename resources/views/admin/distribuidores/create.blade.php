<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Cadastrar Distribuidor</h2>
    </x-slot>

    <div class="max-w-6xl mx-auto p-6">
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
              class="bg-white shadow rounded-lg p-6 grid grid-cols-12 gap-4"
              x-data="formDist()" x-init="init()">
            @csrf

            {{-- ===== Gestor + Percentual ===== --}}
            <div class="col-span-12 md:col-span-6">
                <label for="gestor_id" class="block text-sm font-medium text-gray-700">
                    Gestor <span class="text-red-600">*</span>
                </label>
                <select name="gestor_id" id="gestor_id" x-model="gestorId"
                        @change="onGestorChange"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                    <option value="">-- Selecione --</option>
                    @foreach($gestores as $gestor)
                        <option value="{{ $gestor->id }}" @selected(old('gestor_id') == $gestor->id)>{{ $gestor->razao_social }}</option>
                    @endforeach
                </select>
                @error('gestor_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror

                <div class="mt-4">
                    <label for="percentual_vendas" class="block text-sm font-medium text-gray-700">
                        Percentual sobre vendas
                    </label>
                    <div class="mt-1 flex rounded-md shadow-sm">
                        <input
                            type="number"
                            id="percentual_vendas"
                            name="percentual_vendas"
                            step="0.01" min="0" max="100"
                            value="{{ old('percentual_vendas') }}"
                            class="flex-1 rounded-l-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        >
                        <span class="inline-flex items-center rounded-r-md border border-l-0 border-gray-300 bg-gray-50 px-3 text-sm text-gray-600">%</span>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">
                        Se houver um contrato/aditivo marcado como <strong>Ativo</strong>, o percentual acima será atualizado por ele.
                    </p>
                    @error('percentual_vendas') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- ===== Cidades (busca global multi-UF) ===== --}}
            <div class="col-span-12 md:col-span-6"
                 x-data="citiesPicker({
                    searchUrl: @js(route('admin.cidades.search')),
                    selectedInitial: @js(collect(old('cities', []))->map(fn($id)=>['id'=>(int)$id,'name'=>'','uf'=>''])->values()),
                 })"
                 x-init="init()"
                 @gestor-updated.window="fetchList()">
                <label class="block text-sm font-medium text-gray-700 mb-1">Cidades de atuação</label>

                <div class="flex gap-2">
                    <input type="text" x-model="q" @input="debouncedFetch()" placeholder="Buscar cidade..."
                           class="flex-1 rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 px-3 py-2">
                    @php
                        $ufs = ['','AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'];
                    @endphp
                    <select id="ufFiltroCidades" x-model="uf" @change="fetchList()"
                            class="w-28 rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        @foreach($ufs as $uf)
                            <option value="{{ $uf }}">{{ $uf === '' ? 'UF...' : $uf }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mt-2 max-h-44 overflow-auto rounded border divide-y" x-show="results.length">
                    <template x-for="item in results" :key="item.id">
                        <div class="flex items-center justify-between px-3 py-2">
                            <div class="text-sm">
                                <span x-text="item.name"></span>
                                <span class="text-gray-500" x-text="'(' + (item.uf || '') + ')'"></span>
                                <template x-if="item.occupied">
                                    <span class="ml-2 text-xs rounded px-2 py-0.5 bg-red-100 text-red-700"
                                          x-text="'ocupada' + (item.distribuidor_name ? ' por ' + item.distribuidor_name : '')"></span>
                                </template>
                            </div>
                            <button type="button"
                                    class="text-xs px-2 py-1 rounded border hover:bg-gray-50"
                                    :disabled="item.occupied || has(item.id)"
                                    @click="add(item)">
                                Adicionar
                            </button>
                        </div>
                    </template>
                </div>

                <div class="mt-3">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Selecionadas</label>
                    <div class="min-h-10 rounded border p-2 flex flex-wrap gap-2 bg-gray-50">
                        <template x-if="selected.length === 0">
                            <span class="text-xs text-gray-500">Nenhuma cidade selecionada.</span>
                        </template>
                        <template x-for="s in selected" :key="s.id">
                            <span class="inline-flex items-center text-xs rounded-full bg-blue-100 text-blue-800 px-3 py-1">
                                <span x-text="s.name + (s.uf ? ' ('+s.uf+')' : '')"></span>
                                <button type="button" class="ml-2 text-blue-700 hover:text-blue-900" @click="remove(s.id)">×</button>
                                <input type="hidden" name="cities[]" :value="s.id">
                            </span>
                        </template>
                    </div>
                </div>
                @error('cities') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- ===== Dados cadastrais ===== --}}
            <div class="col-span-12 md:col-span-6">
                <label for="razao_social" class="block text-sm font-medium text-gray-700">Razão Social <span class="text-red-600">*</span></label>
                <input type="text" id="razao_social" name="razao_social" value="{{ old('razao_social') }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                @error('razao_social') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-12 md:col-span-6">
                <label for="representante_legal" class="block text-sm font-medium text-gray-700">Representante Legal <span class="text-red-600">*</span></label>
                <input type="text" id="representante_legal" name="representante_legal" value="{{ old('representante_legal') }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                @error('representante_legal') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-12 md:col-span-6">
                <label for="cnpj" class="block text-sm font-medium text-gray-700">CNPJ <span class="text-red-600">*</span></label>
                <input type="text" id="cnpj" name="cnpj" value="{{ old('cnpj') }}" maxlength="18"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                @error('cnpj') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-12 md:col-span-3">
                <label for="cpf" class="block text-sm font-medium text-gray-700">CPF <span class="text-red-600">*</span></label>
                <input type="text" id="cpf" name="cpf" value="{{ old('cpf') }}" maxlength="14"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                @error('cpf') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-12 md:col-span-3">
                <label for="rg" class="block text-sm font-medium text-gray-700">RG </label>
                <input type="text" id="rg" name="rg" value="{{ old('rg') }}" maxlength="30"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" >
                @error('rg') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- ===== E-mails (+) ===== --}}
            <div class="col-span-12 md:col-span-6">
                <div class="flex items-center justify-between">
                    <label class="block text-sm font-medium text-gray-700">E-mails</label>
                    <button type="button" class="inline-flex h-8 items-center rounded-md border px-3 text-xs hover:bg-gray-50"
                            @click="emails.push('')">+ Adicionar</button>
                </div>
                <template x-for="(e, i) in emails" :key="i">
                    <div class="mt-2 flex gap-2">
                        <input type="email" class="flex-1 rounded-md border-gray-300"
                               :name="'emails['+i+']'" x-model="emails[i]" @input="syncLoginEmail()">
                        <button type="button" class="rounded-md border px-2 text-xs hover:bg-gray-50"
                                @click="removeEmail(i)" x-show="emails.length > 1">Remover</button>
                    </div>
                </template>
                <input type="hidden" name="email" x-model="loginEmail">
                <p class="mt-1 text-xs text-gray-500">O <b>primeiro e-mail</b> será usado para criar o usuário (login). Você pode alterar depois.</p>
                @error('emails') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                @error('emails.*') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- ===== Telefones (+) ===== --}}
            <div class="col-span-12 md:col-span-6">
                <div class="flex items-center justify-between">
                    <label class="block text-sm font-medium text-gray-700">Telefones</label>
                    <button type="button" class="inline-flex h-8 items-center rounded-md border px-3 text-xs hover:bg-gray-50"
                            @click="telefones.push('')">+ Adicionar</button>
                </div>
                <template x-for="(t, i) in telefones" :key="i">
                    <div class="mt-2 flex gap-2">
                        <input type="text" maxlength="30" class="flex-1 rounded-md border-gray-300"
                               :name="'telefones['+i+']'" x-model="telefones[i]">
                        <button type="button" class="rounded-md border px-2 text-xs hover:bg-gray-50"
                                @click="removeTelefone(i)" x-show="telefones.length > 1">Remover</button>
                    </div>
                </template>
                @error('telefones') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                @error('telefones.*') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- ===== Credenciais (senha apenas) ===== --}}
            <div class="col-span-12 md:col-span-6">
                <label for="password" class="block text-sm font-medium text-gray-700">Senha (para o usuário)</label>
                <input type="password" id="password" name="password"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- ===== Endereço principal ===== --}}
            <div class="col-span-12 md:col-span-9">
                <label for="endereco" class="block text-sm font-medium text-gray-700">Endereço</label>
                <input type="text" id="endereco" name="endereco" value="{{ old('endereco') }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('endereco') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-12 md:col-span-1">
                <label for="numero" class="block text-sm font-medium text-gray-700">Número</label>
                <input type="text" id="numero" name="numero" value="{{ old('numero') }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('numero') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-12 md:col-span-2">
                <label for="complemento" class="block text-sm font-medium text-gray-700">Complemento</label>
                <input type="text" id="complemento" name="complemento" value="{{ old('complemento') }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('complemento') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-12 md:col-span-3">
                <label for="bairro" class="block text-sm font-medium text-gray-700">Bairro</label>
                <input type="text" id="bairro" name="bairro" value="{{ old('bairro') }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('bairro') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-12 md:col-span-2">
                <label for="cidade" class="block text-sm font-medium text-gray-700">Cidade</label>
                <input type="text" id="cidade" name="cidade" value="{{ old('cidade') }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('cidade') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-12 md:col-span-1">
                <label for="uf" class="block text-sm font-medium text-gray-700">UF</label>
                <select id="uf" name="uf"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">--</option>
                    @foreach(['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'] as $uf)
                        <option value="{{ $uf }}" @selected(old('uf') === $uf)>{{ $uf }}</option>
                    @endforeach
                </select>
                @error('uf') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-12 md:col-span-4">
                <label for="cep" class="block text-sm font-medium text-gray-700">CEP</label>
                <input type="text" id="cep" name="cep" value="{{ old('cep') }}" maxlength="9"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('cep') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- ===== Endereço secundário ===== --}}
            <div class="col-span-12">
                <h3 class="text-sm font-semibold text-gray-700 mt-4 mb-2">Endereço Secundário (opcional)</h3>
            </div>
            <div class="col-span-12 md:col-span-9">
                <label for="endereco2" class="block text-sm font-medium text-gray-700">Endereço</label>
                <input type="text" id="endereco2" name="endereco2" value="{{ old('endereco2') }}"
                       class="mt-1 block w-full rounded-md border-gray-300">
                @error('endereco2') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-12 md:col-span-1">
                <label for="numero2" class="block text-sm font-medium text-gray-700">Número</label>
                <input type="text" id="numero2" name="numero2" value="{{ old('numero2') }}"
                       class="mt-1 block w-full rounded-md border-gray-300">
                @error('numero2') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-12 md:col-span-2">
                <label for="complemento2" class="block text-sm font-medium text-gray-700">Complemento</label>
                <input type="text" id="complemento2" name="complemento2" value="{{ old('complemento2') }}"
                       class="mt-1 block w-full rounded-md border-gray-300">
                @error('complemento2') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-12 md:col-span-3">
                <label for="bairro2" class="block text-sm font-medium text-gray-700">Bairro</label>
                <input type="text" id="bairro2" name="bairro2" value="{{ old('bairro2') }}"
                       class="mt-1 block w-full rounded-md border-gray-300">
                @error('bairro2') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-12 md:col-span-2">
                <label for="cidade2" class="block text-sm font-medium text-gray-700">Cidade</label>
                <input type="text" id="cidade2" name="cidade2" value="{{ old('cidade2') }}"
                       class="mt-1 block w-full rounded-md border-gray-300">
                @error('cidade2') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-12 md:col-span-1">
                <label for="uf2" class="block text-sm font-medium text-gray-700">UF</label>
                <select id="uf2" name="uf2"
                        class="mt-1 block w-full rounded-md border-gray-300">
                    <option value="">--</option>
                    @foreach(['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'] as $uf)
                        <option value="{{ $uf }}" @selected(old('uf2') === $uf)>{{ $uf }}</option>
                    @endforeach
                </select>
                @error('uf2') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-12 md:col-span-4">
                <label for="cep2" class="block text-sm font-medium text-gray-700">CEP</label>
                <input type="text" id="cep2" name="cep2" value="{{ old('cep2') }}" maxlength="9"
                       class="mt-1 block w-full rounded-md border-gray-300">
                @error('cep2') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- ===== Anexos (múltiplos) ===== --}}
            <div x-data="{ itens: [{id: Date.now()}] }" class="col-span-12">
                <label class="block text-sm font-medium text-gray-700 mb-2">Anexos (PDF)</label>

                <template x-for="(item, idx) in itens" :key="item.id">
                    <div class="grid grid-cols-12 gap-3 mb-4 p-3 rounded border"
                         x-data="anexoCidadeDist()" x-init="init()">
                        <!-- Tipo + Cidade (dinâmico) -->
                        <div class="col-span-12 md:col-span-3">
                            <label class="text-xs text-gray-600">Tipo</label>
                            <select :name="'contratos['+idx+'][tipo]'"
                                    x-model="tipo" @change="onTipoChange()"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="contrato">Contrato</option>
                                <option value="aditivo">Aditivo</option>
                                <option value="outro">Outro</option>
                                <option value="contrato_cidade">Contrato por cidade</option>
                            </select>

                            <!-- Select CIDADE (apenas quando contrato_cidade) -->
                            <div x-show="tipo === 'contrato_cidade'" class="mt-2">
                                <label class="text-xs text-gray-600">Cidade (das UFs do gestor)</label>
                                <select
                                    :name="'contratos['+idx+'][cidade_id]'"
                                    x-model="cidadeId"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    @click="refreshCidades()"
                                >
                                    <option value="" x-show="!carregando && cidades.length === 0">Selecione...</option>
                                    <option value="" x-show="carregando">Carregando...</option>
                                    <template x-for="c in cidades" :key="c.id">
                                        <option :value="c.id" x-text="c.text"></option>
                                    </template>
                                </select>
                                <p class="mt-1 text-[11px] text-gray-500">
                                    Percentual aplicado apenas para a cidade escolhida (tem prioridade nos cálculos).
                                </p>
                            </div>
                        </div>

                        <div class="col-span-12 md:col-span-5">
                            <label class="text-xs text-gray-600">Arquivo (PDF)</label>
                            <input type="file" accept="application/pdf" :name="'contratos['+idx+'][arquivo]'"
                                   class="mt-1 block w-full rounded-md border-gray-300 text-sm file:mr-4 file:rounded-md file:border-0 file:bg-gray-100 file:px-4 file:py-2 file:text-gray-700 hover:file:bg-gray-200">
                        </div>

                        <div class="col-span-12 md:col-span-2">
                            <label class="text-xs text-gray-600">Percentual</label>
                            <div class="mt-1 flex rounded-md shadow-sm">
                                <input type="number" step="0.01" min="0" max="100"
                                       :name="'contratos['+idx+'][percentual_vendas]'"
                                       class="flex-1 rounded-l-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <span class="inline-flex items-center rounded-r-md border border-l-0 border-gray-300 bg-gray-50 px-3 text-sm">%</span>
                            </div>
                            <p class="mt-1 text-[11px] text-gray-500">Se marcado <b>Ativo</b>, aplicará este percentual.</p>
                        </div>

                        <div class="col-span-12 md:col-span-2">
                            <label class="text-xs text-gray-600">Ativo?</label>
                            <div class="mt-2">
                                <label class="inline-flex items-center text-sm">
                                    <input type="checkbox" :name="'contratos['+idx+'][ativo]'" value="1" class="rounded border-gray-300">
                                    <span class="ml-2">Ativo</span>
                                </label>
                            </div>
                        </div>

                        <div class="col-span-12 md:col-span-3">
                            <label class="text-xs text-gray-600">Data de Assinatura</label>
                            <input type="date" :name="'contratos['+idx+'][data_assinatura]'"
                                   class="mt-1 block w-full rounded-md border-gray-300">
                        </div>

                        <div class="col-span-12 md:col-span-3">
                            <label class="text-xs text-gray-600">Validade (meses)</label>
                            <input type="number" min="1" max="120" step="1" :name="'contratos['+idx+'][validade_meses]'"
                                   class="mt-1 block w-full rounded-md border-gray-300">
                            <p class="mt-1 text-[11px] text-gray-500">Vencimento = Assinatura + Validade.</p>
                        </div>

                        <div class="col-span-12">
                            <input type="text" placeholder="Descrição (opcional)" :name="'contratos['+idx+'][descricao]'"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <label class="inline-flex items-center text-sm mt-2">
                                <input type="checkbox" :name="'contratos['+idx+'][assinado]'" value="1" class="rounded border-gray-300">
                                <span class="ml-2">Assinado</span>
                            </label>
                        </div>

                        <div class="col-span-12 md:col-span-2">
                            <button type="button"
                                    class="inline-flex h-9 items-center mt-1 rounded-md border px-3 text-sm hover:bg-gray-50 w-full justify-center"
                                    @click="itens.splice(idx,1)" x-show="itens.length > 1">
                                Remover
                            </button>
                        </div>
                    </div>
                </template>

                <button type="button"
                        class="inline-flex h-9 items-center rounded-md border px-3 text-sm hover:bg-gray-50"
                        @click="itens.push({id: Date.now()})">
                    + Adicionar anexo
                </button>

                @error('contratos.*.arquivo') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
                @error('contratos.*.percentual_vendas') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
                @error('contratos.*.data_assinatura') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror>
                @error('contratos.*.validade_meses') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
                @error('contratos.*.cidade_id') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
                @error('contratos.*.tipo') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- ===== Ações ===== --}}
            <div class="col-span-12 flex items-center justify-end gap-3 pt-2">
                <a href="{{ route('admin.distribuidores.index') }}"
                   class="inline-flex h-10 items-center rounded-md border px-4 text-sm hover:bg-gray-50">Cancelar</a>
                <button type="submit"
                        class="inline-flex h-10 items-center rounded-md bg-green-600 px-5 text-sm font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                    Salvar
                </button>
            </div>
        </form>
    </div>

    {{-- JS: Alpine helpers + store compartilhado (gestor → cidades) --}}
    <script>
        // debounce simples
        function debounce(fn, delay=400) {
            let t; return function(...args){ clearTimeout(t); t=setTimeout(()=>fn.apply(this,args), delay); }
        }

        // Store: cacheia cidades por gestor
        document.addEventListener('alpine:init', () => {
            Alpine.store('dist', {
                gestorId: @json(old('gestor_id', '')),
                cidadesCacheByGestor: {},

                async getCidadesOptions() {
                    const gid = String(this.gestorId || '').trim();
                    if (!gid) return [];
                    if (this.cidadesCacheByGestor[gid]) return this.cidadesCacheByGestor[gid];

                    try {
                        const url = "{{ route('admin.gestores.ufs', ['gestor' => '__ID__']) }}".replace('__ID__', gid); // só pra validar acesso; cidades vem do search
                        // não precisamos da resposta aqui; a lista de cidades por UF já vem do search global
                    } catch(e) {}

                    return [];
                }
            });
        });

        // Picker de cidades (lista geral do distribuidor)
        function citiesPicker({searchUrl, selectedInitial=[]}) {
            return {
                q: '',
                uf: '',
                results: [],
                selected: [],
                debouncedFetch: null,

                init() {
                    this.selected = (selectedInitial || []).map(s => ({id: s.id, name: s.name || '…', uf: s.uf || ''}));
                    this.debouncedFetch = debounce(this.fetchList.bind(this), 350);
                    this.fetchList();
                },
                has(id) { return this.selected.some(s => String(s.id) === String(id)); },
                add(item) {
                    if (item.occupied) return;
                    if (this.has(item.id)) return;
                    this.selected.push({id: item.id, name: item.name, uf: item.uf || ''});
                },
                remove(id) { this.selected = this.selected.filter(s => String(s.id) !== String(id)); },
                async fetchList() {
                    const params = new URLSearchParams();
                    if (this.q.trim() !== '') params.set('q', this.q.trim());
                    if (this.uf) params.set('uf', this.uf);
                    params.set('with_occupancy', '1');
                    params.set('limit', '50');

                    try {
                        const resp = await fetch(`${searchUrl}?${params.toString()}`, {
                            credentials: 'same-origin',
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                        });
                        if (!resp.ok) throw new Error('HTTP '+resp.status);
                        const rows = await resp.json();
                        this.results = rows.map(r => ({
                            id: r.id, name: r.name, uf: r.uf || r.state || '',
                            occupied: !!r.occupied,
                            distribuidor_id: r.distribuidor_id || null,
                            distribuidor_name: r.distribuidor_name || r.distribuidor_nome || null,
                        }));
                        // atualiza names das já selecionadas
                        const mapById = new Map(this.results.map(r => [String(r.id), r]));
                        this.selected = this.selected.map(s => {
                            const hit = mapById.get(String(s.id));
                            return hit ? {id: s.id, name: hit.name, uf: hit.uf || ''} : s;
                        });
                    } catch(e) {
                        console.error('[citiesPicker] fetch error:', e);
                    }
                }
            }
        }

        // Form principal do distribuidor
        function formDist() {
            return {
                emails: @json(old('emails', [''])),
                telefones: @json(old('telefones', [''])),
                loginEmail: '',
                gestorId: @json(old('gestor_id', '')),

                init() {
                    this.syncLoginEmail();
                    if (window.Alpine) Alpine.store('dist').gestorId = this.gestorId || '';
                    this.$nextTick(()=> window.dispatchEvent(new CustomEvent('gestor-updated')));

                    // ---- Filtro de UFs por gestor (endereços + picker) ----
                    setupUfFiltering();
                },

                onGestorChange: async () => {
                    if (window.Alpine) Alpine.store('dist').gestorId = document.getElementById('gestor_id').value || '';
                    window.dispatchEvent(new CustomEvent('gestor-updated'));
                    // também atualiza as UFs permitidas nas selects
                    if (typeof loadUfsForGestor === 'function') {
                        loadUfsForGestor(document.getElementById('gestor_id').value);
                    }
                },

                syncLoginEmail() { this.loginEmail = (this.emails[0] || '').trim(); },
                removeEmail(i) { this.emails.splice(i,1); if (this.emails.length===0) this.emails.push(''); this.syncLoginEmail(); },
                removeTelefone(i) { this.telefones.splice(i,1); if (this.telefones.length===0) this.telefones.push(''); },
            }
        }

        // Componente de cada "card" de anexo com cidade dinâmica
        function anexoCidadeDist() {
            return {
                tipo: 'contrato',
                cidades: [],
                cidadeId: null,
                carregando: false,

                async refreshCidades() {
                    if (this.tipo !== 'contrato_cidade') return;
                    this.carregando = true;
                    // (Se necessário, você pode buscar cidades por UF do gestor aqui)
                    this.carregando = false;
                },

                onTipoChange() { this.refreshCidades(); },

                init() {
                    this.refreshCidades();
                    window.addEventListener('gestor-updated', () => this.refreshCidades());
                }
            }
        }

        // ----------------- Filtro de UFs por Gestor (endereços + picker) -----------------
        const ufsCache = new Map(); // gestorId -> ['SP','RJ',...]

        function enableAllOptions(selectEl){
            if(!selectEl) return;
            [...selectEl.options].forEach(opt => opt.disabled = false);
        }

        function filterSelectByAllowedUFs(selectEl, allowed){
            if(!selectEl) return;
            const current = selectEl.value;
            [...selectEl.options].forEach(opt => {
                if (opt.value === '' || opt.value === '--') { opt.disabled = false; return; }
                opt.disabled = !allowed.includes(opt.value);
            });
            if (current && !allowed.includes(current)) selectEl.value = '';
        }

        function applyAllowed(allowed){
            const uf1Sel    = document.getElementById('uf');
            const uf2Sel    = document.getElementById('uf2');
            const ufCitySel = document.getElementById('ufFiltroCidades');

            filterSelectByAllowedUFs(uf1Sel, allowed);
            filterSelectByAllowedUFs(uf2Sel, allowed);
            if (ufCitySel) {
                filterSelectByAllowedUFs(ufCitySel, [''].concat(allowed));

                // Se a UF do filtro ficou inválida, limpa e força uma nova busca do componente Alpine
                if (ufCitySel.value && !allowed.includes(ufCitySel.value)) {
                    ufCitySel.value = '';
                    try {
                        const root = ufCitySel.closest('[x-data]');
                        if (root && window.Alpine) {
                            const comp = Alpine.$data(root);
                            if (comp && typeof comp.fetchList === 'function') comp.fetchList();
                            if (comp && Array.isArray(comp.selected)) {
                                comp.selected = comp.selected.filter(s => !s.uf || allowed.includes(String(s.uf).toUpperCase()));
                            }
                        }
                    } catch(e) {}
                } else {
                    // Ainda assim, saneia selecionadas fora do escopo
                    try {
                        const root = ufCitySel.closest('[x-data]');
                        if (root && window.Alpine) {
                            const comp = Alpine.$data(root);
                            if (comp && Array.isArray(comp.selected)) {
                                comp.selected = comp.selected.filter(s => !s.uf || allowed.includes(String(s.uf).toUpperCase()));
                            }
                        }
                    } catch(e) {}
                }
            }
        }

        async function loadUfsForGestor(gestorId){
            const uf1Sel    = document.getElementById('uf');
            const uf2Sel    = document.getElementById('uf2');
            const ufCitySel = document.getElementById('ufFiltroCidades');

            if (!gestorId) {
                enableAllOptions(uf1Sel); enableAllOptions(uf2Sel); if (ufCitySel) enableAllOptions(ufCitySel);
                return;
            }
            if (ufsCache.has(gestorId)) { applyAllowed(ufsCache.get(gestorId)); return; }

            const url = "{{ route('admin.gestores.ufs', ['gestor' => '__ID__']) }}".replace('__ID__', gestorId);
            try {
                const resp = await fetch(url, { headers: { 'Accept': 'application/json' } });
                if (!resp.ok) throw new Error('HTTP ' + resp.status);
                const allowed = (await resp.json()).map(u => String(u).toUpperCase());
                ufsCache.set(gestorId, allowed);
                applyAllowed(allowed);
            } catch (e) {
                console.error('Erro ao carregar UFs do gestor:', e);
                enableAllOptions(uf1Sel); enableAllOptions(uf2Sel); if (ufCitySel) enableAllOptions(ufCitySel);
            }
        }

        function setupUfFiltering(){
            const gestorSel = document.getElementById('gestor_id');
            if (!gestorSel) return;

            gestorSel.addEventListener('change', (e) => loadUfsForGestor(e.target.value));

            // Primeira carga (considera old())
            if (gestorSel.value) loadUfsForGestor(gestorSel.value);
        }
        // -------------------------------------------------------------------------------
    </script>
</x-app-layout>
