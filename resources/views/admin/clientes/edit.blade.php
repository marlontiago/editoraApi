<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Editar Cliente</h2>
    </x-slot>

    <div class="max-w-3xl mx-auto py-8">
        <form action="{{ route('admin.clientes.update', $cliente->id) }}" method="POST" class="space-y-6 bg-white p-6 rounded shadow">
            @csrf
            @method('PUT')

            {{-- Identificação --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="razao_social" class="block text-sm font-medium text-gray-700">Razão Social / Nome</label>
                    <input type="text" name="razao_social" id="razao_social" value="{{ old('razao_social', $cliente->razao_social) }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    @error('razao_social') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">E-mail</label>
                    <input type="email" name="email" id="email" value="{{ old('email', $cliente->email) }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    @error('email') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>
            </div>

            {{-- Documentos --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="cnpj" class="block text-sm font-medium text-gray-700">CNPJ</label>
                    <input type="text" name="cnpj" id="cnpj" value="{{ old('cnpj', $cliente->cnpj) }}" placeholder="00.000.000/0000-00" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    @error('cnpj') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label for="cpf" class="block text-sm font-medium text-gray-700">CPF</label>
                    <input type="text" name="cpf" id="cpf" value="{{ old('cpf', $cliente->cpf) }}" placeholder="000.000.000-00" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    @error('cpf') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    <p class="text-xs text-gray-500 mt-1">Informe CNPJ <u>ou</u> CPF.</p>
                </div>
                <div>
                    <label for="inscr_estadual" class="block text-sm font-medium text-gray-700">Inscrição Estadual</label>
                    <input type="text" name="inscr_estadual" id="inscr_estadual" value="{{ old('inscr_estadual', $cliente->inscr_estadual) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    @error('inscr_estadual') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>
            </div>

            {{-- Contato --}}
            <div>
                <label for="telefone" class="block text-sm font-medium text-gray-700">Telefone</label>
                <input type="text" name="telefone" id="telefone" value="{{ old('telefone', $cliente->telefone) }}" placeholder="(00) 00000-0000" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                @error('telefone') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
            </div>

            {{-- Endereço --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-2">
                    <label for="endereco" class="block text-sm font-medium text-gray-700">Endereço</label>
                    <input type="text" name="endereco" id="endereco" value="{{ old('endereco', $cliente->endereco) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    @error('endereco') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label for="numero" class="block text-sm font-medium text-gray-700">Número</label>
                    <input type="text" name="numero" id="numero" value="{{ old('numero', $cliente->numero) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    @error('numero') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="complemento" class="block text-sm font-medium text-gray-700">Complemento</label>
                    <input type="text" name="complemento" id="complemento" value="{{ old('complemento', $cliente->complemento) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    @error('complemento') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label for="bairro" class="block text-sm font-medium text-gray-700">Bairro</label>
                    <input type="text" name="bairro" id="bairro" value="{{ old('bairro', $cliente->bairro) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    @error('bairro') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label for="cidade" class="block text-sm font-medium text-gray-700">Cidade</label>
                    <input type="text" name="cidade" id="cidade" value="{{ old('cidade', $cliente->cidade) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    @error('cidade') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="uf" class="block text-sm font-medium text-gray-700">UF</label>
                    <input type="text" name="uf" id="uf" value="{{ old('uf', $cliente->uf) }}" maxlength="2" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm uppercase">
                    @error('uf') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label for="cep" class="block text-sm font-medium text-gray-700">CEP</label>
                    <input type="text" name="cep" id="cep" value="{{ old('cep', $cliente->cep) }}" placeholder="00000-000" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    @error('cep') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full md:w-auto px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Atualizar</button>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>
