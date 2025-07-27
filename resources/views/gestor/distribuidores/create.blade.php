<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Cadastrar Distribuidor</h2>
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

        <form action="{{ route('gestor.distribuidores.store') }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label class="block">Nome completo:</label>
                <input type="text" name="nome_completo" class="w-full border rounded p-2" required>
            </div>

            <div>
                <label class="block">Telefone:</label>
                <input type="text" name="telefone" class="w-full border rounded p-2">
            </div>

            <div>
                <label class="block">E-mail:</label>
                <input type="email" name="email" class="w-full border rounded p-2" required>
            </div>

            <div>
                <label class="block">Senha:</label>
                <input type="password" name="senha" class="w-full border rounded p-2" required>
            </div>

            <button class="bg-green-600 text-black border mt-2  px-4 py-2 rounded" type="submit">Salvar</button>
        </form>
    </div>
</x-app-layout>
