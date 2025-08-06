<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Editar Gestor</h2>
    </x-slot>

     @if ($errors->any())
        <div class="mb-4 text-sm text-red-600">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="max-w-4xl mx-auto py-6">
        <form method="POST" action="{{ route('admin.gestores.update', $gestor) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            {{-- Razão Social --}}
            <div class="mb-4">
                <label for="razao_social" class="block text-sm font-medium text-gray-700">Razão Social</label>
                <input type="text" name="razao_social" value="{{ old('razao_social', $gestor->razao_social) }}" class="mt-1 block w-full border border-gray-300 rounded-md" required>
            </div>

            {{-- CNPJ --}}
            <div class="mb-4">
                <label for="cnpj" class="block text-sm font-medium text-gray-700">CNPJ</label>
                <input type="text" name="cnpj" value="{{ old('cnpj', $gestor->cnpj) }}" class="mt-1 block w-full border border-gray-300 rounded-md" required>
            </div>

            {{-- Representante Legal --}}
            <div class="mb-4">
                <label for="representante_legal" class="block text-sm font-medium text-gray-700">Representante Legal</label>
                <input type="text" name="representante_legal" value="{{ old('representante_legal', $gestor->representante_legal) }}" class="mt-1 block w-full border border-gray-300 rounded-md" required>
            </div>

            {{-- CPF --}}
            <div class="mb-4">
                <label for="cpf" class="block text-sm font-medium text-gray-700">CPF</label>
                <input type="text" name="cpf" value="{{ old('cpf', $gestor->cpf) }}" class="mt-1 block w-full border border-gray-300 rounded-md" required>
            </div>

            {{-- RG --}}
            <div class="mb-4">
                <label for="rg" class="block text-sm font-medium text-gray-700">RG</label>
                <input type="text" name="rg" value="{{ old('rg', $gestor->rg) }}" class="mt-1 block w-full border border-gray-300 rounded-md" required>
            </div>

            {{-- Telefone --}}
            <div class="mb-4">
                <label for="telefone" class="block text-sm font-medium text-gray-700">Telefone</label>
                <input type="text" name="telefone" value="{{ old('telefone', $gestor->telefone) }}" class="mt-1 block w-full border border-gray-300 rounded-md" required>
            </div>

            {{-- Email --}}
            <div class="mb-4">
                <label for="email" class="block text-sm font-medium text-gray-700">Email (preencha se quiser alterar)</label>
                <input type="email" name="email" value="{{ old('email', $gestor->user->email) }}" class="mt-1 block w-full border border-gray-300 rounded-md">
            </div>

            {{-- Endereço Completo --}}
            <div class="mb-4">
                <label for="endereco_completo" class="block text-sm font-medium text-gray-700">Endereço Completo</label>
                <input type="text" name="endereco_completo" value="{{ old('endereco_completo', $gestor->endereco_completo) }}" class="mt-1 block w-full border border-gray-300 rounded-md">
            </div>

            {{-- Estados Atribuídos (Cidades) --}}
            <div class="mb-4">
                <label for="cities" class="block text-sm font-medium text-gray-700">Estados Atribuídos</label>
                <select name="cities[]" id="cities" class="mt-1 block w-full border border-gray-300 rounded-md" multiple>
                    @foreach($cities as $city)
                        <option value="{{ $city->id }}" {{ $gestor->cities->contains($city->id) ? 'selected' : '' }}>
                            {{ $city->name }}
                        </option>
                    @endforeach
                </select>
                <p class="text-sm text-gray-500 mt-1">Segure Ctrl (Windows) ou Cmd (Mac) para selecionar múltiplos</p>
            </div>

            {{-- Percentual sobre vendas --}}
            <div class="mb-4">
                <label for="percentual_vendas" class="block text-sm font-medium text-gray-700">Percentual sobre vendas (%)</label>
                <input type="number" step="0.01" min="0" max="100" name="percentual_vendas" value="{{ old('percentual_vendas', $gestor->percentual_vendas) }}" class="mt-1 block w-full border border-gray-300 rounded-md" required>
            </div>

            {{-- Vencimento do contrato --}}
            <div class="mb-4">
                <label for="vencimento_contrato" class="block text-sm font-medium text-gray-700">Vencimento do contrato</label>
                <input type="date" name="vencimento_contrato" value="{{ old('vencimento_contrato', $gestor->vencimento_contrato ? $gestor->vencimento_contrato->format('Y-m-d') : '') }}" class="mt-1 block w-full border border-gray-300 rounded-md">
            </div>

            {{-- Contrato assinado --}}
            <div class="mb-4 flex items-center">
                <input type="checkbox" name="contrato_assinado" id="contrato_assinado" value="1" {{ old('contrato_assinado', $gestor->contrato_assinado) ? 'checked' : '' }} class="mr-2">
                <label for="contrato_assinado" class="text-sm font-medium text-gray-700">Contrato assinado</label>
            </div>

            {{-- Anexar contrato PDF (substituir se necessário) --}}
            <div class="mb-4">
                <label for="contrato" class="block text-sm font-medium text-gray-700">Anexar novo contrato (PDF)</label>
                <input type="file" name="contrato" id="contrato" accept="application/pdf" class="mt-1 block w-full border border-gray-300 rounded-md">

                @if($gestor->contrato)
                    <p class="mt-2 text-sm text-blue-600">
                        <a href="{{ asset('storage/' . $gestor->contrato) }}" target="_blank" class="underline">Ver contrato atual</a>
                    </p>
                @endif
            </div>

            {{-- Botão --}}
            <div class="flex justify-end">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">Atualizar</button>
            </div>
        </form>
    </div>
</x-app-layout>
