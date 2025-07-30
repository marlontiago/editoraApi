<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard - Admin') }}
        </h2>
    </x-slot>

    <div class="max-w-6xl mx-auto p-6 space-y-6">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white p-4 shadow rounded">
                <h3 class="text-sm text-gray-500">Produtos</h3>
                <p class="text-2xl font-bold">{{ $totalProdutos }}</p>
                <a href="{{ route('admin.produtos.index') }}" class="inline-block bg-green-600 mt-6 text-white px-4 py-2 rounded hover:bg-green-700">Ver produtos</a>
            </div>

            <div class="bg-white p-4 shadow rounded">
                <h3 class="text-sm text-gray-500">Gestores</h3>
                <p class="text-2xl font-bold">{{ $totalGestores }}</p>
                <a href="{{ route('admin.gestores.index') }}" class="inline-block bg-green-600 mt-6 text-white px-4 py-2 rounded hover:bg-green-700">Ver gestores</a>
            </div>

            <div class="bg-white p-4 shadow rounded">
                <h3 class="text-sm text-gray-500">Criar Usuário</h3>
                <a href="{{ route('admin.usuarios.create') }}" class="inline-block bg-green-600 mt-6 text-white px-4 py-2 rounded hover:bg-green-700">
                    Novo Usuário
                </a>
            </div>

            <div class="bg-white p-4 shadow rounded">
                <h3 class="text-sm text-gray-500">Comissões</h3>
                <a href="{{ route('admin.comissoes.index') }}" class="inline-block mt-10 bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Gerenciar Comissões</a>
            </div>           
        </div>
    </div>

    <div>
        <h2 class="text-bold text-xl">Relatório de vendas </h2>
        <table class="w-full text-left border-collapse mt-4 bg-white shadow rounded-lg">
            <thead>
                <tr class="border-b bg-gray-100">
                    <th class="p-2">Gestor</th>
                    <th class="p-2">Distribuidor</th>
                    <th class="p-2">Data</th>
                    <th class="p-2">Valor da Venda</th>
                    <th class="p-2">Comissão (%)</th>
                    <th class="p-2">Comissão (R$)</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($vendas as $venda)
                    @php
                        $userId = $venda->distribuidor->user_id;
                        $percentual = $venda->distribuidor->user->commissions->last()->percentage ?? 0;
                        $valorComissao = ($percentual / 100) * $venda->valor_total;
                    @endphp
                    <tr class="border-b">
                        <td class="p-2">{{ $venda->distribuidor->gestor->user->name }}</td>
                        <td class="p-2">{{ $venda->distribuidor->user->name }}</td>
                        <td class="p-2">{{ \Carbon\Carbon::parse($venda->data)->format('d/m/Y') }}</td>
                        <td class="p-2">R$ {{ number_format($venda->valor_total, 2, ',', '.') }}</td>
                        <td class="p-2">{{ number_format($percentual, 2, ',', '.') }}%</td>
                        <td class="p-2">R$ {{ number_format($valorComissao, 2, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center p-2">Nenhuma venda encontrada</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-app-layout>