<x-app-layout>
    <x-slot name="header"><h2 class="text-2xl font-bold">Editar Advogado</h2></x-slot>

    <div class="p-6 max-w-4xl mx-auto">
        <form action="{{ route('admin.advogados.update', $advogado) }}" method="POST" class="bg-white border rounded p-6 space-y-6">
            @csrf @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block mb-1">Nome *</label>
                    <input type="text" name="nome" class="w-full border rounded px-3 py-2" value="{{ old('nome', $advogado->nome) }}" required>
                </div>
                <div>
                    <label class="block mb-1">Email *</label>
                    <input type="email" name="email" class="w-full border rounded px-3 py-2" value="{{ old('email', $advogado->email) }}" required>
                </div>
                <div>
                    <label class="block mb-1">Telefone</label>
                    <input type="text" name="telefone" class="w-full border rounded px-3 py-2" value="{{ old('telefone', $advogado->telefone) }}">
                </div>
                <div>
                    <label class="block mb-1">OAB *</label>
                    <input type="text" name="oab" class="w-full border rounded px-3 py-2" value="{{ old('oab', $advogado->oab) }}" required>
                </div>

                <div class="md:col-span-2">
                    <label class="block mb-1">Logradouro</label>
                    <input type="text" name="logradouro" class="w-full border rounded px-3 py-2" value="{{ old('logradouro', $advogado->logradouro) }}">
                </div>
                <div>
                    <label class="block mb-1">Número</label>
                    <input type="text" name="numero" class="w-full border rounded px-3 py-2" value="{{ old('numero', $advogado->numero) }}">
                </div>
                <div>
                    <label class="block mb-1">Complemento</label>
                    <input type="text" name="complemento" class="w-full border rounded px-3 py-2" value="{{ old('complemento', $advogado->complemento) }}">
                </div>
                <div>
                    <label class="block mb-1">Bairro</label>
                    <input type="text" name="bairro" class="w-full border rounded px-3 py-2" value="{{ old('bairro', $advogado->bairro) }}">
                </div>
                <div>
                    <label class="block mb-1">Cidade</label>
                    <input type="text" name="cidade" class="w-full border rounded px-3 py-2" value="{{ old('cidade', $advogado->cidade) }}">
                </div>
                <div>
                    <label class="block mb-1">Estado (UF)</label>
                    <input type="text" name="estado" maxlength="2" class="w-full border rounded px-3 py-2 uppercase" value="{{ old('estado', $advogado->estado) }}">
                </div>
                <div>
                    <label class="block mb-1">CEP</label>
                    <input type="text" name="cep" class="w-full border rounded px-3 py-2" value="{{ old('cep', $advogado->cep) }}">
                </div>
            </div>

            <div class="flex gap-3">
                <a href="{{ route('admin.advogados.index') }}" class="px-4 py-2 border rounded">Cancelar</a>
                <button class="px-4 py-2 rounded bg-blue-600 text-white">Atualizar</button>
            </div>
        </form>
    </div>
</x-app-layout>
