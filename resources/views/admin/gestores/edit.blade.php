<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Editar Gestor</h2>
    </x-slot>

    <div class="max-w-xl mx-auto p-6">
        @if ($errors->any())
            <div class="bg-red-100 text-red-800 p-3 rounded mb-4">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.gestores.update', $gestor) }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block">Nome completo:</label>
                <input type="text" name="nome_completo" value="{{ $gestor->nome_completo }}" class="w-full border rounded p-2" required>
            </div>

            <div>
                <label class="block">Telefone:</label>
                <input type="text" name="telefone" value="{{ $gestor->telefone }}" class="w-full border rounded p-2">
            </div>

            <button class="bg-blue-600 text-black border mt-2 px-4 py-2 rounded" type="submit">Atualizar</button>
        </form>
    </div>
</x-app-layout>
