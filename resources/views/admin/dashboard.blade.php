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
                <p class="text-2xl font-bold">{{ \App\Models\Produto::count() }}</p>
                <a href="{{ route('admin.produtos.index') }}" class="text-blue-600 text-sm underline">Ver produtos</a>
            </div>

            <div class="bg-white p-4 shadow rounded">
                <h3 class="text-sm text-gray-500">Gestores</h3>
                <p class="text-2xl font-bold">{{ \App\Models\Gestor::count() }}</p>
                <a href="{{ route('admin.gestores.index') }}" class="text-blue-600 text-sm underline">Ver gestores</a>
            </div>

            <div class="bg-white p-4 shadow rounded">
                <h3 class="text-sm text-gray-500">Comissões</h3>
                <a href="{{ route('admin.comissoes.index') }}" class="text-blue-600 text-sm underline">Gerenciar Comissões</a>
            </div>

            <a href="{{ route('admin.comissoes.index') }}" class="text-black px-4 py-2 rounded inline-block">
            Gerenciar Comissões
            </a>
        </div>
    </div>
</x-app-layout>
