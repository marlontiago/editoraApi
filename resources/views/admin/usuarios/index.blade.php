<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Usuários Cadastrados') }}
        </h2>
    </x-slot>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <div class="p-6">
<div class="flex justify-start mb-4">
        <a href="{{ route('admin.usuarios.create') }}" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition">
            Criar Usuário
        </a>
    </div>
        
        <div class="bg-white p-6 rounded shadow">
            
            <h3 class="text-lg font-semibold mb-4">Usuários Cadastrados</h3>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse bg-white rounded-lg shadow">
                    <thead class="bg-gray-100">
                        <tr class="border-b">
                            <th class="p-2">Nome</th>
                            <th class="p-2">Email</th>
                            <th class="p-2">Papel</th>
                            <th class="p-2">Editar</th>
                            <th class="p-2">Excluir</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($usuarios as $user)
                            <tr class="border-b hover:bg-gray-50">
                                <td class="p-2">{{ $user->name }}</td>
                                <td class="p-2">{{ $user->email }}</td>
                                <td class="p-2">
                                    @foreach ($user->roles as $role)
                                        <span class="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded mr-1">
                                            {{ ucfirst($role->name) }}
                                        </span>
                                    @endforeach
                                </td>
                                <td>
                                    <a href="{{ route('admin.usuarios.edit', $user) }}" class="p-2" >Editar</a>
                                </td>
                                <td>
                                    <form action="{{ route('admin.usuarios.destroy', $user->id) }}" method="POST">
                                        @csrf @method('DELETE')
                                    <button type="submit" class="text-red-600" onclick="return confirm('Tem certeza que deseja excluir?')">Excluir</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-gray-500 p-2">Nenhum usuário cadastrado.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
