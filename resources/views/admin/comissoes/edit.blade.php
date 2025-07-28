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
                    <select name="user_id" class="mt-1 block w-full border-gray-300 rounded-md" required>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}" {{ $commission->user_id == $u->id ? 'selected' : '' }}>
                                {{ $u->name }} ({{ $u->email }})
                            </option>
                        @endforeach
                    </select>
                    @error('user_id') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Percentual (%)</label>
                    <input type="number" step="0.01" name="percentage" value="{{ old('percentage', $commission->percentage) }}"
                           class="mt-1 block w-full border-gray-300 rounded-md" required>
                    @error('percentage') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
                </div>

                <div class="mb-4 flex gap-2">
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700">Válido de</label>
                        <input type="date" name="valid_from" value="{{ old('valid_from', optional($commission->valid_from)->format('Y-m-d')) }}"
                               class="mt-1 block w-full border-gray-300 rounded-md">
                        @error('valid_from') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700">Até</label>
                        <input type="date" name="valid_to" value="{{ old('valid_to', optional($commission->valid_to)->format('Y-m-d')) }}"
                               class="mt-1 block w-full border-gray-300 rounded-md">
                        @error('valid_to') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="mb-4">
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="active" value="1" class="rounded" {{ old('active', $commission->active) ? 'checked' : '' }}>
                        <span class="ml-2">Ativa</span>
                    </label>
                </div>

                <div class="flex justify-end">
                    <a href="{{ route('admin.comissoes.index') }}" class="bg-gray-300 text-black px-4 py-2 rounded mr-2">Cancelar</a>
                    <button type="submit" class="text-black border px-4 py-2 rounded hover:bg-green-700">
                        Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
