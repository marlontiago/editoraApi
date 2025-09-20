<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Editar Distribuidor</h2>
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

        {{-- ========= FORM PRINCIPAL ========= --}}
        <form action="{{ route('admin.distribuidores.update', $distribuidor) }}" method="POST" enctype="multipart/form-data"
              class="bg-white shadow rounded-lg p-6 grid grid-cols-12 gap-4">
            @csrf
            @method('PUT')

            {{-- ===== Gestor + UF/Percentual ===== --}}
            <div class="col-span-12 md:col-span-6">
                <label for="gestor_id" class="block text-sm font-medium text-gray-700">Gestor <span class="text-red-600">*</span></label>
                <select name="gestor_id" id="gestor_id"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                    <option value="">-- Selecione --</option>
                    @foreach($gestores as $gestor)
                        <option value="{{ $gestor->id }}" @selected(old('gestor_id', $distribuidor->gestor_id) == $gestor->id)>{{ $gestor->razao_social }}</option>
                    @endforeach
                </select>
                @error('gestor_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror

                <br>

                @php
                    $ufs = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'];
                @endphp
                <div class="col-span-12 md:col-span-3">
                    <label for="uf_cidades" class="block text-sm font-medium text-gray-700">UF de atuação</label>
                    <select id="uf_cidades" name="uf_cidades"
                            class="mt-1 block w-full rounded-md border-gray-300 bg-white shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">-- Selecione --</option>
                        @foreach($ufs as $uf)
                            <option value="{{ $uf }}" @selected(old('uf_cidades') === $uf)>{{ $uf }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Liste e selecione as cidades desta UF.</p>
                    @error('uf_cidades') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="col-span-12 md:col-span-5">
                    <label for="percentual_vendas" class="block text-sm font-medium text-gray-700">
                        Percentual sobre vendas
                    </label>
                    <div class="mt-1 flex rounded-md shadow-sm">
                        <input
                            type="number"
                            id="percentual_vendas"
                            name="percentual_vendas"
                            step="0.01"
                            min="0"
                            max="100"
                            value="{{ old('percentual_vendas', $distribuidor->percentual_vendas) }}"
                            class="flex-1 rounded-l-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        >
                        <span class="inline-flex items-center rounded-r-md border border-l-0 border-gray-300 bg-gray-50 px-3 text-sm text-gray-600">%</span>
                    </div>
                    @error('percentual_vendas')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- ===== Cidades ===== --}}
            <div class="col-span-12 md:col-span-6">
                <label class="block text-sm font-medium text-gray-700">Cidades de atuação</label>
                <select name="cities[]" id="cities" multiple size="10"
                        class="mt-1 block w-full rounded-md border-gray-300 bg-gray-50 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @php
                        $oldCities = collect(old('cities', $distribuidor->cities->pluck('id')->all()))->map(fn($v)=>(string)$v)->all();
                    @endphp
                    @foreach($distribuidor->cities as $city)
                        <option value="{{ $city->id }}" @selected(in_array((string)$city->id, $oldCities))>
                            {{ $city->name }} ({{ $city->uf }})
                        </option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-gray-500">Segure Ctrl/Cmd para múltipla seleção. Alterar a UF recarrega a lista.</p>
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
                <label for="rg" class="block text-sm font-medium text-gray-700">RG</label>
                <input type="text" id="rg" name="rg" value="{{ old('rg', $distribuidor->rg) }}" maxlength="30"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('rg') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-12 md:col-span-6">
                <label for="telefone" class="block text-sm font-medium text-gray-700">Telefone</label>
                <input type="text"
                    id="telefone"
                    name="telefone"
                    maxlength="20"
                    value="{{ old('telefone', $distribuidor->telefone) }}"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('telefone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- ===== Credenciais ===== --}}
            <div class="col-span-12 md:col-span-6">
                <label for="email" class="block text-sm font-medium text-gray-700">E-mail</label>
                <input type="email" id="email" name="email" value="{{ old('email', $distribuidor->user?->email) }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-12 md:col-span-6">
                <label for="password" class="block text-sm font-medium text-gray-700">Senha</label>
                <input type="password" id="password" name="password"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <p class="mt-1 text-xs text-gray-500">Preencha apenas se desejar alterar.</p>
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

            {{-- ===== Contatos ===== --}}
            @php
                $contatosInit = old('contatos');
                if ($contatosInit === null) {
                    $contatosInit = $distribuidor->contatos->map(function($c){
                        return [
                            'id'           => $c->id,
                            'nome'         => $c->nome,
                            'email'        => $c->email,
                            'telefone'     => $c->telefone,
                            'whatsapp'     => $c->whatsapp,
                            'cargo'        => $c->cargo,
                            'tipo'         => $c->tipo,
                            'preferencial' => (bool) $c->preferencial,
                            'observacoes'  => $c->observacoes,
                        ];
                    })->values()->toArray();
                }
                if (empty($contatosInit)) {
                    $contatosInit = [[
                        'id'=>null,'nome'=>'','email'=>'','telefone'=>'','whatsapp'=>'',
                        'cargo'=>'','tipo'=>'outro','preferencial'=>false,'observacoes'=>''
                    ]];
                }
            @endphp

            <div
                x-data="{
                    itens: (function(seed){ try { return Array.isArray(seed) ? seed : []; } catch(e){ return []; } })(@js($contatosInit)),
                    add(){ this.itens.push({id:null,nome:'',email:'',telefone:'',whatsapp:'',cargo:'',tipo:'outro',preferencial:false,observacoes:''}); }
                }"
                x-init="if(!Array.isArray(itens) || itens.length===0){ add() }"
                class="col-span-12"
            >
                <div class="flex items-center justify-between mb-2">
                    <label class="block text-sm font-medium text-gray-700">Contatos</label>
                    <button type="button"
                            @click.prevent="add()"
                            class="inline-flex h-8 items-center rounded-md border px-3 text-xs hover:bg-gray-50">
                        + Adicionar contato
                    </button>
                </div>

                <template x-for="(item, idx) in itens" :key="idx">
                    <div class="grid grid-cols-12 gap-3 p-3 mb-3 rounded-md border">
                        <input type="hidden" :name="`contatos[${idx}][id]`" x-model="item.id">

                        <div class="col-span-12 md:col-span-4">
                            <label class="text-xs text-gray-600">Nome <span class="text-red-600">*</span></label>
                            <input type="text" class="mt-1 block w-full rounded-md border-gray-300"
                                x-model="item.nome" :name="`contatos[${idx}][nome]`">
                        </div>

                        <div class="col-span-12 md:col-span-4">
                            <label class="text-xs text-gray-600">E-mail</label>
                            <input type="email" class="mt-1 block w-full rounded-md border-gray-300"
                                x-model="item.email" :name="`contatos[${idx}][email]`">
                        </div>

                        <div class="col-span-6 md:col-span-2">
                            <label class="text-xs text-gray-600">Telefone</label>
                            <input type="text" maxlength="30" class="mt-1 block w-full rounded-md border-gray-300"
                                x-model="item.telefone" :name="`contatos[${idx}][telefone]`">
                        </div>

                        <div class="col-span-6 md:col-span-2">
                            <label class="text-xs text-gray-600">WhatsApp</label>
                            <input type="text" maxlength="30" class="mt-1 block w-full rounded-md border-gray-300"
                                x-model="item.whatsapp" :name="`contatos[${idx}][whatsapp]`">
                        </div>

                        <div class="col-span-6 md:col-span-2">
                            <label class="text-xs text-gray-600">Cargo</label>
                            <input type="text" class="mt-1 block w-full rounded-md border-gray-300"
                                x-model="item.cargo" :name="`contatos[${idx}][cargo]`">
                        </div>

                        <div class="col-span-6 md:col-span-2">
                            <label class="text-xs text-gray-600">Tipo</label>
                            <select class="mt-1 block w-full rounded-md border-gray-300"
                                    x-model="item.tipo" :name="`contatos[${idx}][tipo]`">
                                <option value="principal">Principal</option>
                                <option value="secundario">Secundário</option>
                                <option value="financeiro">Financeiro</option>
                                <option value="comercial">Comercial</option>
                                <option value="outro">Outro</option>
                            </select>
                        </div>

                        <div class="col-span-12 md:col-span-2 flex items-center gap-2 mt-6">
                            <input type="checkbox" class="rounded border-gray-300"
                                x-model="item.preferencial" :name="`contatos[${idx}][preferencial]`" value="1">
                            <span class="text-sm">Preferencial</span>
                        </div>

                        <div class="col-span-12">
                            <label class="text-xs text-gray-600">Observações</label>
                            <textarea rows="2" class="mt-1 block w-full rounded-md border-gray-300"
                                    x-model="item.observacoes" :name="`contatos[${idx}][observacoes]`"></textarea>
                        </div>

                        <div class="col-span-12 md:col-span-2">
                            <button type="button" @click.prevent="itens.splice(idx,1)" x-show="itens.length > 1"
                                    class="inline-flex h-9 items-center mt-1 rounded-md border px-3 text-sm hover:bg-gray-50 w-full justify-center">
                                Remover
                            </button>
                        </div>
                    </div>
                </template>

                @error('contatos.*.nome')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                @error('contatos.*.email')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                @error('contatos')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            {{-- ===== Novos anexos (append) ===== --}}
            <div x-data="{ itens: [{id: Date.now()}] }" class="col-span-12">
                <label class="block text-sm font-medium text-gray-700 mb-2">Novos anexos (PDF)</label>

                <template x-for="(item, idx) in itens" :key="item.id">
                    <div class="grid grid-cols-12 gap-3 mb-4 p-3 rounded border">
                        <div class="col-span-12 md:col-span-3">
                            <label class="text-xs text-gray-600">Tipo</label>
                            <select :name="'contratos['+idx+'][tipo]'"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="contrato">Contrato</option>
                                <option value="aditivo">Aditivo</option>
                                <option value="outro">Outro</option>
                            </select>
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
                @error('contratos.*.data_assinatura') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
                @error('contratos.*.validade_meses') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
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

        {{-- ========= LISTA DE ANEXOS EXISTENTES (FORA DO FORM PRINCIPAL) ========= --}}
        @if($distribuidor->anexos->count())
            <div class="bg-white shadow rounded-lg p-6 mt-6">
                <h3 class="text-sm font-semibold mb-2">Contratos / Aditivos existentes</h3>
                <ul class="space-y-2">
                    @foreach($distribuidor->anexos as $anexo)
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
                                    <form method="POST" action="{{ route('admin.distribuidores.anexos.ativar', [$distribuidor, $anexo]) }}">
                                        @csrf
                                        <button class="text-sm px-3 py-1 rounded bg-blue-600 text-white hover:bg-blue-700">
                                            Ativar
                                        </button>
                                    </form>
                                @endunless

                                <form method="POST" action="{{ route('admin.distribuidores.anexos.destroy', [$distribuidor, $anexo]) }}"
                                      onsubmit="return confirm('Excluir este anexo?')">
                                    @csrf @method('DELETE')
                                    <button class="text-sm px-3 py-1 rounded bg-red-600 text-white hover:bg-red-700">
                                        Excluir
                                    </button>
                                </form>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    {{-- JS: carrega cidades por UF --}}
    <script>
        (function() {
            var ufSelect     = document.getElementById('uf_cidades');
            var citiesSelect = document.getElementById('cities');
            var BASE_CIDADES_UF = @json(url('/admin/cidades/por-uf'));
            var oldCities    = @json($oldCities ?? collect(old('cities', $distribuidor->cities->pluck('id')->all()))->all());

            function carregarCidadesPorUF(uf) {
                citiesSelect.innerHTML = '';
                citiesSelect.disabled  = true;
                if (!uf) return;

                fetch(BASE_CIDADES_UF + '/' + encodeURIComponent(uf) + '?with_occupancy=1', {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                }).then(function(resp) {
                    if (!resp.ok) throw new Error('HTTP ' + resp.status);
                    var ct = resp.headers.get('content-type') || '';
                    if (ct.indexOf('application/json') === -1) throw new Error('Resposta não é JSON');
                    return resp.json();
                }).then(function(payload) {
                    var cidades = Array.isArray(payload) ? payload : (payload.data || []);

                    cidades.forEach(function(c) {
                        var opt = document.createElement('option');
                        opt.value = c.id;

                        if (c.occupied && (!c.distribuidor_id || String(c.distribuidor_id) !== "{{ $distribuidor->id }}")) {
                            var quem = c.distribuidor_name ? ' — ocupada por ' + c.distribuidor_name : ' — já ocupada';
                            opt.textContent = c.name + quem;
                            opt.disabled = true;
                            opt.classList.add('text-gray-500');
                        } else {
                            opt.textContent = c.name;
                            if (oldCities.indexOf(c.id) !== -1 || oldCities.indexOf(String(c.id)) !== -1) {
                                opt.selected = true;
                            }
                        }
                        citiesSelect.appendChild(opt);
                    });
                    citiesSelect.disabled = cidades.length === 0;
                }).catch(function(e) {
                    citiesSelect.innerHTML = '';
                    citiesSelect.disabled = true;
                    console.error('[carregarCidadesPorUF] erro:', e);
                    alert('Não foi possível carregar as cidades para a UF selecionada.');
                });
            }

            if (ufSelect) {
                ufSelect.addEventListener('change', function(e) { carregarCidadesPorUF(e.target.value); });
            }
            @if (old('uf_cidades')) carregarCidadesPorUF(@json(old('uf_cidades'))); @endif
        })();
    </script>
</x-app-layout>
