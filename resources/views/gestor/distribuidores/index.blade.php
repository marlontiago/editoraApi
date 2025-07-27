<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Distribuidores</h2>
    </x-slot>

    <div class="max-w-7xl mx-auto p-6">
        @if (session('success'))
            <div class="bg-green-100 text-green-800 p-2 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        <a href="{{ route('gestor.distribuidores.create') }}"
           class="bg-blue-600 text-black border px-4 py-2 rounded mb-4 inline-block">Novo Distribuidor</a>

        <table class="w-full table-auto text-left border">
            <thead class="bg-gray-200">
                <tr>
                    <th class="p-2 border">Nome</th>
                    <th class="p-2 border">E-mail</th>
                    <th class="p-2 border">Telefone</th>
                    <th class="p-2 border">Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($distribuidores as $distribuidor)
                    <tr class="border-t">
                        <td class="p-2 border">{{ $distribuidor->nome_completo }}</td>
                        <td class="p-2 border">{{ optional($distribuidor->user)->email ?? 'Sem usuário' }}</td>
                        <td class="p-2 border">{{ $distribuidor->telefone }}</td>
                        <td class="p-2 border">
                            <a href="{{ route('gestor.distribuidores.edit', $distribuidor) }}" class="text-blue-600">Editar</a>
                            |
                            <form action="{{ route('gestor.distribuidores.destroy', $distribuidor) }}"
                                  method="POST" class="inline"
                                  onsubmit="return confirm('Tem certeza que deseja excluir?')">
                                @csrf
                                @method('DELETE')
                                <button class="text-red-600" type="submit">Excluir</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="p-4 text-center text-gray-500">Nenhum distribuidor encontrado.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-app-layout>
