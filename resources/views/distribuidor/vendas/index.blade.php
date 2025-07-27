<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Minhas Vendas</h2>
    </x-slot>

    

    <div class="max-w-7xl mx-auto p-6">
        @if (session('success'))
            <div class="bg-green-100 text-green-800 p-2 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-blue-100 text-blue-800 p-3 rounded mb-4">
            Comissão acumulada: <strong>R$ {{ number_format($totalComissao, 2, ',', '.') }}</strong>
        </div>

        <a href="{{ route('distribuidor.vendas.create') }}" class=" text-black border px-4 py-2 rounded inline-block mb-4">
            Registrar nova venda
        </a>

        

        <table class="w-full table-auto text-left border">
            <thead class="bg-gray-200">
                <tr>
                    <th class="p-2 border">Produto</th>
                    <th class="p-2 border">Quantidade</th>
                    <th class="p-2 border">Valor total</th>
                    <th class="p-2 border">Comissão</th>
                    <th class="p-2 border">Data</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($vendas as $venda)
                    <tr class="border-t">
                        <td class="p-2 border">{{ $venda->produto->nome }}</td>
                        <td class="p-2 border">{{ $venda->quantidade }}</td>
                        <td class="p-2 border">R$ {{ number_format($venda->valor_total, 2, ',', '.') }}</td>
                        <td class="p-2 border">R$ {{ number_format($venda->comissao, 2, ',', '.') }}</td>
                        <td class="p-2 border">{{ $venda->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-4 text-center text-gray-500">Nenhuma venda registrada.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="mt-4">
            {{ $vendas->links() }}
        </div>
    </div>
</x-app-layout>
