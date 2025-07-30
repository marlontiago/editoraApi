<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard - Admin') }}
        </h2>
    </x-slot>

    <div class="max-w-6xl mx-auto p-6 space-y-6">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white p-4 shadow rounded">
                <h3 class="text-sm text-gray-500">Produtos</h3>
                <p class="text-2xl font-bold">{{ $totalProdutos }}</p>
                <a href="{{ route('admin.produtos.index') }}" class="inline-block bg-green-600 mt-6 text-white px-4 py-2 rounded hover:bg-green-700">Ver produtos</a>
            </div>

            <div class="bg-white p-4 shadow rounded">
                <h3 class="text-sm text-gray-500">Gestores</h3>
                <p class="text-2xl font-bold">{{ $totalGestores }}</p>
                <a href="{{ route('admin.gestores.index') }}" class="inline-block bg-blue-600 mt-6 text-white px-4 py-2 rounded hover:bg-green-700">Ver gestores</a>
            </div>

            <div class="bg-white p-4 shadow rounded">
                <h3 class="text-sm text-gray-500">Criar Usuário</h3>
                <a href="{{ route('admin.usuarios.create') }}" class="inline-block bg-green-600 mt-6 text-white px-4 py-2 rounded hover:bg-green-700">
                    Novo Usuário
                </a>
            </div>

            <div class="bg-white p-4 shadow rounded">
                <h3 class="text-sm text-gray-500">Comissões</h3>
                <a href="{{ route('admin.comissoes.index') }}" class="inline-block mt-10 bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Gerenciar Comissões</a>
            </div>
            

            

            
        </div>
    </div>
</x-app-layout>