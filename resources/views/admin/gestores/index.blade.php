<x-app-layout>
     <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">
                Gestores
            </h2>
            <a href="{{ route('admin.gestores.create') }}"
               class="inline-flex items-center rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                + Novo Gestor
            </a>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto p-6">
        @if (session('success'))
            <div class="mb-4 rounded-md border border-green-300 bg-green-50 p-4 text-green-800">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white shadow rounded-lg overflow-hidden">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="bg-gray-100 text-left text-gray-600 border-b">
                        <th class="px-4 py-2">Razão Social</th>
                        <th class="px-4 py-2">CNPJ</th>
                        <th class="px-4 py-2">E-mail</th>
                        <th class="px-4 py-2">UF</th>
                        <th class="px-4 py-2">Contrato até</th>
                        <th class="px-4 py-2">Assinado</th>
                        <th class="px-4 py-2 text-center w-32">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($gestores as $gestor)
                        <tr>
                            <td class="px-4 py-2">{{ $gestor->razao_social }}</td>
                            <td class="px-4 py-2">{{ $gestor->cnpj }}</td>
                            <td class="px-4 py-2">{{ $gestor->email_exibicao }}</td>
                            <td class="px-4 py-2">{{ $gestor->estado_uf ?? '—' }}</td>
                            <td class="px-4 py-2">
                                @if($gestor->vencimento_contrato)
                                    {{ \Carbon\Carbon::parse($gestor->vencimento_contrato)->format('d/m/Y') }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-2">
                                @if($gestor->contrato_assinado)
                                    <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700">Sim</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700">Não</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-center">
                                <a href="{{ route('admin.gestores.show', $gestor) }}"
                                   class="text-blue-600 hover:underline">Ver</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-4 text-center text-gray-500">
                                Nenhum gestor cadastrado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $gestores->links() }}
        </div>
    </div>
</x-app-layout>
