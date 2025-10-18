<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Novo Cliente</h2>
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

        <form action="{{ route('admin.clientes.store') }}" method="POST"
              class="bg-white shadow rounded-lg p-6 grid grid-cols-12 gap-4">
            @csrf

            {{-- Identificação --}}
            <div class="col-span-12 md:col-span-8">
                <label for="razao_social" class="block text-sm font-medium text-gray-700">
                    Razão Social / Nome <span class="text-red-600">*</span>
                </label>
                <input type="text" id="razao_social" name="razao_social" value="{{ old('razao_social') }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                @error('razao_social') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- E-mail principal --}}
            <div class="col-span-12 md:col-span-4">
                <label for="email" class="block text-sm font-medium text-gray-700">E-mail (principal)</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" >
                @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Documentos --}}
            <div class="col-span-12 md:col-span-4">
                <label for="cnpj" class="block text-sm font-medium text-gray-700">CNPJ</label>
                <input type="text" id="cnpj" name="cnpj" value="{{ old('cnpj') }}" maxlength="18"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" >
                @error('cnpj') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-12 md:col-span-4">
                <label for="cpf" class="block text-sm font-medium text-gray-700">CPF</label>
                <input type="text" id="cpf" name="cpf" value="{{ old('cpf') }}" maxlength="14"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" >
                <p class="mt-1 text-xs text-gray-500">Informe CNPJ <u>ou</u> CPF.</p>
                @error('cpf') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-12 md:col-span-4">
                <label for="inscr_estadual" class="block text-sm font-medium text-gray-700">Inscrição Estadual</label>
                <input type="text" id="inscr_estadual" name="inscr_estadual" value="{{ old('inscr_estadual') }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('inscr_estadual') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Telefones (lista) --}}
            @php
                $telefonesSeed = old('telefones', []);
                if (empty($telefonesSeed)) $telefonesSeed = [''];
            @endphp
            <div class="col-span-12 md:col-span-6" x-data='{ lista: @json(array_values($telefonesSeed)) }'>
                <label class="block text-sm font-medium text-gray-700">Telefones</label>
                <template x-for="(tel, i) in lista" :key="i">
                    <div class="mt-1 flex gap-2">
                        <input type="text" maxlength="30"
                               class="flex-1 rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               :name="`telefones[${i}]`" x-model="lista[i]">
                        <button type="button" class="inline-flex items-center rounded-md border px-3 text-sm hover:bg-gray-50"
                                @click="lista.splice(i,1)" x-show="lista.length > 1">Remover</button>
                    </div>
                </template>
                <button type="button" class="mt-2 inline-flex h-8 items-center rounded-md border px-3 text-xs hover:bg-gray-50"
                        @click="lista.push('')">+ Adicionar telefone</button>
                @error('telefones.*') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- E-mails (lista) --}}
            @php
                $emailsSeed = old('emails', []);
                if (empty($emailsSeed)) $emailsSeed = [''];
            @endphp
            <div class="col-span-12 md:col-span-6" x-data='{ lista: @json(array_values($emailsSeed)) }'>
                <label class="block text-sm font-medium text-gray-700">E-mai</label>
                <template x-for="(em, i) in lista" :key="i">
                    <div class="mt-1 flex gap-2">
                        <input type="email" maxlength="255"
                               class="flex-1 rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               :name="`emails[${i}]`" x-model="lista[i]">
                        <button type="button" class="inline-flex items-center rounded-md border px-3 text-sm hover:bg-gray-50"
                                @click="lista.splice(i,1)" x-show="lista.length > 1">Remover</button>
                    </div>
                </template>
                <button type="button" class="mt-2 inline-flex h-8 items-center rounded-md border px-3 text-xs hover:bg-gray-50"
                        @click="lista.push('')">+ Adicionar e-mail</button>
                @error('emails.*') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Endereço principal --}}
            <div class="col-span-12 md:col-span-6">
                <label for="endereco" class="block text-sm font-medium text-gray-700">Endereço</label>
                <input type="text" id="endereco" name="endereco" value="{{ old('endereco') }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('endereco') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-12 md:col-span-3">
                <label for="numero" class="block text-sm font-medium text-gray-700">Número</label>
                <input type="text" id="numero" name="numero" value="{{ old('numero') }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('numero') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-12 md:col-span-3">
                <label for="complemento" class="block text-sm font-medium text-gray-700">Complemento</label>
                <input type="text" id="complemento" name="complemento" value="{{ old('complemento') }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('complemento') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-12 md:col-span-4">
                <label for="bairro" class="block text-sm font-medium text-gray-700">Bairro</label>
                <input type="text" id="bairro" name="bairro" value="{{ old('bairro') }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('bairro') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-12 md:col-span-5">
                <label for="cidade" class="block text-sm font-medium text-gray-700">Cidade</label>
                <input type="text" id="cidade" name="cidade" value="{{ old('cidade') }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('cidade') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-12 md:col-span-1">
                @php $ufs = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO']; @endphp
                <label for="uf" class="block text-sm font-medium text-gray-700">UF</label>
                <select id="uf" name="uf"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">--</option>
                    @foreach($ufs as $uf)
                        <option value="{{ $uf }}" @selected(old('uf') === $uf)>{{ $uf }}</option>
                    @endforeach
                </select>
                @error('uf') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-12 md:col-span-2">
                <label for="cep" class="block text-sm font-medium text-gray-700">CEP</label>
                <input type="text" id="cep" name="cep" value="{{ old('cep') }}" maxlength="9"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('cep') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Endereço secundário --}}
            <div class="col-span-12">
                <h3 class="text-sm font-semibold text-gray-700 mt-4 mb-1">Endereço secundário</h3>
            </div>
            <div class="col-span-12 md:col-span-6">
                <label for="endereco2" class="block text-sm font-medium text-gray-700">Endereço</label>
                <input type="text" id="endereco2" name="endereco2" value="{{ old('endereco2') }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('endereco2') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-12 md:col-span-3">
                <label for="numero2" class="block text-sm font-medium text-gray-700">Número</label>
                <input type="text" id="numero2" name="numero2" value="{{ old('numero2') }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('numero2') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-12 md:col-span-3">
                <label for="complemento2" class="block text-sm font-medium text-gray-700">Complemento</label>
                <input type="text" id="complemento2" name="complemento2" value="{{ old('complemento2') }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('complemento2') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-12 md:col-span-4">
                <label for="bairro2" class="block text-sm font-medium text-gray-700">Bairro</label>
                <input type="text" id="bairro2" name="bairro2" value="{{ old('bairro2') }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('bairro2') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-12 md:col-span-5">
                <label for="cidade2" class="block text-sm font-medium text-gray-700">Cidade</label>
                <input type="text" id="cidade2" name="cidade2" value="{{ old('cidade2') }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('cidade2') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-12 md:col-span-1">
                <label for="uf2" class="block text-sm font-medium text-gray-700">UF</label>
                <select id="uf2" name="uf2"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">--</option>
                    @foreach($ufs as $uf)
                        <option value="{{ $uf }}" @selected(old('uf2') === $uf)>{{ $uf }}</option>
                    @endforeach
                </select>
                @error('uf2') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-12 md:col-span-2">
                <label for="cep2" class="block text-sm font-medium text-gray-700">CEP</label>
                <input type="text" id="cep2" name="cep2" value="{{ old('cep2') }}" maxlength="9"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" >
                @error('cep2') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Ações --}}
            <div class="col-span-12 flex items-center justify-end gap-3 pt-2">
                <a href="{{ route('admin.clientes.index') }}"
                   class="inline-flex h-10 items-center rounded-md border px-4 text-sm hover:bg-gray-50">Cancelar</a>
                <button type="submit"
                        class="inline-flex h-10 items-center rounded-md bg-blue-600 px-5 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    Salvar
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
