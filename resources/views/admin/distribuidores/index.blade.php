<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Distribuidores</h2>
    </x-slot>

    <div class="max-w-7xl mx-auto p-6">
        <a href="{{ route('admin.distribuidores.create') }}" class="bg-green-600 text-white px-4 py-2 rounded">Novo Distribuidor</a>

        @if(session('success'))
            <div class="mt-4 text-green-600">{{ session('success') }}</div>
        @endif

        <table class="w-full mt-6 bg-white rounded shadow">
            <thead>
                <tr class="bg-gray-200">
                    <th class="px-4 py-2 text-left">Nome</th>
                    <th class="px-4 py-2 text-left">Telefone</th>
                    <th class="px-4 py-2 text-left">Ações</th>
                </tr>
            </thead>
            <tbody>
                @foreach($distribuidores as $distribuidor)
                    <tr class="border-t">
                        <td class="px-4 py-2">{{ $distribuidor->nome_completo }}</td>
                        <td class="px-4 py-2">{{ $distribuidor->telefone }}</td>
                        <td class="px-4 py-2 space-x-2">
                            <a href="{{ route('admin.distribuidores.edit', $distribuidor->id) }}" class="text-blue-600">Editar</a>
                            <form action="{{ route('admin.distribuidores.destroy', $distribuidor->id) }}" method="POST" class="inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-600" onclick="return confirm('Tem certeza?')">Excluir</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-app-layout>
