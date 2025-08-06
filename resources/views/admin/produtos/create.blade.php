<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Cadastrar Novo Produto</h2>
    </x-slot>

    <div class="p-6 max-w-6xl mt-6 mx-auto bg-white rounded shadow">
        @if ($errors->any())
            <div class="p-4 mb-6 bg-red-100 text-red-800 rounded">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.produtos.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <!-- Nome -->
            <div>
                <label for="nome" class="block text-sm font-medium text-gray-700">Nome</label>
                <input type="text" name="nome" id="nome" class="form-input mt-1 block w-full" required>
            </div>

            <!-- Título e Coleção -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="titulo" class="block text-sm font-medium text-gray-700">Título</label>
                    <input type="text" name="titulo" id="titulo" class="form-input mt-1 block w-full">
                </div>
                <div>
                    <label for="colecao_id" class="block text-sm font-medium text-gray-700">Coleção</label>
                    <select name="colecao_id" id="colecao_id" class="form-input mt-1 block w-full">
                        <option value="">Selecione</option>
                        @foreach ($colecoes as $colecao)
                            <option value="{{ $colecao->id }}">{{ $colecao->nome }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- ISBN e Edição -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="isbn" class="block text-sm font-medium text-gray-700">ISBN</label>
                    <input type="text" name="isbn" id="isbn" class="form-input mt-1 block w-full">
                </div>
                <div>
                    <label for="edicao" class="block text-sm font-medium text-gray-700">Edição</label>
                    <input type="text" name="edicao" id="edicao" class="form-input mt-1 block w-full">
                </div>
            </div>

            <!-- Ano e Nº de Páginas -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="ano" class="block text-sm font-medium text-gray-700">Ano</label>
                    <input type="number" name="ano" id="ano" class="form-input mt-1 block w-full">
                </div>
                <div>
                    <label for="numero_paginas" class="block text-sm font-medium text-gray-700">Nº de Páginas</label>
                    <input type="number" name="numero_paginas" id="numero_paginas" class="form-input mt-1 block w-full">
                </div>
            </div>

            <!-- Peso e Ano Escolar -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="peso" class="block text-sm font-medium text-gray-700">Peso (kg)</label>
                    <input type="number" step="0.001" name="peso" id="peso" class="form-input mt-1 block w-full">
                </div>
                <div>
                    <label for="ano_escolar" class="block text-sm font-medium text-gray-700">Ano Escolar</label>
                    <select name="ano_escolar" id="ano_escolar" class="form-input mt-1 block w-full">
                        <option value="">Selecione</option>
                        @foreach (['Educação Infantil', 'Ensino Fundamental 1', 'Ensino Fundamental 2', 'Ensino Médio'] as $opcao)
                            <option value="{{ $opcao }}">{{ $opcao }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Autores -->
            <div>
                <label for="autores" class="block text-sm font-medium text-gray-700">Autor(es)</label>
                <input type="text" name="autores" id="autores" class="form-input mt-1 block w-full">
            </div>

            <!-- Descrição -->
            <div>
                <label for="descricao" class="block text-sm font-medium text-gray-700">Descrição</label>
                <textarea name="descricao" id="descricao" rows="3" class="form-input mt-1 block w-full"></textarea>
            </div>

            <!-- Preço e Estoque -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="preco" class="block text-sm font-medium text-gray-700">Preço</label>
                    <input type="number" step="0.01" name="preco" id="preco" class="form-input mt-1 block w-full" required>
                </div>
                <div>
                    <label for="quantidade_estoque" class="block text-sm font-medium text-gray-700">Estoque</label>
                    <input type="number" name="quantidade_estoque" id="quantidade_estoque" class="form-input mt-1 block w-full" required>
                </div>
            </div>

            <!-- Imagem -->
            <div>
                <label for="imagem" class="block text-sm font-medium text-gray-700">Imagem do Produto</label>
                <input type="file" name="imagem" id="imagem" class="form-input mt-1 block w-full">
            </div>

            <!-- Botão -->
            <div>
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                    Salvar Produto
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
