<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Distribuidores</h2>
            <a href="{{ route('admin.distribuidores.create') }}"
               class="inline-flex h-9 items-center rounded-md bg-green-600 px-4 text-sm font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                + Novo Distribuidor
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
                @forelse($distribuidores as $d)
                    @php
                        $email = optional($d->user)->email;
                        $emailExibicao = ($email && !str_contains($email, '@placeholder.local')) ? $email : 'Não informado';
                    @endphp
                    <tr>
                        <td class="px-4 py-2">{{ $d->razao_social }}</td>
                        <td class="px-4 py-2">{{ $d->cnpj }}</td>
                        <td class="px-4 py-2">{{ $emailExibicao }}</td>
                        <td class="px-4 py-2">{{ $d->uf ?? '—' }}</td>
                        <td class="px-4 py-2">
                            @if($d->vencimento_contrato)
                                {{ \Carbon\Carbon::parse($d->vencimento_contrato)->format('d/m/Y') }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-2">
                            @if($d->contrato_assinado)
                                <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700">Sim</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700">Não</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-center">
                            <a href="{{ route('admin.distribuidores.show', $d) }}" class="text-blue-600 hover:underline">Ver</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-4 text-center text-gray-500">
                            Nenhum distribuidor cadastrado.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $distribuidores->links() }}
        </div>
    </div>
</x-app-layout>
