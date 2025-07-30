<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Comissões por Usuário
        </h2>
    </x-slot>

    <div class="max-w-6xl mx-auto py-6">
        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        <div class="mb-4">
            <a href="{{ route('admin.comissoes.create') }}"
                class="bg-blue-600 text-white px-4 py-2 rounded">
                Nova Comissão
            </a>
        </div>

        <div class="bg-white shadow rounded p-6">
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th class="text-left px-4 py-2">Usuário</th>
                        <th class="text-left px-4 py-2">Email</th>
                        <th class="text-left px-4 py-2">Percentual</th>
                        <th class="text-left px-4 py-2"></th>
                        <th class="text-left px-4 py-2">Ações</th>

                    </tr>
                </thead>
                <tbody>
                    @foreach ($commissions as $commission)
                        <tr class="border-t">
                            <td class="px-4 py-2">{{ $commission->user->name }}</td>
                            <td class="px-4 py-2">{{ $commission->user->email }}</td>
                            <td class="px-4 py-2">{{ number_format($commission->percentage, 2, ',', '.') }}%</td>
                            <td class="px-4 py-2">
                                
                            <td class="px-4 py-2">
                                <a href="{{ route('admin.comissoes.edit', $commission) }}"
                                   class="text-black border px-3 py-1 rounded hover:bg-blue-700 text-sm">Editar</a>

                                <form action="{{ route('admin.comissoes.destroy', $commission) }}"
                                      method="POST"
                                      class="inline"
                                      onsubmit="return confirm('Excluir esta comissão?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700 text-sm ml-1">
                                        Excluir
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="mt-4">
                {{ $commissions->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
