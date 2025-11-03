{{-- resources/views/admin/colecoes/index.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Coleções</h2>
            <a href="{{ route('admin.colecoes.create') }}" class="px-3 py-1.5 rounded border">Nova coleção</a>
        </div>
    </x-slot>

    <div class="max-w-6xl mx-auto p-6">
        @if (session('success'))
            <div class="mb-4 rounded bg-green-50 text-green-800 px-4 py-2">
                {!! session('success') !!}
            </div>
        @endif

        <form method="GET" class="mb-4">
            <input type="text" name="q" value="{{ $q }}" placeholder="Buscar por nome..."
                   class="w-full md:w-80 border rounded px-3 py-2">
        </form>

        <div class="bg-white shadow rounded-lg">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="text-left p-3">Nome</th>
                        <th class="text-left p-3">Qtd. produtos</th>
                        <th class="text-right p-3">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($colecoes as $c)
                        <tr class="border-t">
                            <td class="p-3">{{ $c->nome }}</td>
                            <td class="p-3">{{ $c->produtos_count }}</td>
                            
                            <td class="p-3 text-right">
                                <a class="underline mr-2" href="{{ route('admin.colecoes.show', $c) }}">Ver</a>
                                <a class="underline mr-2" href="{{ route('admin.colecoes.edit', $c) }}">Editar</a>
                                <form action="{{ route('admin.colecoes.destroy', $c) }}" method="POST" class="inline"
                                    onsubmit="return confirm('Remover esta coleção? Os produtos serão desassociados.');">
                                    @csrf @method('DELETE')
                                    <button class="underline text-red-600">Excluir</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td class="p-3" colspan="3">Nenhuma coleção.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $colecoes->links() }}</div>
    </div>
</x-app-layout>
