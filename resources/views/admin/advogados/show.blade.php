<x-app-layout>
    <x-slot name="header"><h2 class="text-2xl font-bold">Advogado</h2></x-slot>

    <div class="p-6 max-w-4xl mx-auto space-y-4">
        <div class="bg-white border rounded p-6 space-y-2">
            <div><strong>Nome:</strong> {{ $advogado->nome }}</div>
            <div><strong>Email:</strong> {{ $advogado->email }}</div>
            <div><strong>Telefone:</strong> {{ $advogado->telefone ?: '—' }}</div>
            <div><strong>OAB:</strong> {{ $advogado->oab }}</div>
            <div><strong>Endereço:</strong> {{ $advogado->logradouro }}, {{ $advogado->numero }} {{ $advogado->complemento }}</div>
            <div><strong>Bairro:</strong> {{ $advogado->bairro }}</div>
            <div><strong>Cidade/UF:</strong> {{ $advogado->cidade }} / {{ $advogado->estado }}</div>
            <div><strong>CEP:</strong> {{ $advogado->cep }}</div>
        </div>

        <div class="flex gap-2">
            <a href="{{ route('admin.advogados.edit', $advogado) }}" class="px-3 py-2 border rounded">Editar</a>
            <a href="{{ route('admin.advogados.index') }}" class="px-3 py-2 border rounded">Voltar</a>
        </div>
    </div>
</x-app-layout>
