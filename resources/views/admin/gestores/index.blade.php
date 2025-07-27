<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Gestores</h2>
    </x-slot>

    <div class="max-w-6xl mx-auto p-6">
        @if (session('success'))
            <div class="bg-green-100 text-green-800 p-2 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        <a href="{{ route('admin.gestores.create') }}" class="bg-blue-600 text-black border px-4 py-2 rounded mb-4 inline-block">Novo Gestor</a>

        <table class="w-full border text-left mt-4">
            <thead class="bg-gray-200">
                <tr>
                    <th class="p-2">Nome</th>
                    <th class="p-2">E-mail</th>
                    <th class="p-2">Telefone</th>
                    <th class="p-2">Ações</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($gestores as $gestor)
                    <tr class="border-t">
                        <td class="p-2">{{ $gestor->nome_completo }}</td>
                        <td class="p-2">{{ optional($gestor->user)->email ?? 'Usuário ausente' }}</td>
                        <td class="p-2">{{ $gestor->telefone }}</td>
                        <td class="p-2">
                            <a href="{{ route('admin.gestores.edit', $gestor) }}" class="text-blue-600">Editar</a> |
                            <form action="{{ route('admin.gestores.destroy', $gestor) }}" method="POST" class="inline" onsubmit="return confirm('Tem certeza que deseja excluir?')">
                                @csrf
                                @method('DELETE')
                                <button class="text-red-600" type="submit">Excluir</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mt-4">
            {{ $gestores->links() }}
        </div>
    </div>
</x-app-layout>
