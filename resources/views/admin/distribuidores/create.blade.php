<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Novo Distribuidor</h2>
    </x-slot>
    @if ($errors->any())
    <div class="mb-4 text-red-600">
        <ul class="list-disc list-inside">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

    <div class="max-w-4xl mx-auto p-6 bg-white shadow rounded">
        <form action="{{ route('admin.distribuidores.store') }}" method="POST">
            @csrf

            <div class="mb-4">
                <label>Nome</label>
                <input type="text" name="name" class="w-full border rounded px-3 py-2" required>
            </div>

            <div class="mb-4">
                <label>Email</label>
                <input type="email" name="email" class="w-full border rounded px-3 py-2" required>
            </div>

            <div class="mb-4">
                <label>Senha</label>
                <input type="password" name="password" class="w-full border rounded px-3 py-2" required>
            </div>

            <div class="mb-4">
                <label>Confirmar Senha</label>
                <input type="password" name="password_confirmation" class="w-full border rounded px-3 py-2" required>
            </div>

            <div class="mb-4">
                <label>Nome Completo</label>
                <input type="text" name="nome_completo" class="w-full border rounded px-3 py-2" required>
            </div>

            <div class="mb-4">
                <label>Telefone</label>
                <input type="text" name="telefone" class="w-full border rounded px-3 py-2" required>
            </div>

            <div class="mb-4">
                <label>Gestor</label>
                <select name="gestor_id" class="w-full border rounded px-3 py-2" required>
                    @foreach($gestores as $gestor)
                        <option value="{{ $gestor->id }}">{{ $gestor->nome_completo }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-4">
                <label class="block font-medium text-sm text-gray-700 mb-2">Cidades de atuação</label>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2">
                    @foreach($cities as $cidade)
                        <label class="flex items-center space-x-2">
                            <input type="checkbox" name="cities[]" value="{{ $cidade->id }}" class="rounded border-gray-300">
                            <span>{{ $cidade->name }} - {{ $cidade->state }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Salvar</button>
        </form>
    </div>
</x-app-layout>
