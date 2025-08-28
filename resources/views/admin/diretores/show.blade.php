<x-app-layout>
    <x-slot name="header"><h2 class="text-2xl font-bold">Diretor Comercial</h2></x-slot>

    <div class="p-6 max-w-4xl mx-auto space-y-4">
        @if(session('success'))
            <div class="border bg-green-50 text-green-800 px-4 py-2 rounded">{{ session('success') }}</div>
        @endif

        <div class="bg-white border rounded p-6 space-y-2">
            <div><strong>Nome:</strong> {{ $diretor->nome }}</div>
            <div><strong>Email:</strong> {{ $diretor->email }}</div>
            <div><strong>Telefone:</strong> {{ $diretor->telefone ?: '—' }}</div>
            <div><strong>Endereço:</strong>
                {{ $diretor->logradouro ?: '—' }}, {{ $diretor->numero ?: 's/n' }}
                {{ $diretor->complemento ? ' - '.$diretor->complemento : '' }}
            </div>
            <div><strong>Bairro:</strong> {{ $diretor->bairro ?: '—' }}</div>
            <div><strong>Cidade/UF:</strong> {{ $diretor->cidade ?: '—' }} / {{ $diretor->estado ?: '—' }}</div>
            <div><strong>CEP:</strong> {{ $diretor->cep ?: '—' }}</div>
        </div>

        <div class="flex gap-2">
            <a href="{{ route('admin.diretor-comercials.edit', $diretor) }}" class="px-3 py-2 border rounded">Editar</a>
            <a href="{{ route('admin.diretor-comercials.index') }}" class="px-3 py-2 border rounded">Voltar</a>
        </div>
    </div>
</x-app-layout>
