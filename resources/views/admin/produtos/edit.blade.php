<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Editar Produto</h2>
    </x-slot>

    <div class="p-6 max-w-xl mx-auto bg-white rounded shadow">
        @if ($errors->any())
            <div class="mb-4 p-4 bg-red-100 text-red-800 rounded">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.produtos.update', $produto) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            @method('PUT')

            <!-- Nome -->
            <div>
                <label class="block">Nome</label>
                <input type="text" name="nome" value="{{ old('nome', $produto->nome) }}" class="form-input w-full" required>
            </div>

            <!-- Coleção -->
            <div>
                <label class="block">Coleção</label>
                <select name="colecao_id" class="form-input w-full">
                    <option value="">Selecione</option>
                    @foreach ($colecoes as $colecao)
                        <option value="{{ $colecao->id }}" {{ old('colecao_id', $produto->colecao_id) == $colecao->id ? 'selected' : '' }}>
                            {{ $colecao->nome }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Título -->
            <div>
                <label class="block">Título</label>
                <input type="text" name="titulo" value="{{ old('titulo', $produto->titulo) }}" class="form-input w-full">
            </div>

            <!-- ISBN -->
            <div>
                <label class="block">ISBN</label>
                <input type="text" name="isbn" value="{{ old('isbn', $produto->isbn) }}" class="form-input w-full">
            </div>

            <!-- Autores -->
            <div>
                <label class="block">Autor(es)</label>
                <input type="text" name="autores" value="{{ old('autores', $produto->autores) }}" class="form-input w-full">
            </div>

            <!-- Edição -->
            <div>
                <label class="block">Edição</label>
                <input type="text" name="edicao" value="{{ old('edicao', $produto->edicao) }}" class="form-input w-full">
            </div>

            <!-- Ano -->
            <div>
                <label class="block">Ano</label>
                <input type="number" name="ano" value="{{ old('ano', $produto->ano) }}" class="form-input w-full">
            </div>

            <!-- Número de páginas -->
            <div>
                <label class="block">Número de Páginas</label>
                <input type="number" name="numero_paginas" value="{{ old('numero_paginas', $produto->numero_paginas) }}" class="form-input w-full">
            </div>

            <!-- Peso -->
            <div>
                <label class="block">Peso (kg)</label>
                <input type="number" step="0.001" name="peso" value="{{ old('peso', $produto->peso) }}" class="form-input w-full">
            </div>

            <!-- Ano Escolar -->
            <div>
                <label class="block">Ano Escolar</label>
                <select name="ano_escolar" class="form-input w-full">
                    <option value="">Selecione</option>
                    @foreach(['Educação Infantil', 'Ensino Fundamental 1', 'Ensino Fundamental 2', 'Ensino Médio'] as $opcao)
                        <option value="{{ $opcao }}" {{ old('ano_escolar', $produto->ano_escolar) == $opcao ? 'selected' : '' }}>
                            {{ $opcao }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Descrição -->
            <div>
                <label class="block">Descrição</label>
                <textarea name="descricao" class="form-input w-full">{{ old('descricao', $produto->descricao) }}</textarea>
            </div>

            <!-- Preço -->
            <div>
                <label class="block">Preço</label>
                <input type="number" step="0.01" name="preco" value="{{ old('preco', $produto->preco) }}" class="form-input w-full" required>
            </div>

            <!-- Estoque -->
            <div>
                <label class="block">Estoque</label>
                <input type="number" name="quantidade_estoque" value="{{ old('quantidade_estoque', $produto->quantidade_estoque) }}" class="form-input w-full" required>
            </div>

            <!-- Imagem -->
            <div>
                <label class="block">Imagem do Produto</label>
                <input type="file" name="imagem" class="form-input w-full">
                @if ($produto->imagem && Storage::disk('public')->exists($produto->imagem))
                    <img src="{{ asset('storage/' . $produto->imagem) }}" alt="{{ $produto->nome }}" class="mt-2 w-32 h-32 object-cover rounded">
                @endif
            </div>

            <!-- Botões -->
            <div class="flex items-center space-x-4 mt-4">
                <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded">Atualizar</button>
                <a href="{{ route('admin.produtos.index') }}" class="text-gray-600 hover:underline">Cancelar</a>
            </div>
        </form>
    </div>
</x-app-layout>
