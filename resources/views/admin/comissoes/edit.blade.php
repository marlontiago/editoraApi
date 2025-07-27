<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Editar Comissão</h2>
    </x-slot>

    <div class="max-w-xl mx-auto p-6">
        <form action="{{ route('admin.comissoes.update', $comissao) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-4">
                <label class="block font-semibold">Tipo</label>
                <input type="text" value="{{ $comissao->tipo }}" class="w-full border rounded p-2 bg-gray-100" disabled>
            </div>

            <div class="mb-4">
                <label class="block font-semibold">Percentual (%)</label>
                <input type="number" name="percentual" value="{{ old('percentual', $comissao->percentual) }}" class="w-full border rounded p-2" step="0.01" required>
            </div>

            <button class=" text-black border mt-2 px-4 py-2 rounded" type="submit">Salvar</button>
        </form>
    </div>
</x-app-layout>
