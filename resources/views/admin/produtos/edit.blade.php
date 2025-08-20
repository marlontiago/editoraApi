<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Editar Produto</h2>
    </x-slot>

    <div class="p-6 mx-auto max-w-6xl">
        {{-- Resumo de validação --}}
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

        <form action="{{ route('admin.produtos.update', $produto) }}" method="POST" enctype="multipart/form-data"
              class="bg-white shadow rounded-lg p-6 grid grid-cols-12 gap-4">
            @csrf
            @method('PUT')

            {{-- ====== Bloco: Básico ====== --}}
            <div class="col-span-12">
                <h3 class="text-sm font-semibold text-gray-700">Informações básicas</h3>
                <div class="mt-3 grid grid-cols-12 gap-4">
                    <div class="col-span-12 md:col-span-6">
                        <label class="block text-sm font-medium text-gray-700">Nome <span class="text-red-600">*</span></label>
                        <input type="text" name="nome" value="{{ old('nome', $produto->nome) }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                    </div>

                    <div class="col-span-12 md:col-span-6">
                        <label class="block text-sm font-medium text-gray-700">Coleção</label>
                        <select name="colecao_id"
                                class="mt-1 block w-full rounded-md border-gray-300 bg-white shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Selecione</option>
                            @foreach ($colecoes as $colecao)
                                <option value="{{ $colecao->id }}" {{ old('colecao_id', $produto->colecao_id) == $colecao->id ? 'selected' : '' }}>
                                    {{ $colecao->nome }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-span-12 md:col-span-6">
                        <label class="block text-sm font-medium text-gray-700">Título</label>
                        <input type="text" name="titulo" value="{{ old('titulo', $produto->titulo) }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div class="col-span-12 md:col-span-3">
                        <label class="block text-sm font-medium text-gray-700">ISBN</label>
                        <input type="text" name="isbn" value="{{ old('isbn', $produto->isbn) }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div class="col-span-12 md:col-span-3">
                        <label class="block text-sm font-medium text-gray-700">Edição</label>
                        <input type="text" name="edicao" value="{{ old('edicao', $produto->edicao) }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
            </div>

            {{-- ====== Bloco: Detalhes do livro ====== --}}
            <div class="col-span-12">
                <h3 class="text-sm font-semibold text-gray-700">Detalhes do livro</h3>
                <div class="mt-3 grid grid-cols-12 gap-4">
                    <div class="col-span-12 md:col-span-6">
                        <label class="block text-sm font-medium text-gray-700">Autor(es)</label>
                        <input type="text" name="autores" value="{{ old('autores', $produto->autores) }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div class="col-span-12 md:col-span-3">
                        <label class="block text-sm font-medium text-gray-700">Ano</label>
                        <input type="number" name="ano" value="{{ old('ano', $produto->ano) }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div class="col-span-12 md:col-span-3">
                        <label class="block text-sm font-medium text-gray-700">Número de Páginas</label>
                        <input type="number" name="numero_paginas" value="{{ old('numero_paginas', $produto->numero_paginas) }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div class="col-span-12 md:col-span-3">
                        <label class="block text-sm font-medium text-gray-700">Peso</label>
                        <div class="mt-1 flex rounded-md shadow-sm">
                            <input type="number" step="0.001" name="peso" value="{{ old('peso', $produto->peso) }}"
                                   class="flex-1 rounded-l-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <span class="inline-flex items-center rounded-r-md border border-l-0 border-gray-300 bg-gray-50 px-3 text-sm text-gray-600">kg</span>
                        </div>
                    </div>

                    <div class="col-span-12 md:col-span-3">
                        <label for="quantidade_por_caixa" class="block text-sm font-medium text-gray-700">Quantidade por Caixa</label>
                        <input type="number" name="quantidade_por_caixa" id="quantidade_por_caixa"
                               value="{{ old('quantidade_por_caixa', $produto->quantidade_por_caixa ?? 1) }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               required min="1">
                    </div>

                    <div class="col-span-12 md:col-span-6">
                        <label class="block text-sm font-medium text-gray-700">Ano Escolar</label>
                        <select name="ano_escolar"
                                class="mt-1 block w-full rounded-md border-gray-300 bg-white shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Selecione</option>
                            @foreach (['Ens Inf' => 'Educação Infantil', 'Fund 1' => 'Ens. Fundamental 1', 'Fund 2' => 'Ens. Fundamental 2', 'EM' => 'Ensino Médio'] as $valor => $label)
                                <option value="{{ $valor }}" @selected(old('ano_escolar') == $valor)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-span-12">
                        <label class="block text-sm font-medium text-gray-700">Descrição</label>
                        <textarea name="descricao" rows="4"
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">{{ old('descricao', $produto->descricao) }}</textarea>
                    </div>
                </div>
            </div>

            {{-- ====== Bloco: Preço & Estoque ====== --}}
            <div class="col-span-12">
                <h3 class="text-sm font-semibold text-gray-700">Preço & estoque</h3>
                <div class="mt-3 grid grid-cols-12 gap-4">
                    <div class="col-span-12 md:col-span-6">
                        <label class="block text-sm font-medium text-gray-700">Preço <span class="text-red-600">*</span></label>
                        <div class="mt-1 flex rounded-md shadow-sm">
                            <span class="inline-flex items-center rounded-l-md border border-gray-300 bg-gray-50 px-3 text-sm text-gray-600">R$</span>
                            <input type="number" step="0.01" name="preco" value="{{ old('preco', $produto->preco) }}"
                                   class="flex-1 rounded-r-md border border-l-0 border-gray-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                        </div>
                    </div>

                    <div class="col-span-12 md:col-span-6">
                        <label class="block text-sm font-medium text-gray-700">Estoque <span class="text-red-600">*</span></label>
                        <input type="number" name="quantidade_estoque" value="{{ old('quantidade_estoque', $produto->quantidade_estoque) }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                    </div>
                </div>
            </div>

            {{-- ====== Bloco: Mídia ====== --}}
            <div class="col-span-12">
                <h3 class="text-sm font-semibold text-gray-700">Mídia</h3>
                <div class="mt-3 grid grid-cols-12 gap-4">
                    <div class="col-span-12 md:col-span-6">
                        <label class="block text-sm font-medium text-gray-700">Imagem do Produto</label>
                        <input type="file" name="imagem" accept=".jpg,.jpeg,.png,.webp"
                               class="mt-1 block w-full rounded-md border-gray-300 text-sm file:mr-4 file:rounded-md file:border-0 file:bg-gray-100 file:px-4 file:py-2 file:text-gray-700 hover:file:bg-gray-200">
                        <p class="mt-1 text-xs text-gray-500">Formatos: JPG, PNG, WEBP. Máx 5MB.</p>
                    </div>

                    <div class="col-span-12 md:col-span-6">
                        @if ($produto->imagem && Storage::disk('public')->exists($produto->imagem))
                            <div>
                                <span class="block text-sm font-medium text-gray-700">Pré-visualização atual</span>
                                <img src="{{ asset('storage/' . $produto->imagem) }}" alt="{{ $produto->nome }}"
                                     class="mt-2 w-36 h-36 object-cover rounded-lg shadow">
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- ====== Ações ====== --}}
            <div class="col-span-12 flex items-center justify-end gap-3 pt-2">
                <a href="{{ route('admin.produtos.index') }}"
                   class="inline-flex h-10 items-center rounded-md border px-4 text-sm hover:bg-gray-50">
                    Cancelar
                </a>
                <button type="submit"
                        class="inline-flex h-10 items-center rounded-md bg-blue-600 px-5 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    Atualizar
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
