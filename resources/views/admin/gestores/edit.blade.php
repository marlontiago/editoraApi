{{-- resources/views/admin/gestores/edit.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Editar Gestor</h2>
    </x-slot>

    {{-- Evita "piscar" de elementos controlados pelo Alpine --}}
    <style>[x-cloak]{display:none !important}</style>

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

        <form method="POST" action="{{ route('admin.gestores.update', ['gestor' => $gestor->id]) }}" enctype="multipart/form-data"
              class="bg-white shadow rounded-lg p-6 grid grid-cols-12 gap-4"
              x-data>
            @csrf
            @method('PUT')

            {{-- Razão Social --}}
            <div class="col-span-12 md:col-span-8">
                <label for="razao_social" class="block text-sm font-medium text-gray-700">
                    Razão Social <span class="text-red-600">*</span>
                </label>
                <input type="text" id="razao_social" name="razao_social" value="{{ old('razao_social', $gestor->razao_social) }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" >
                @error('razao_social') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- CNPJ --}}
            <div class="col-span-12 md:col-span-4">
                <label for="cnpj" class="block text-sm font-medium text-gray-700">
                    CNPJ <span class="text-red-600">*</span>
                </label>
                <input type="text" id="cnpj" name="cnpj" value="{{ old('cnpj', $gestor->cnpj) }}" maxlength="18"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" >
                @error('cnpj') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Representante Legal --}}
            <div class="col-span-12 md:col-span-6">
                <label for="representante_legal" class="block text-sm font-medium text-gray-700">
                    Representante Legal <span class="text-red-600">*</span>
                </label>
                <input type="text" id="representante_legal" name="representante_legal" value="{{ old('representante_legal', $gestor->representante_legal) }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" >
                @error('representante_legal') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- CPF --}}
            <div class="col-span-12 md:col-span-3">
                <label for="cpf" class="block text-sm font-medium text-gray-700">
                    CPF <span class="text-red-600">*</span>
                </label>
                <input type="text" id="cpf" name="cpf" value="{{ old('cpf', $gestor->cpf) }}" maxlength="14"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" >
                @error('cpf') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- RG --}}
            <div class="col-span-12 md:col-span-3">
                <label for="rg" class="block text-sm font-medium text-gray-700">RG</label>
                <input type="text" id="rg" name="rg" value="{{ old('rg', $gestor->rg) }}" maxlength="30"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('rg') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- TELEFONES (lista com +) --}}
            @php
                $telsInit = old('telefones');
                if ($telsInit === null) {
                    $telsInit = $gestor->telefones ?: [];
                    if (empty($telsInit) && !empty($gestor->telefone)) $telsInit = [$gestor->telefone];
                }
                if (empty($telsInit)) $telsInit = [''];
            @endphp
            <div class="col-span-12 md:col-span-6" x-data='{ lista: @json(array_values($telsInit)) }'>
                <label class="block text-sm font-medium text-gray-700">Telefones</label>
                <template x-for="(tel, i) in lista" :key="i">
                    <div class="mt-1 flex gap-2">
                        <input type="text" maxlength="20"
                               class="flex-1 rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               :name="`telefones[${i}]`" x-model="lista[i]">
                        <button type="button" class="inline-flex items-center rounded-md border px-3 text-sm hover:bg-gray-50"
                                @click="lista.splice(i,1)" x-show="lista.length > 1">
                            Remover
                        </button>
                    </div>
                </template>
                <button type="button" class="mt-2 inline-flex h-8 items-center rounded-md border px-3 text-xs hover:bg-gray-50"
                        @click="lista.push('')">+ Adicionar telefone</button>
                @error('telefones.*') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- EMAILS (lista com +) --}}
            @php
                $emailsInit = old('emails');
                if ($emailsInit === null) {
                    $emailsInit = $gestor->emails ?: [];
                    if (empty($emailsInit) && !empty($gestor->email)) $emailsInit = [$gestor->email];
                }
                if (empty($emailsInit)) $emailsInit = [''];
            @endphp
            <div class="col-span-12 md:col-span-6" x-data='{ lista: @json(array_values($emailsInit)) }'>
                <label class="block text-sm font-medium text-gray-700">E-mails</label>
                <template x-for="(em, i) in lista" :key="i">
                    <div class="mt-1 flex gap-2">
                        <input type="email" maxlength="255"
                               class="flex-1 rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               :name="`emails[${i}]`" x-model="lista[i]">
                        <button type="button" class="inline-flex items-center rounded-md border px-3 text-sm hover:bg-gray-50"
                                @click="lista.splice(i,1)" x-show="lista.length > 1">
                            Remover
                        </button>
                    </div>
                </template>
                <button type="button" class="mt-2 inline-flex h-8 items-center rounded-md border px-3 text-xs hover:bg-gray-50"
                        @click="lista.push('')">+ Adicionar e-mail</button>
                <p class="mt-1 text-xs text-gray-500">O <b>primeiro e-mail</b> será usado para criar o usuário (login). Você pode alterar depois.</p>
                @error('emails.*') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Senha (opcional) --}}
            <div class="col-span-12 md:col-span-4">
                <label for="password" class="block text-sm font-medium text-gray-700">Senha</label>
                <input type="password" id="password" name="password"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <p class="mt-1 text-xs text-gray-500">Informe para trocar a senha. Deixe vazio para manter.</p>
                @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- UFs de atuação (MÚLTIPLAS) --}}
            @php
                $selecionadas = old('estados_uf', isset($gestor) ? $gestor->ufs->pluck('uf')->all() : []);
                $ocupadas = $ufOcupadas ?? [];
            @endphp

            <div class="col-span-12">
                <label class="block text-sm font-medium text-gray-700">UF(s) de Atuação</label>
                <div class="mt-2 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2">
                    @foreach($ufs as $uf)
                        @php
                            $isOcupada = array_key_exists($uf, $ocupadas);
                            $checked   = in_array($uf, $selecionadas);
                        @endphp
                        <label class="inline-flex items-center gap-2 text-sm {{ $isOcupada ? 'text-gray-400' : '' }}">
                            <input
                                type="checkbox"
                                name="estados_uf[]"
                                value="{{ $uf }}"
                                class="rounded border-gray-300"
                                @checked($checked)
                                @disabled($isOcupada)
                                @change="$store.gestor.toggleUf('{{$uf}}', $event.target.checked)"
                            >
                            <span>
                                {{ $uf }}
                                @if($isOcupada)
                                    — ocupada por {{ $ocupadas[$uf] }}
                                @endif
                            </span>
                        </label>
                    @endforeach
                </div>
                @error('estados_uf') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                @error('estados_uf.*') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Percentual sobre vendas --}}
            <div class="col-span-12 md:col-span-5">
                <label for="percentual_vendas" class="block text-sm font-medium text-gray-700">Percentual sobre vendas</label>
                <div class="mt-1 flex rounded-md shadow-sm">
                    <input type="number" step="0.01" min="0" max="100" id="percentual_vendas" name="percentual_vendas"
                           value="{{ old('percentual_vendas', $gestor->percentual_vendas) }}"
                           class="flex-1 rounded-l-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <span class="inline-flex items-center rounded-r-md border border-l-0 border-gray-300 bg-gray-50 px-3 text-sm text-gray-600">%</span>
                </div>
                <p class="mt-1 text-xs text-gray-500">
                    Se houver um contrato/aditivo <b>Ativo</b>, o percentual será atualizado por ele.
                </p>
                @error('percentual_vendas') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Endereço principal --}}
            <div class="col-span-12 md:col-span-6">
                <label for="endereco" class="block text-sm font-medium text-gray-700">Endereço</label>
                <input type="text" id="endereco" name="endereco" value="{{ old('endereco', $gestor->endereco) }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('endereco') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-12 md:col-span-3">
                <label for="numero" class="block text-sm font-medium text-gray-700">Número</label>
                <input type="text" id="numero" name="numero" value="{{ old('numero', $gestor->numero) }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('numero') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-12 md:col-span-3">
                <label for="complemento" class="block text-sm font-medium text-gray-700">Complemento</label>
                <input type="text" id="complemento" name="complemento" value="{{ old('complemento', $gestor->complemento) }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('complemento') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-12 md:col-span-4">
                <label for="bairro" class="block text-sm font-medium text-gray-700">Bairro</label>
                <input type="text" id="bairro" name="bairro" value="{{ old('bairro', $gestor->bairro) }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('bairro') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-12 md:col-span-5">
                <label for="cidade" class="block text-sm font-medium text-gray-700">Cidade</label>
                <input type="text" id="cidade" name="cidade" value="{{ old('cidade', $gestor->cidade) }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('cidade') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-12 md:col-span-1">
                <label for="uf" class="block text-sm font-medium text-gray-700">UF</label>
                <select id="uf" name="uf"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">--</option>
                    @foreach($ufs as $uf)
                        <option value="{{ $uf }}" @selected(old('uf', $gestor->uf) === $uf)>{{ $uf }}</option>
                    @endforeach
                </select>
                @error('uf') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-12 md:col-span-2">
                <label for="cep" class="block text-sm font-medium text-gray-700">CEP</label>
                <input type="text" id="cep" name="cep" value="{{ old('cep', $gestor->cep) }}" maxlength="9"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('cep') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Endereço secundário --}}
            <div class="col-span-12">
                <h3 class="text-sm font-semibold text-gray-700 mt-4 mb-1">Endereço secundário</h3>
            </div>
            <div class="col-span-12 md:col-span-6">
                <label for="endereco2" class="block text-sm font-medium text-gray-700">Endereço</label>
                <input type="text" id="endereco2" name="endereco2" value="{{ old('endereco2', $gestor->endereco2) }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('endereco2') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-12 md:col-span-3">
                <label for="numero2" class="block text-sm font-medium text-gray-700">Número</label>
                <input type="text" id="numero2" name="numero2" value="{{ old('numero2', $gestor->numero2) }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('numero2') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-12 md:col-span-3">
                <label for="complemento2" class="block text-sm font-medium text-gray-700">Complemento</label>
                <input type="text" id="complemento2" name="complemento2" value="{{ old('complemento2', $gestor->complemento2) }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('complemento2') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-12 md:col-span-4">
                <label for="bairro2" class="block text-sm font-medium text-gray-700">Bairro</label>
                <input type="text" id="bairro2" name="bairro2" value="{{ old('bairro2', $gestor->bairro2) }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('bairro2') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-12 md:col-span-5">
                <label for="cidade2" class="block text-sm font-medium text-gray-700">Cidade</label>
                <input type="text" id="cidade2" name="cidade2" value="{{ old('cidade2', $gestor->cidade2) }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('cidade2') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-12 md:col-span-1">
                <label for="uf2" class="block text-sm font-medium text-gray-700">UF</label>
                <select id="uf2" name="uf2"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">--</option>
                    @foreach($ufs as $uf)
                        <option value="{{ $uf }}" @selected(old('uf2', $gestor->uf2) === $uf)>{{ $uf }}</option>
                    @endforeach
                </select>
                @error('uf2') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-12 md:col-span-2">
                <label for="cep2" class="block text-sm font-medium text-gray-700">CEP</label>
                <input type="text" id="cep2" name="cep2" value="{{ old('cep2', $gestor->cep2) }}" maxlength="9"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('cep2') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- ================= ANEXOS (NOVOS) ================= --}}
            <div x-data="{ itens: [{id: Date.now()}] }" class="col-span-12">
                <label class="block text-sm font-medium text-gray-700 mb-2">Adicionar novos anexos (PDF)</label>

                <template x-for="(item, idx) in itens" :key="item.id">
                    {{-- x-data no CARD inteiro para compartilhar "tipo" com as colunas --}}
                    <div x-data="anexoCidade()" class="grid grid-cols-12 gap-3 mb-4 p-3 rounded border">
                        <!-- Tipo + Cidade (dinâmico) -->
                        <div class="col-span-12 md:col-span-3">
                            <label class="text-xs text-gray-600">Tipo</label>
                            <select :name="`contratos[${idx}][tipo]`"
                                    x-model="tipo"
                                    @change="onTipoChange()"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="contrato">Contrato</option>
                                <option value="aditivo">Aditivo</option>
                                <option value="outro">Outro</option>
                                <option value="contrato_cidade">Contrato por cidade</option>
                            </select>

                            <div x-show="tipo === 'contrato_cidade'" class="mt-2" x-cloak>
                                <label class="text-xs text-gray-600">Cidade (das UFs selecionadas)</label>
                                <select :name="`contratos[${idx}][cidade_id]`"
                                        x-model="cidadeId"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        @click="refreshCidades()">
                                    <option value="" x-show="!carregando && cidades.length === 0">Selecione...</option>
                                    <option value="" x-show="carregando">Carregando...</option>
                                    <template x-for="c in cidades" :key="c.id">
                                        <option :value="c.id" x-text="c.text"></option>
                                    </template>
                                </select>
                                <p class="mt-1 text-[11px] text-gray-500">Aplica-se apenas à cidade escolhida; tem prioridade sobre o contrato ativo.</p>
                            </div>
                        </div>

                        <div class="col-span-12 md:col-span-5">
                            <label class="text-xs text-gray-600">Arquivo (PDF)</label>
                            <input type="file" accept="application/pdf" :name="`contratos[${idx}][arquivo]`"
                                   class="mt-1 block w-full rounded-md border-gray-300 text-sm file:mr-4 file:rounded-md file:border-0 file:bg-gray-100 file:px-4 file:py-2 file:text-gray-700 hover:file:bg-gray-200">
                        </div>

                        <div class="col-span-12 md:col-span-2">
                            <label class="text-xs text-gray-600">Percentual</label>
                            <div class="mt-1 flex rounded-md shadow-sm">
                                <input type="number" step="0.01" min="0" max="100"
                                       :name="`contratos[${idx}][percentual_vendas]`"
                                       class="flex-1 rounded-l-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <span class="inline-flex items-center rounded-r-md border border-l-0 border-gray-300 bg-gray-50 px-3 text-sm">%</span>
                            </div>
                            <p class="mt-1 text-[11px] text-gray-500">Se marcado como <b>Ativo</b>, este percentual será aplicado ao Gestor.</p>
                        </div>

                        {{-- Esconde e não envia "Ativo?" quando for contrato por cidade --}}
                        <div class="col-span-12 md:col-span-2" x-show="tipo !== 'contrato_cidade'" x-cloak>
                            <label class="text-xs text-gray-600">Ativo?</label>
                            <div class="mt-2">
                                <label class="inline-flex items-center text-sm">
                                    <input
                                        type="checkbox"
                                        :name="tipo !== 'contrato_cidade' ? `contratos[${idx}][ativo]` : null"
                                        :disabled="tipo === 'contrato_cidade'"
                                        value="1"
                                        class="rounded border-gray-300">
                                    <span class="ml-2">Ativo</span>
                                </label>
                            </div>
                        </div>

                        <div class="col-span-12 md:col-span-3">
                            <label class="text-xs text-gray-600">Data de Assinatura</label>
                            <input type="date" :name="`contratos[${idx}][data_assinatura]`"
                                   class="mt-1 block w-full rounded-md border-gray-300">
                        </div>

                        <div class="col-span-12 md:col-span-3">
                            <label class="text-xs text-gray-600">Validade (meses)</label>
                            <input type="number" min="1" max="120" step="1" :name="`contratos[${idx}][validade_meses]`"
                                   class="mt-1 block w-full rounded-md border-gray-300">
                            <p class="mt-1 text-[11px] text-gray-500">Vencimento = Assinatura + Validade.</p>
                        </div>

                        <div class="col-span-12">
                            <input type="text" placeholder="Descrição (opcional)" :name="`contratos[${idx}][descricao]`"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <label class="inline-flex items-center text-sm mt-2">
                                <input type="checkbox" :name="`contratos[${idx}][assinado]`" value="1" class="rounded border-gray-300">
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
                @error('contratos.*.data_assinatura') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
                @error('contratos.*.validade_meses') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
                @error('contratos.*.cidade_id') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
                @error('contratos.*.tipo') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- LISTA DE ANEXOS EXISTENTES --}}
            @if($gestor->anexos->count())
                <div class="col-span-12">
                    <h3 class="text-sm font-semibold mb-2">Contratos / Aditivos existentes</h3>
                    <ul class="space-y-2">
                        @foreach($gestor->anexos as $anexo)
                            <li class="border rounded p-3 flex items-center justify-between">
                                <div class="text-sm">
                                    <div>
                                        <strong>{{ strtoupper($anexo->tipo) }}</strong>
                                        @if($anexo->assinado)
                                            <span class="ml-2 px-2 py-0.5 text-xs rounded bg-green-100 text-green-700">Assinado</span>
                                        @endif
                                        @if($anexo->ativo)
                                            <span class="ml-2 px-2 py-0.5 text-xs rounded bg-blue-100 text-blue-700">Ativo</span>
                                        @endif
                                    </div>

                                    @if(!is_null($anexo->percentual_vendas))
                                        <div>Percentual desse contrato: <strong>{{ number_format($anexo->percentual_vendas, 2, ',', '.') }}%</strong></div>
                                    @endif

                                    @if($anexo->data_assinatura)
                                        <div>Assinado em: {{ \Carbon\Carbon::parse($anexo->data_assinatura)->format('d/m/Y') }}</div>
                                    @endif

                                    @if($anexo->data_vencimento)
                                        <div>Vence em: {{ \Carbon\Carbon::parse($anexo->data_vencimento)->format('d/m/Y') }}</div>
                                    @endif

                                    @if($anexo->descricao)
                                        <div class="text-gray-600">{{ $anexo->descricao }}</div>
                                    @endif
                                </div>

                                <div class="flex items-center gap-2">
                                    @if($anexo->arquivo)
                                        <a href="{{ Storage::disk('public')->url($anexo->arquivo) }}" target="_blank"
                                           class="text-blue-600 text-sm underline">Abrir</a>
                                    @endif

                                    @unless($anexo->ativo)
                                        <button type="button"
                                            class="text-sm px-3 py-1 rounded bg-blue-600 text-white hover:bg-blue-700"
                                            onclick="document.getElementById('form-ativar-{{ $anexo->id }}').submit()">
                                            Ativar
                                        </button>
                                    @endunless

                                    <button type="button"
                                        class="text-sm px-3 py-1 rounded bg-red-600 text-white hover:bg-red-700"
                                        onclick="if(confirm('Excluir este anexo?')) document.getElementById('form-excluir-{{ $anexo->id }}').submit()">
                                        Excluir
                                    </button>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Ações --}}
            <div class="col-span-12 flex items-center justify-end gap-3 pt-2">
                <a href="{{ route('admin.gestores.index') }}"
                   class="inline-flex h-10 items-center rounded-md border px-4 text-sm hover:bg-gray-50">
                    Cancelar
                </a>
                <button type="submit"
                        class="inline-flex h-10 items-center rounded-md bg-green-600 px-5 text-sm font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                    Salvar alterações
                </button>
            </div>
        </form>

        {{-- ===== Forms ocultos para ATIVAR/EXCLUIR anexos (fora do form principal!) ===== --}}
        @if($gestor->anexos->count())
            @foreach($gestor->anexos as $anexo)
                <form id="form-ativar-{{ $anexo->id }}" method="POST" action="{{ route('admin.gestores.anexos.ativar', [$gestor, $anexo]) }}" class="hidden">
                    @csrf
                </form>
                <form id="form-excluir-{{ $anexo->id }}" method="POST" action="{{ route('admin.gestores.anexos.destroy', [$gestor, $anexo]) }}" class="hidden">
                    @csrf
                    @method('DELETE')
                </form>
            @endforeach
        @endif
        {{-- ============================================================================ --}}
    </div>

    {{-- Alpine store + componentes auxiliares --}}
    <script>
        document.addEventListener('alpine:init', () => {
            // Store compartilhado
            Alpine.store('gestor', {
                ufsSelecionadas: @json($selecionadas),
                cidadesCache: {},
                async getCidadesOptions() {
                    const key = this.ufsSelecionadas.slice().sort().join(',');
                    if (!key) return [];
                    if (this.cidadesCache[key]) return this.cidadesCache[key];

                    try {
                        const url = "{{ route('admin.utils.cidades-por-ufs') }}" + "?ufs=" + encodeURIComponent(key);
                        const res = await fetch(url, { headers: { 'Accept': 'application/json' }});
                        const data = await res.json();
                        this.cidadesCache[key] = Array.isArray(data) ? data : [];
                        return this.cidadesCache[key];
                    } catch (e) {
                        console.error('Erro ao carregar cidades', e);
                        return [];
                    }
                },
                toggleUf(uf, checked) {
                    const set = new Set(this.ufsSelecionadas);
                    if (checked) set.add(uf); else set.delete(uf);
                    this.ufsSelecionadas = Array.from(set);
                    window.dispatchEvent(new Event('ufs-updated'));
                }
            });

            // Componente por card
            Alpine.data('anexoCidade', () => ({
                tipo: 'contrato',
                cidades: [],
                cidadeId: null,
                carregando: false,

                async refreshCidades() {
                    if (this.tipo !== 'contrato_cidade') return;
                    this.carregando = true;
                    this.cidades = await Alpine.store('gestor').getCidadesOptions();
                    if (!this.cidades.some(c => String(c.id) === String(this.cidadeId))) {
                        this.cidadeId = null;
                    }
                    this.carregando = false;
                },

                onTipoChange() {
                    this.refreshCidades();
                },

                init() {
                    this.refreshCidades();
                    window.addEventListener('ufs-updated', () => this.refreshCidades());
                    this.$watch(() => Alpine.store('gestor').ufsSelecionadas.slice().join(','), () => {
                        this.refreshCidades();
                    });
                }
            }));
        });
    </script>
</x-app-layout>
