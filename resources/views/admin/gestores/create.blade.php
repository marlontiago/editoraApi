<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Novo Gestor</h2>
    </x-slot>

    <div class="max-w-2xl mx-auto py-6">
        <form method="POST" action="{{ route('admin.gestores.store') }}">
            @csrf

            <div class="mb-4">
                <label for="nome_completo" class="block text-sm font-medium text-gray-700">Nome Completo</label>
                <input type="text" name="nome_completo" class="mt-1 block w-full border border-gray-300 rounded-md" required>
            </div>

            <div class="mb-4">
                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" name="email" class="mt-1 block w-full border border-gray-300 rounded-md" required>
            </div>

            <div class="mb-4">
                <label for="telefone" class="block text-sm font-medium text-gray-700">Telefone</label>
                <input type="text" name="telefone" class="mt-1 block w-full border border-gray-300 rounded-md">
            </div>

            <div class="mb-4">
                <label for="password" class="block text-sm font-medium text-gray-700">Senha</label>
                <input type="password" name="password" class="mt-1 block w-full border border-gray-300 rounded-md" required>
            </div>

            <button type="submit" class="text-black border px-4 py-2 rounded">Salvar</button>
        </form>
    </div>
</x-app-layout>