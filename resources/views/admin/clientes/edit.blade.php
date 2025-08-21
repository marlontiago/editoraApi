<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Editar Cliente</h2>
    </x-slot>

    <div class="max-w-2xl mx-auto py-8">
        <form action="{{ route('admin.clientes.update', $cliente->id) }}" method="POST" class="space-y-6 bg-white p-6 rounded shadow">
            @csrf
            @method('PUT')

            <div>
                <label for="razao_social" class="block text-sm font-medium text-gray-700">Razão social</label>
                <input
                    type="text"
                    name="razao_social"
                    id="razao_social"
                    value="{{ old('razao_social', $cliente->razao_social) }}"
                    required
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                >
                @error('razao_social')
                    <span class="text-red-600 text-sm">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label for="cnpj" class="block text-sm font-medium text-gray-700">CNPJ</label>
                <input
                    type="text"
                    name="cnpj"
                    id="cnpj"
                    value="{{ old('cnpj', $cliente->cnpj) }}"
                    required
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                >
                @error('cnpj')
                    <span class="text-red-600 text-sm">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label for="cpf" class="block text-sm font-medium text-gray-700">CPF</label>
                <input
                    type="text"
                    name="cpf"
                    id="cpf"
                    value="{{ old('cpf', $cliente->cpf) }}"
                    required
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                >
                @error('cpf')
                    <span class="text-red-600 text-sm">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label for="rg" class="block text-sm font-medium text-gray-700">RG</label>
                <input
                    type="text"
                    name="rg"
                    id="rg"
                    value="{{ old('rg', $cliente->rg) }}"
                    required
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                >
                @error('rg')
                    <span class="text-red-600 text-sm">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">E-mail</label>
                <input
                    type="email"
                    name="email"
                    id="email"
                    value="{{ old('email', $cliente->email) }}"
                    required
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                >
                @error('email')
                    <span class="text-red-600 text-sm">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label for="telefone" class="block text-sm font-medium text-gray-700">Telefone</label>
                <input
                    type="text"
                    name="telefone"
                    id="telefone"
                    value="{{ old('telefone', $cliente->telefone) }}"
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                >
                @error('telefone')
                    <span class="text-red-600 text-sm">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label for="endereco_completo" class="block text-sm font-medium text-gray-700">Endereço</label>
                <input
                    type="text"
                    name="endereco_completo"
                    id="endereco_completo"
                    value="{{ old('endereco_completo', $cliente->endereco_completo) }}"
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                >
                @error('endereco_completo')
                    <span class="text-red-600 text-sm">{{ $message }}</span>
                @enderror
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                    Salvar
                </button>
                <a href="{{ route('admin.clientes.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded hover:bg-gray-200">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</x-app-layout>
