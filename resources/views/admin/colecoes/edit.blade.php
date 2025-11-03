{{-- resources/views/admin/colecoes/edit.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Editar Coleção</h2>
    </x-slot>

    <style>[x-cloak]{display:none!important}</style>

    <div class="max-w-6xl mx-auto p-6"
         x-data="colecaoEdit({ produtos: @js($produtos), pre: @js($selecionados) })" x-init="init()">

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

        <form action="{{ route('admin.colecoes.update', $colecao) }}" method="POST"
              class="bg-white shadow rounded-lg p-6 grid grid-cols-12 gap-4">
            @csrf @method('PUT')

            <div class="col-span-12 md:col-span-6">
                <label class="block text-sm font-medium mb-1">Nome da coleção <span class="text-red-600">*</span></label>
                <input type="text" name="nome" required class="w-full border rounded px-3 py-2" value="{{ old('nome', $colecao->nome) }}">
            </div>

            <div class="col-span-12">
                <div class="flex items-center justify-between mb-2">
                    <label class="block text-sm font-medium">Produtos da coleção</label>
                    <div class="text-xs text-gray-600">
                        Selecionados: <span x-text="selecionados.length"></span>
                    </div>
                </div>

                <div class="mb-2 flex gap-2">
                    <input type="text" x-model="busca" placeholder="Filtrar por título/ISBN..."
                           class="w-full md:w-96 border rounded px-3 py-2">
                    <button type="button" @click="toggleTodos(true)" class="px-3 py-1.5 rounded border">Marcar todos</button>
                    <button type="button" @click="toggleTodos(false)" class="px-3 py-1.5 rounded border">Desmarcar todos</button>
                </div>

                <div class="max-h-80 overflow-auto border rounded">
                    <template x-for="p in filtrados()" :key="p.id">
                        <label class="flex items-center gap-3 border-b px-3 py-2">
                            <input type="checkbox" :value="p.id" name="produtos[]"
                                   x-model="selecionados">
                            <div class="text-sm">
                                <div class="font-medium" x-text="p.titulo ?? ('Produto #'+p.id)"></div>
                                <div class="text-gray-500" x-text="(p.isbn ? ('ISBN: '+p.isbn+' · ') : '') + (p.ano ?? '') + (p.edicao ? (' · Edição '+p.edicao) : '')"></div>
                                <template x-if="p.colecao_id && !selecionados.includes(p.id) && p.colecao_id !== {{ $colecao->id }}">
                                    <div class="text-xs text-red-600">⚠ Já pertence a outra coleção</div>
                                </template>
                            </div>
                        </label>
                    </template>
                    <div class="px-3 py-4 text-sm text-gray-500" x-show="filtrados().length === 0">Nenhum produto encontrado…</div>
                </div>
            </div>

            <div class="col-span-12 flex justify-between items-center">
                <div class="text-xs text-gray-500">
                    Ao salvar, os produtos não marcados serão desassociados desta coleção.
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('admin.colecoes.index') }}" class="px-3 py-1.5 rounded border">Cancelar</a>
                    <button class="px-3 py-1.5 rounded bg-blue-600 text-white">Salvar alterações</button>
                </div>
            </div>
        </form>
    </div>

    <script>
        function colecaoEdit({ produtos, pre }) {
            return {
                produtos,
                busca: '',
                selecionados: pre ?? [],
                init(){},
                filtrados(){
                    const q = this.busca.toLowerCase().trim();
                    if (!q) return this.produtos;
                    return this.produtos.filter(p => {
                        const t = (p.titulo || '').toLowerCase();
                        const i = (p.isbn || '').toLowerCase();
                        return t.includes(q) || i.includes(q);
                    });
                },
                toggleTodos(v){
                    if (v) this.selecionados = this.filtrados().map(p => p.id);
                    else this.selecionados = [];
                }
            }
        }
    </script>
</x-app-layout>
