<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Cadastrar Novo Produto</h2>
    </x-slot>

    <div class="max-w-6xl mx-auto p-6">
        {{-- Alerta de validação (resumo) --}}
        @if ($errors->any())
            <div class="mb-6 rounded-md border border-red-300 bg-red-50 p-4 text-red-800">
                <div class="font-semibold mb-2">Corrija os campos abaixo:</div>
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li class="text-sm">{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.produtos.store') }}" method="POST" enctype="multipart/form-data"
              class="bg-white shadow rounded-lg p-6 grid grid-cols-12 gap-4">
            @csrf

            {{-- Título (único, substitui "Nome") --}}
            <div class="col-span-12 md:col-span-8">
                <label for="titulo" class="block text-sm font-medium text-gray-700">Título <span class="text-red-600">*</span></label>
                <input type="text" id="titulo" name="titulo" value="{{ old('titulo') }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                       required>
                @error('titulo') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Coleção --}}
            <div class="col-span-12 md:col-span-4">
                <label for="colecao_id" class="block text-sm font-medium text-gray-700">Coleção</label>
                <select id="colecao_id" name="colecao_id"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500">
                    <option value="">Selecione</option>
                    @foreach ($colecoes as $colecao)
                        <option value="{{ $colecao->id }}" @selected(old('colecao_id') == $colecao->id)>{{ $colecao->nome }}</option>
                    @endforeach
                </select>
                @error('colecao_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- ISBN e Edição --}}
            <div class="col-span-12 md:col-span-6">
                <label for="isbn" class="block text-sm font-medium text-gray-700">ISBN</label>
                <input type="text" id="isbn" name="isbn" value="{{ old('isbn') }}" maxlength="17"
       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500">
                @error('isbn') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-12 md:col-span-6">
                <label for="edicao" class="block text-sm font-medium text-gray-700">Edição</label>
                <input type="text" id="edicao" name="edicao" value="{{ old('edicao') }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500">
                @error('edicao') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Ano, Nº de Páginas, Quantidade por Caixa --}}
            <div class="col-span-12 md:col-span-4">
                <label for="ano" class="block text-sm font-medium text-gray-700">Ano</label>
                <input type="number" id="ano" name="ano" value="{{ old('ano') }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500">
                @error('ano') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-12 md:col-span-4">
                <label for="numero_paginas" class="block text-sm font-medium text-gray-700">Nº de Páginas</label>
                <input type="number" id="numero_paginas" name="numero_paginas" value="{{ old('numero_paginas') }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500">
                @error('numero_paginas') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-12 md:col-span-4">
                <label for="quantidade_por_caixa" class="block text-sm font-medium text-gray-700">Quantidade por Caixa <span class="text-red-600">*</span></label>
                <input type="number" id="quantidade_por_caixa" name="quantidade_por_caixa" min="0"
                       value="{{ old('quantidade_por_caixa', 1) }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500" >
                @error('quantidade_por_caixa') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Peso e Ano Escolar --}}
            <div class="col-span-12 md:col-span-4">
                <label for="peso" class="block text-sm font-medium text-gray-700">Peso</label>
                <div class="mt-1 flex rounded-md shadow-sm">
                    <input type="number" step="0.001" id="peso" name="peso" value="{{ old('peso') }}"
                           class="flex-1 rounded-l-md border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-2 focus:ring-blue-500">
                    <span class="inline-flex items-center rounded-r-md border border-l-0 border-gray-300 bg-gray-50 px-3 text-sm text-gray-600">kg</span>
                </div>
                @error('peso') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-12 md:col-span-8">
                <label for="ano_escolar" class="block text-sm font-medium text-gray-700">Ano Escolar</label>
                <select id="ano_escolar" name="ano_escolar"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500">
                    <option value="">Selecione</option>
                    @foreach (['Ens Inf' => 'Educação Infantil', 'Fund 1' => 'Ens. Fundamental 1', 'Fund 2' => 'Ens. Fundamental 2', 'EM' => 'Ensino Médio'] as $valor => $label)
                        <option value="{{ $valor }}" @selected(old('ano_escolar') == $valor)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('ano_escolar') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Autores --}}
            <div class="col-span-12">
                <label for="autores" class="block text-sm font-medium text-gray-700">Autor(es)</label>
                <input type="text" id="autores" name="autores" value="{{ old('autores') }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500">
                <p class="mt-1 text-xs text-gray-500">Separe múltiplos autores por vírgula.</p>
                @error('autores') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Descrição --}}
            <div class="col-span-12">
                <label for="descricao" class="block text-sm font-medium text-gray-700">Descrição</label>
                <textarea id="descricao" name="descricao" rows="4"
                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500">{{ old('descricao') }}</textarea>
                @error('descricao') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Preço e Estoque --}}
            <div class="col-span-12 md:col-span-6">
                <label for="preco" class="block text-sm font-medium text-gray-700">Preço </label>
                <div class="mt-1 flex rounded-md shadow-sm">
                    <span class="inline-flex items-center rounded-l-md border border-gray-300 bg-gray-50 px-3 text-sm text-gray-600">R$</span>
                    <input type="number" step="0.01" id="preco" name="preco" value="{{ old('preco') }}"
                           class="flex-1 rounded-r-md border border-l-0 border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-2 focus:ring-blue-500">
                </div>
                @error('preco') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-12 md:col-span-6">
                <label for="quantidade_estoque" class="block text-sm font-medium text-gray-700">Estoque</label>
                <input type="number" id="quantidade_estoque" name="quantidade_estoque" value="{{ old('quantidade_estoque') }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500">
                @error('quantidade_estoque') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Imagem --}}
            <div class="col-span-12">
                <label for="imagem" class="block text-sm font-medium text-gray-700">Imagem do Produto</label>
                <input type="file" id="imagem" name="imagem"
                       class="mt-1 block w-full rounded-md border-gray-300 text-sm file:mr-4 file:rounded-md file:border-0 file:bg-gray-100 file:px-4 file:py-2 file:text-gray-700 hover:file:bg-gray-200">
                @error('imagem') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                <p class="mt-1 text-xs text-gray-500">Formatos aceitos: JPG/PNG/WEBP. Máx. 2 MB.</p>
            </div>

            {{-- Ações --}}
            <div class="col-span-12 flex items-center justify-end gap-3 pt-2">
                <a href="{{ route('admin.produtos.index') }}"
                   class="inline-flex h-10 items-center rounded-md border px-4 text-sm hover:bg-gray-50">
                    Cancelar
                </a>
                <button type="submit"
                        class="inline-flex h-10 items-center rounded-md bg-blue-600 px-5 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    Salvar Produto
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
