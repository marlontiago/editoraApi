<x-app-layout>
    <x-slot name="header"><h2 class="text-2xl font-bold">Diretores Comerciais</h2></x-slot>

    <div class="p-6 max-w-7xl mx-auto space-y-4">
        @if(session('success'))
            <div class="border bg-green-50 text-green-800 px-4 py-2 rounded">{{ session('success') }}</div>
        @endif

        <a href="{{ route('admin.diretor-comercials.create') }}" class="px-4 py-2 rounded bg-blue-600 text-white">Novo</a>

        <div class="overflow-x-auto bg-white border rounded mt-4">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50">
                <tr class="text-left">
                    <th class="p-3">Nome</th>
                    <th class="p-3">Email</th>
                    <th class="p-3">Telefone</th>
                    <th class="p-3">Cidade/UF</th>
                    <th class="p-3 w-48">Ações</th>
                </tr>
                </thead>
                <tbody>
                @forelse($diretores as $d)
                    <tr class="border-t">
                        <td class="p-3">{{ $d->nome }}</td>
                        <td class="p-3">{{ $d->email }}</td>
                        <td class="p-3">{{ $d->telefone }}</td>
                        <td class="p-3">{{ $d->cidade }} / {{ $d->estado }}</td>
                        <td class="p-3 flex gap-2">
                            <a href="{{ route('admin.diretor-comercials.show', $d) }}" class="px-2 py-1 border rounded">Ver</a>
                            <a href="{{ route('admin.diretor-comercials.edit', $d) }}" class="px-2 py-1 border rounded">Editar</a>
                            <form action="{{ route('admin.diretor-comercials.destroy', $d) }}" method="POST" onsubmit="return confirm('Remover?')">
                                @csrf @method('DELETE')
                                <button class="px-2 py-1 border rounded text-red-600">Excluir</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td class="p-3" colspan="6">Nenhum registro.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        {{ $diretores->links() }}
    </div>
</x-app-layout>
