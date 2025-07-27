<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Gerenciar Comissões</h2>
    </x-slot>

    <div class="p-6">
        @if (session('success'))
            <div class="bg-green-100 text-green-800 p-2 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        <table class="w-full border text-left">
            <thead class="bg-gray-200">
                <tr>
                    <th class="p-2">Tipo</th>
                    <th class="p-2">Percentual</th>
                    <th class="p-2">Ação</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($comissoes as $comissao)
                    <tr class="border-t">
                        <td class="p-2 capitalize">{{ $comissao->tipo }}</td>
                        <td class="p-2">{{ $comissao->percentual }}%</td>
                        <td class="p-2">
                            <a href="{{ route('admin.comissoes.edit', $comissao) }}" class="text-blue-600">Editar</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-app-layout>
