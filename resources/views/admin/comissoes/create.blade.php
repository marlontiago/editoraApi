<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Nova Comissão
        </h2>
    </x-slot>

    <div class="max-w-xl mx-auto py-6">
        <div class="bg-white shadow rounded p-6">
            <form action="{{ route('admin.comissoes.store') }}" method="POST">
                @csrf

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Usuário</label>
                    <select name="user_id" class="mt-1 block w-full border-gray-300 rounded-md" required>
                        <option value="">Selecione...</option>
                        @foreach($usuarios as $u)
                            <option value="{{ $u->id }}" {{ old('user_id') == $u->id ? 'selected' : '' }}>
                                {{ $u->name }} ({{ $u->email }})
                            </option>
                        @endforeach
                    </select>
                    @error('user_id') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Percentual (%)</label>
                    <input type="number" step="0.01" name="percentage" value="{{ old('percentage') }}"
                           class="mt-1 block w-full border-gray-300 rounded-md" required>
                    @error('percentage') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
                </div>             

                <div class="flex justify-end">
                    <a href="{{ route('admin.comissoes.index') }}" class="bg-gray-300 text-black px-4 py-2 rounded mr-2">Cancelar</a>
                    <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                        Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
