<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Dashboard do Distribuidor</h2>
    </x-slot>

    <div class="p-6">
        <p>Bem-vindo, {{ auth()->user()->name }}!</p>
        <a href="{{ route('distribuidor.vendas.index') }}" class="inline-block mt-4 px-4 py-2 bg-blue-600 text-black border rounded">
            Ver Vendas
        </a>
    </div>
</x-app-layout>
