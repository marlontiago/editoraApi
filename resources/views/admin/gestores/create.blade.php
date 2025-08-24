<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Cadastrar Gestor</h2>
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

        <form method="POST" action="{{ route('admin.gestores.store') }}" enctype="multipart/form-data"
              class="bg-white shadow rounded-lg p-6 grid grid-cols-12 gap-4">
            @csrf

            {{-- Razão Social --}}
            <div class="col-span-12 md:col-span-8">
                <label for="razao_social" class="block text-sm font-medium text-gray-700">Razão Social <span class="text-red-600">*</span></label>
                <input type="text" id="razao_social" name="razao_social" value="{{ old('razao_social') }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                @error('razao_social') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- CNPJ --}}
            <div class="col-span-12 md:col-span-4">
                <label for="cnpj" class="block text-sm font-medium text-gray-700">CNPJ <span class="text-red-600">*</span></label>
                <input type="text" id="cnpj" name="cnpj" value="{{ old('cnpj') }}" maxlength="14" minlength="14" placeholder="00.000.000/0000-00"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                @error('cnpj') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Representante Legal --}}
            <div class="col-span-12 md:col-span-6">
                <label for="representante_legal" class="block text-sm font-medium text-gray-700">Representante Legal <span class="text-red-600">*</span></label>
                <input type="text" id="representante_legal" name="representante_legal" value="{{ old('representante_legal') }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                @error('representante_legal') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- CPF --}}
            <div class="col-span-12 md:col-span-3">
                <label for="cpf" class="block text-sm font-medium text-gray-700">CPF <span class="text-red-600">*</span></label>
                <input type="text" id="cpf" name="cpf" value="{{ old('cpf') }}" minlength="11" maxlength="11" placeholder="000.000.000-00"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                @error('cpf') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- RG --}}
            <div class="col-span-12 md:col-span-3">
                <label for="rg" class="block text-sm font-medium text-gray-700">RG</label>
                <input type="text" id="rg" name="rg" value="{{ old('rg') }}" minlength="7" maxlength="10"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('rg') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Telefone --}}
            <div class="col-span-12 md:col-span-4">
                <label for="telefone" class="block text-sm font-medium text-gray-700">Telefone <span class="text-red-600">*</span></label>
                <input type="text" id="telefone" name="telefone" value="{{ old('telefone') }}" minlength="10" maxlength="11"placeholder="(00)00000-0000"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                @error('telefone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- E-mail --}}
            <div class="col-span-12 md:col-span-8">
                <label for="email" class="block text-sm font-medium text-gray-700">E-mail <span class="text-red-600">*</span></label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="email@example.com"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Senha --}}
            <div class="col-span-12 md:col-span-4">
                <label for="password" class="block text-sm font-medium text-gray-700">Senha <span class="text-red-600">*</span></label>
                <input type="password" id="password" name="password"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Endereço completo --}}
            <div class="col-span-12">
                <label for="endereco_completo" class="block text-sm font-medium text-gray-700">Endereço Completo</label>
                <input type="text" id="endereco_completo" name="endereco_completo" value="{{ old('endereco_completo') }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('endereco_completo') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- UF do Gestor --}}
            @php
                $ufs = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'];
                $ufOld = old('estado_uf', $gestor->estado_uf ?? null);
            @endphp
            <div class="col-span-12 md:col-span-3">
                <label for="estado_uf" class="block text-sm font-medium text-gray-700">UF</label>
                <select id="estado_uf" name="estado_uf"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">-- Selecione --</option>
                    @foreach($ufs as $uf)
                        <option value="{{ $uf }}" @selected($ufOld === $uf)>{{ $uf }}</option>
                    @endforeach
                </select>
                @error('estado_uf') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Percentual sobre vendas --}}
            <div class="col-span-12 md:col-span-5">
                <label for="percentual_vendas" class="block text-sm font-medium text-gray-700">Percentual sobre vendas</label>
                <div class="mt-1 flex rounded-md shadow-sm">
                    <input type="number" step="0.01" min="0" max="100" id="percentual_vendas" name="percentual_vendas"
                           value="{{ old('percentual_vendas') }}"
                           class="flex-1 rounded-l-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                    <span class="inline-flex items-center rounded-r-md border border-l-0 border-gray-300 bg-gray-50 px-3 text-sm text-gray-600">%</span>
                </div>
                @error('percentual_vendas') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Vencimento do contrato --}}
            <div class="col-span-12 md:col-span-4">
                <label for="vencimento_contrato" class="block text-sm font-medium text-gray-700">Vencimento do contrato</label>
                <input type="date" id="vencimento_contrato" name="vencimento_contrato" value="{{ old('vencimento_contrato') }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('vencimento_contrato') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Contrato assinado --}}
            <div class="col-span-12 md:col-span-5">
                <label class="block text-sm font-medium text-gray-700">Contrato assinado</label>
                <div class="mt-2 flex items-center gap-3">
                    <input type="hidden" name="contrato_assinado" value="0"/>
                    <input type="checkbox" id="contrato_assinado" name="contrato_assinado" value="1" {{ old('contrato_assinado') ? 'checked' : '' }}>
                    <span class="text-sm text-gray-700">Sim</span>
                </div>
            </div>

            {{-- Anexar contrato (PDF) --}}
            <div class="col-span-12 md:col-span-7">
                <label for="contrato" class="block text-sm font-medium text-gray-700">Anexar contrato (PDF)</label>
                <input type="file" id="contrato" name="contrato" accept="application/pdf"
                       class="mt-1 block w-full rounded-md border-gray-300 text-sm file:mr-4 file:rounded-md file:border-0 file:bg-gray-100 file:px-4 file:py-2 file:text-gray-700 hover:file:bg-gray-200">
                <p class="mt-1 text-xs text-gray-500">PDF até 5MB.</p>
                @error('contrato') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Ações --}}
            <div class="col-span-12 flex items-center justify-end gap-3 pt-2">
                <a href="{{ route('admin.gestores.index') }}"
                   class="inline-flex h-10 items-center rounded-md border px-4 text-sm hover:bg-gray-50">
                    Cancelar
                </a>
                <button type="submit"
                        class="inline-flex h-10 items-center rounded-md bg-green-600 px-5 text-sm font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                    Salvar
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
