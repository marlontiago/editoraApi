
<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Novo Distribuidor</h2>
    </x-slot>

    @if ($errors->any())
        <div class="mb-4 text-red-600">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="max-w-2xl mx-auto p-6 bg-white shadow rounded">
        <form action="{{ route('admin.distribuidores.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                

                <div>
                    <label>Email</label>
                    <input type="email" name="email" class="w-full border rounded px-3 py-2" required>
                </div>

                <div>
                    <label>Senha</label>
                    <input type="password" name="password" class="w-full border rounded px-3 py-2" required>
                </div>

                <div>
                    <label>Confirmar Senha</label>
                    <input type="password" name="password_confirmation" class="w-full border rounded px-3 py-2" required>
                </div>

                <div>
                    <label>Razão Social</label>
                    <input type="text" name="razao_social" class="w-full border rounded px-3 py-2" required>
                </div>

                <div>
                    <label>CNPJ</label>
                    <input type="text" name="cnpj" class="w-full border rounded px-3 py-2" required>
                </div>

                <div>
                    <label>Representante Legal</label>
                    <input type="text" name="representante_legal" class="w-full border rounded px-3 py-2" required>
                </div>

                <div>
                    <label>CPF</label>
                    <input type="text" name="cpf" class="w-full border rounded px-3 py-2" required>
                </div>

                <div>
                    <label>RG</label>
                    <input type="text" name="rg" class="w-full border rounded px-3 py-2" required>
                </div>

                <div>
                    <label>Telefone</label>
                    <input type="text" name="telefone" class="w-full border rounded px-3 py-2">
                </div>

                <div>
                    <label>Endereço Completo</label>
                    <input type="text" name="endereco_completo" class="w-full border rounded px-3 py-2">
                </div>

                <div>
                    <label>Percentual sobre vendas (%)</label>
                    <input type="number" name="percentual_vendas" step="0.01" max="100" class="w-full border rounded px-3 py-2" required>
                </div>

                <div>
                    <label>Vencimento do contrato</label>
                    <input type="date" name="vencimento_contrato" class="w-full border rounded px-3 py-2">
                </div>

                <div class="flex items-center space-x-2">
                    <input type="hidden" name="contrato_assinado" value="0">
                    <input type="checkbox" name="contrato_assinado" value="1" class="rounded border-gray-300" {{ old('contrato_assinado') ? 'checked' : '' }}>
                    <label>Contrato Assinado?</label>
                </div>

                <div>
                    <label>Anexar contrato (PDF)</label>
                    <input type="file" name="contrato" accept=".pdf" class="w-full border rounded px-3 py-2">
                </div>

                <div>
                    <label>Gestor</label>
                    <select name="gestor_id" class="w-full border rounded px-3 py-2" required>
                        @foreach($gestores as $gestor)
                            <option value="{{ $gestor->id }}">{{ $gestor->razao_social }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mt-6">
                <label class="block font-medium text-sm text-gray-700 mb-2">Cidades de atuação</label>
                <select name="cities[]" id="cities" multiple class="form-select mt-1 block w-full">
                    @foreach($cities as $city)
                        @php
                            $ocupada = DB::table('city_distribuidor')->where('city_id', $city->id)->exists();
                        @endphp
                        <option value="{{ $city->id }}" {{ $ocupada ? 'disabled' : '' }}>
                            {{ $city->name }} {{ $ocupada ? '(ocupada)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mt-6">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Salvar</button>
            </div>
        </form>
    </div>
</x-app-layout>

