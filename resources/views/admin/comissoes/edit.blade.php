<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Editar Comissão
        </h2>
    </x-slot>

    <div class="max-w-xl mx-auto py-6">
        <div class="bg-white shadow rounded p-6">
            <form action="{{ route('admin.comissoes.update', $commission) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Usuário</label>
                    <select name="user_id" class="mt-1 block w-full border-gray-300 rounded-md" disabled>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}" {{ $commission->user_id == $u->id ? 'selected' : '' }}>
                                {{ $u->name }} ({{ $u->email }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Percentual (%)</label>
                    <input type="number" step="0.01" name="percentage" value="{{ old('percentage', $commission->percentage) }}"
                           class="mt-1 block w-full border-gray-300 rounded-md" required>
                    @error('percentage') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
                </div>

                <div class="flex justify-end">
                    <a href="{{ route('admin.comissoes.index') }}" class="bg-gray-300 text-black px-4 py-2 rounded mr-2">Cancelar</a>
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        Atualizar
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
