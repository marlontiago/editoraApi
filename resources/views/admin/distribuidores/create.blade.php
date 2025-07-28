<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Novo Distribuidor</h2>
    </x-slot>

    <div class="max-w-2xl mx-auto p-6 bg-white shadow rounded">
        <form action="{{ route('admin.distribuidores.store') }}" method="POST">
            @csrf

            <div class="mb-4">
                <label>Nome Completo</label>
                <input type="text" name="nome_completo" class="w-full border p-2 rounded" required>
            </div>

            <div class="mb-4">
                <label>Telefone</label>
                <input type="text" name="telefone" class="w-full border p-2 rounded" required>
            </div>

            <div class="mb-4">
                <label>Email</label>
                <input type="email" name="email" class="w-full border p-2 rounded" required>
            </div>

            <div class="mb-4">
                <label>Senha</label>
                <input type="password" name="password" class="w-full border p-2 rounded" required>
            </div>

            <div class="mb-4">
                <label>Gestor Responsável</label>
                <select name="gestor_id" class="w-full border p-2 rounded" required>
                    @foreach($gestores as $gestor)
                        <option value="{{ $gestor->id }}">{{ $gestor->nome_completo }}</option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Cadastrar</button>
        </form>
    </div>
</x-app-layout>
