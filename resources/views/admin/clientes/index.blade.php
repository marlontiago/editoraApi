<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Clientes</h2>
    </x-slot>

    @if ($success = Session::get('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
            <span class="block sm:inline">{{ $success }}</span>
        </div>        
    @endif

    <div class="max-w-full mx-auto p-6">
        <a href="{{ route('admin.clientes.create') }}" class="bg-green-600 text-white px-4 py-2 rounded">Novo Cliente</a>

        @if(session('success'))
            <div class="mt-4 text-green-600">{{ session('success') }}</div>
        @endif

        <div class="overflow-x-auto mt-6">
            <table class="min-w-full bg-white rounded shadow text-sm">
                <thead class="bg-gray-100">
                    <tr class="text-left">
                        <th class="px-4 py-2">Razão Social</th>
                        <th class="px-4 py-2">Email</th>
                        <th class="px-4 py-2">Telefone</th>
                        <th class="px-4 py-2">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($clientes as $cliente)
                        <tr class="border-t">
                            <td class="px-4 py-2">{{ $cliente->nome }}</td>
                            <td class="px-4 py-2">{{ $cliente->email }}</td>
                            <td class="px-4 py-2">{{ $cliente->telefone_formatado }}</td>
                            <td class="px-4 py-2 space-x-2">
                                <a href="{{ route('admin.clientes.edit', $cliente->id) }}" class="text-blue-600">Editar</a>
                                <form action="{{ route('admin.clientes.destroy', $cliente->id) }}" method="POST" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-600" onclick="return confirm('Tem certeza que deseja excluir?')">Excluir</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{ $clientes->links() }}
</x-app-layout>