<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Cadastrar Gestor</h2>
    </x-slot>

    @if ($errors->any())
        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 p-4 rounded">
            <strong>Erro(s) encontrado(s):</strong>
            <ul class="mt-2 list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="max-w-xl mx-auto py-6">
        <form method="POST" action="{{ route('admin.gestores.store') }}" enctype="multipart/form-data">
            @csrf

            {{-- Razão Social --}}
            <div class="mb-4">
                <label for="razao_social" class="block text-sm font-medium text-gray-700">Razão Social</label>
                <input type="text" name="razao_social" id="razao_social" class="mt-1 block w-full border border-gray-300 rounded-md" required>
            </div>

            {{-- CNPJ --}}
            <div class="mb-4">
                <label for="cnpj" class="block text-sm font-medium text-gray-700">CNPJ</label>
                <input type="text" name="cnpj" id="cnpj" class="mt-1 block w-full border border-gray-300 rounded-md" required>
            </div>

            {{-- Representante Legal --}}
            <div class="mb-4">
                <label for="representante_legal" class="block text-sm font-medium text-gray-700">Representante Legal</label>
                <input type="text" name="representante_legal" id="representante_legal" class="mt-1 block w-full border border-gray-300 rounded-md" required>
            </div>

            {{-- CPF --}}
            <div class="mb-4">
                <label for="cpf" class="block text-sm font-medium text-gray-700">CPF</label>
                <input type="text" name="cpf" id="cpf" class="mt-1 block w-full border border-gray-300 rounded-md" required>
            </div>

            {{-- RG --}}
            <div class="mb-4">
                <label for="rg" class="block text-sm font-medium text-gray-700">RG</label>
                <input type="text" name="rg" id="rg" class="mt-1 block w-full border border-gray-300 rounded-md" required>
            </div>

            {{-- Telefone --}}
            <div class="mb-4">
                <label for="telefone" class="block text-sm font-medium text-gray-700">Telefone</label>
                <input type="text" name="telefone" id="telefone" class="mt-1 block w-full border border-gray-300 rounded-md" required>
            </div>

            {{-- Email --}}
            <div class="mb-4">
                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" name="email" id="email" class="mt-1 block w-full border border-gray-300 rounded-md" required>
            </div>

            {{-- Senha --}}
            <div class="mb-4">
                <label for="password" class="block text-sm font-medium text-gray-700">Senha</label>
                <input type="password" name="password" id="password" class="mt-1 block w-full border border-gray-300 rounded-md" required>
            </div>

            {{-- Endereço Completo --}}
            <div class="mb-4">
                <label for="endereco_completo" class="block text-sm font-medium text-gray-700">Endereço Completo</label>
                <input type="text" name="endereco_completo" id="endereco_completo" class="mt-1 block w-full border border-gray-300 rounded-md">
            </div>

            @php
$ufs = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'];
@endphp

<div class="mb-4">
    <label for="estado_uf" class="block text-sm font-medium text-gray-700">UF do Gestor</label>
    <select name="estado_uf" id="estado_uf" class="mt-1 block w-full border rounded">
        <option value="">-- Selecione --</option>
        @foreach($ufs as $uf)
            <option value="{{ $uf }}" @selected(old('estado_uf', $gestor->estado_uf ?? null) === $uf)>{{ $uf }}</option>
        @endforeach
    </select>
</div>

            {{-- Percentual sobre vendas --}}
            <div class="mb-4">
                <label for="percentual_vendas" class="block text-sm font-medium text-gray-700">Percentual sobre vendas (%)</label>
                <input type="number" step="0.01" min="0" max="100" name="percentual_vendas" id="percentual_vendas" class="mt-1 block w-full border border-gray-300 rounded-md" required>
            </div>

            {{-- Vencimento do contrato --}}
            <div class="mb-4">
                <label for="vencimento_contrato" class="block text-sm font-medium text-gray-700">Vencimento do contrato</label>
                <input type="date" name="vencimento_contrato" id="vencimento_contrato" class="mt-1 block w-full border border-gray-300 rounded-md">
            </div>

            {{-- Contrato assinado --}}
            <div class="mb-4 flex items-center">
                <input type="hidden" name="contrato_assinado" value="0"/>
                <input type="checkbox" name="contrato_assinado" id="contrato_assinado" value="1" class="mr-2" {{ old('contrato_assinado') ? 'checked' : '' }}>
                <label for="contrato_assinado" class="text-sm font-medium text-gray-700" >Contrato assinado</label>
            </div>

            {{-- Anexar contrato PDF --}}
            <div class="mb-4">
                <label for="contrato" class="block text-sm font-medium text-gray-700">Anexar contrato (PDF)</label>
                <input type="file" name="contrato" id="contrato" accept="application/pdf" class="mt-1 block w-full border border-gray-300 rounded-md">
            </div>

            {{-- Botão --}}
            <div class="flex justify-end">
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">Salvar</button>
            </div>
        </form>
    </div>
</x-app-layout>
