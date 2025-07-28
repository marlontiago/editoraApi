<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Editar Gestor</h2>
    </x-slot>

    <div class="max-w-2xl mx-auto py-6">
        <form method="POST" action="{{ route('admin.gestores.update', $gestor) }}">
            @csrf
            @method('PUT')

            <div class="mb-4">
                <label for="nome_completo" class="block text-sm font-medium text-gray-700">Nome Completo</label>
                <input type="text" name="nome_completo" value="{{ $gestor->nome_completo }}" class="mt-1 block w-full border border-gray-300 rounded-md" required>
            </div>

            <div class="mb-4">
                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" name="email" value="{{ $gestor->user->email }}" class="mt-1 block w-full border border-gray-300 rounded-md" required>
            </div>

            <div class="mb-4">
                <label for="telefone" class="block text-sm font-medium text-gray-700">Telefone</label>
                <input type="text" name="telefone" value="{{ $gestor->telefone }}" class="mt-1 block w-full border border-gray-300 rounded-md">
            </div>

            <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded">Atualizar</button>
        </form>
    </div>
</x-app-layout>