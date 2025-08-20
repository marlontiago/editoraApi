<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Gestores</h2>
    </x-slot>

    <div class="min-w-full mx-auto py-6 sm:px-6 lg:px-8">

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        <div class="flex justify-between mb-4">
            <a href="{{ route('admin.gestores.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Novo Gestor</a>
            <a href="{{ route('admin.gestores.vincular') }}" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Vincular Distribuidores</a>
        </div>

        <div class="overflow-x-auto bg-white shadow-md rounded p-4">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">Razão Social</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">CNPJ</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">CPF</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">RG</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">Telefone</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">Email</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">Endereço</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">UF do Gestor</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">Distribuidores</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">% Vendas</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">Venc. Contrato</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">Contrato</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($gestores as $gestor)
                        <tr>
                            <td class="px-4 py-2">{{ $gestor->razao_social }}</td>
                            <td class="px-4 py-2">{{ $gestor->cnpj_formatado }}</td>
                            <td class="px-4 py-2">{{ $gestor->cpf_formatado }}</td>
                            <td class="px-4 py-2">{{ $gestor->rg_formatado }}</td>
                            <td class="px-4 py-2">{{ $gestor->telefone_formatado }}</td>
                            <td class="px-4 py-2">{{ $gestor->user->email }}</td>
                            <td class="px-4 py-2">{{ $gestor->endereco_completo }}</td>
                            <td class="px-4 py-2">
                                <span class="inline-flex items-center rounded bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700">
                                    {{ $gestor->estado_uf ?? '-' }}
                                </span>
                            </td>
                            <td class="px-4 py-2">
                                @forelse($gestor->distribuidores as $dist)
                                    <span class="block text-sm">{{ $dist->user->name ?? '-' }}</span>
                                @empty
                                    <span class="italic text-gray-500">Nenhum</span>
                                @endforelse
                            </td>
                            <td class="px-4 py-2">{{ $gestor->percentual_vendas }}%</td>
                            <td class="px-4 py-2">
                                {{ optional($gestor->vencimento_contrato)->format('d/m/Y') ?? '-' }}
                            </td>
                            <td class="px-4 py-2">
                                @if($gestor->contrato_assinado)
                                    <span class="text-green-600 font-bold">Sim</span><br>
                                    @if($gestor->contrato)
                                        <a href="{{ asset('storage/' . $gestor->contrato) }}" target="_blank" class="text-blue-600 underline text-sm">Ver contrato</a>
                                    @endif
                                @else
                                    <span class="text-red-600 font-bold">Não</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap">
                                <a href="{{ route('admin.gestores.edit', $gestor) }}" class="text-indigo-600 hover:text-indigo-900">Editar</a>
                                <form action="{{ route('admin.gestores.destroy', $gestor) }}" method="POST" class="inline" onsubmit="return confirm('Tem certeza que deseja excluir esse gestor?');">
                                    @csrf
                                    @method('DELETE')
                                
                                    <button type="submit" class="text-red-600 hover:text-red-900 ml-2">Excluir</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- Paginação --}}
            <div class="mt-4">
                {{ $gestores->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
