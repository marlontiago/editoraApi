<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Distribuidores</h2>
    </x-slot>

    <div class="max-w-7xl mx-auto p-6">
        <a href="{{ route('admin.distribuidores.create') }}" class="bg-green-600 text-white px-4 py-2 rounded">Novo Distribuidor</a>

        @if(session('success'))
            <div class="mt-4 text-green-600">{{ session('success') }}</div>
        @endif

        <div class="overflow-x-auto mt-6">
            <table class="min-w-full bg-white rounded shadow text-sm">
                <thead class="bg-gray-100">
                    <tr class="text-left">
                        <th class="px-4 py-2">Razão Social</th>
                        <th class="px-4 py-2">Representante</th>
                        <th class="px-4 py-2">Email</th>
                        <th class="px-4 py-2">CNPJ</th>
                        <th class="px-4 py-2">Percentual (%)</th>
                        <th class="px-4 py-2">Contrato</th>
                        <th class="px-4 py-2">Cidades</th>
                        <th class="px-4 py-2">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($distribuidores as $distribuidor)
                        <tr class="border-t">
                            <td class="px-4 py-2">{{ $distribuidor->razao_social }}</td>
                            <td class="px-4 py-2">{{ $distribuidor->representante_legal }}</td>
                            <td class="px-4 py-2">{{ $distribuidor->user->email }}</td>
                            <td class="px-4 py-2">{{ $distribuidor->cnpj }}</td>
                            <td class="px-4 py-2">{{ number_format($distribuidor->percentual_vendas, 2) }}%</td>
                            <td class="px-4 py-2">
                                @if($distribuidor->contrato)
                                    <a href="{{ Storage::url($distribuidor->contrato) }}" target="_blank" class="text-blue-600 underline">Ver PDF</a>
                                @else
                                    <span class="text-gray-400">Nenhum</span>
                                @endif
                            </td>
                            <td class="px-4 py-2">
                                @if($distribuidor->cities->count())
                                    {{ $distribuidor->cities->pluck('name')->join(', ') }}
                                @else
                                    <span class="text-gray-400">Sem cidades</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 space-x-2">
                                <a href="{{ route('admin.distribuidores.edit', $distribuidor->id) }}" class="text-blue-600">Editar</a>
                                <form action="{{ route('admin.distribuidores.destroy', $distribuidor->id) }}" method="POST" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-600" onclick="return confirm('Tem certeza que deseja excluir?')">Excluir</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
