<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Clientes</h2>
    </x-slot>

    <div class="max-w-2xl mx-auto py-8">
        <form action="{{ route('admin.clientes.store') }}" method="POST" class="space-y-6 bg-white p-6 rounded shadow">
            @csrf

            <div>
                <label for="razao_social" class="block text-sm font-medium text-gray-700">Razão social</label>
                <input type="text" name="razao_social" id="razao_social" value="{{ old('razao_social') }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                @error('razao_social')
                    <span class="text-red-600 text-sm">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label for="cnpj" class="block text-sm font-medium text-gray-700">CNPJ</label>
                <input type="text" name="cnpj" id="cnpj" value="{{ old('cnpj') }}" maxlength="14" minlength="14" placeholder="00.000.000/0000-00" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                @error('cnpj')
                    <span class="text-red-600 text-sm">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label for="cpf" class="block text-sm font-medium text-gray-700">CPF</label>
                <input type="text" name="cpf" id="cpf" value="{{ old('cpf') }}" maxlength="11" minlength="11" placeholder="000.000.000-00" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                @error('cpf')
                    <span class="text-red-600 text-sm">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label for="rg" class="block text-sm font-medium text-gray-700">RG</label>
                <input type="text" name="rg" id="rg" value="{{ old('rg') }}" maxlength="10" minlength="7" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                @error('rg')
                    <span class="text-red-600 text-sm">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">E-mail</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" placeholder="email@example.com" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                @error('email')
                    <span class="text-red-600 text-sm">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label for="telefone" class="block text-sm font-medium text-gray-700">Telefone</label>
                <input type="text" name="telefone" id="telefone" value="{{ old('telefone') }}" maxlength="11" minlength="10" placeholder="(00)00000-0000" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                @error('telefone')
                    <span class="text-red-600 text-sm">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label for="endereco_completo" class="block text-sm font-medium text-gray-700">Endereço</label>
                <input type="text" name="endereco_completo" id="endereco_completo" value="{{ old('endereco_completo') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                @error('endereco_completo')
                    <span class="text-red-600 text-sm">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Salvar</button>
            </div>
        </form>
    </div>
    </x-app-layout>
