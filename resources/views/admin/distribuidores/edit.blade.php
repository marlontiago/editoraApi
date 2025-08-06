<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Editar Distribuidor</h2>
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

    <div class="max-w-5xl mx-auto p-6 bg-white shadow rounded">
        <form action="{{ route('admin.distribuidores.update', $distribuidor->id) }}" method="POST" enctype="multipart/form-data">
            @csrf @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label>Nome de Usuário</label>
                    <input type="text" name="name" class="w-full border rounded px-3 py-2" value="{{ old('name', $distribuidor->user->name) }}" required>
                </div>

                <div>
                    <label>Email</label>
                    <input type="email" name="email" class="w-full border rounded px-3 py-2" value="{{ old('email', $distribuidor->user->email) }}" required>
                </div>

                <div>
                    <label>Senha (opcional)</label>
                    <input type="password" name="password" class="w-full border rounded px-3 py-2">
                </div>

                <div>
                    <label>Confirmar Senha</label>
                    <input type="password" name="password_confirmation" class="w-full border rounded px-3 py-2">
                </div>

                <div>
                    <label>Razão Social</label>
                    <input type="text" name="razao_social" class="w-full border rounded px-3 py-2" value="{{ old('razao_social', $distribuidor->razao_social) }}" required>
                </div>

                <div>
                    <label>CNPJ</label>
                    <input type="text" name="cnpj" class="w-full border rounded px-3 py-2" value="{{ old('cnpj', $distribuidor->cnpj) }}" required>
                </div>

                <div>
                    <label>Representante Legal</label>
                    <input type="text" name="representante_legal" class="w-full border rounded px-3 py-2" value="{{ old('representante_legal', $distribuidor->representante_legal) }}" required>
                </div>

                <div>
                    <label>CPF</label>
                    <input type="text" name="cpf" class="w-full border rounded px-3 py-2" value="{{ old('cpf', $distribuidor->cpf) }}" required>
                </div>

                <div>
                    <label>RG</label>
                    <input type="text" name="rg" class="w-full border rounded px-3 py-2" value="{{ old('rg', $distribuidor->rg) }}" required>
                </div>

                <div>
                    <label>Telefone</label>
                    <input type="text" name="telefone" class="w-full border rounded px-3 py-2" value="{{ old('telefone', $distribuidor->telefone) }}">
                </div>

                <div>
                    <label>Endereço Completo</label>
                    <input type="text" name="endereco_completo" class="w-full border rounded px-3 py-2" value="{{ old('endereco_completo', $distribuidor->endereco_completo) }}">
                </div>

                <div>
                    <label>Percentual sobre vendas (%)</label>
                    <input type="number" name="percentual_vendas" step="0.01" max="100" class="w-full border rounded px-3 py-2" value="{{ old('percentual_vendas', $distribuidor->percentual_vendas) }}" required>
                </div>

                <div>
                    <label>Vencimento do contrato</label>
                    <input type="date" name="vencimento_contrato" class="w-full border rounded px-3 py-2" value="{{ old('vencimento_contrato', $distribuidor->vencimento_contrato) }}">
                </div>

                <div class="flex items-center space-x-2">
                    <input type="hidden" name="contrato_assinado" value="0">
                    <input type="checkbox" name="contrato_assinado" value="1" class="rounded border-gray-300" {{ old('contrato_assinado') ? 'checked' : '' }}>
                    <label>Contrato Assinado?</label>
                </div>

                <div>
                    <label>Contrato Atual:</label><br>
                    @if($distribuidor->contrato)
                        <a href="{{ Storage::url($distribuidor->contrato) }}" target="_blank" class="text-blue-600 underline">Ver Contrato</a>
                    @else
                        <p class="text-gray-500">Nenhum contrato anexado</p>
                    @endif
                    <input type="file" name="contrato" accept=".pdf" class="mt-2 w-full border rounded px-3 py-2">
                </div>

                <div>
                    <label>Gestor</label>
                    <select name="gestor_id" class="w-full border rounded px-3 py-2" required>
                        @foreach($gestores as $gestor)
                            <option value="{{ $gestor->id }}" @selected($distribuidor->gestor_id == $gestor->id)>{{ $gestor->razao_social }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mt-6">
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

            <div class="mt-6">
                <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded">Atualizar</button>
            </div>
        </form>
    </div>
</x-app-layout>
