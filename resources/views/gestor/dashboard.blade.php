<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">
            Dashboard do Gestor
        </h2>
    </x-slot>

    <div class="max-w-7xl mx-auto p-6 space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white p-4 rounded shadow">
                <h3 class="text-sm text-gray-500">Distribuidores</h3>
                <p class="text-2xl font-bold">{{ $qtdDistribuidores }}</p>
                <a href="{{ route('gestor.distribuidores.index') }}"
                   class="mt-2 inline-block bg-blue-600 text-black border px-4 py-2 rounded">
                    Ver distribuidores
                </a>
                <a href="{{ route('gestor.comissoes.index') }}" 
                   class="bg-green-600 text-black border px-4 py-2 rounded inline-block">
                   Ver Comissões
                </a>
                
            </div>
        </div>
    </div>
</x-app-layout>
