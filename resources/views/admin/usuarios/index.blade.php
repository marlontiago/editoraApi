<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold text-gray-800 leading-tight">
                    {{ __('Usuários Cadastrados') }}
                </h2>
                <p class="text-sm text-gray-500 mt-1">
                    Gerencie usuários, papéis e permissões de forma rápida.
                </p>
            </div>

            <a
                href="{{ route('admin.usuarios.create') }}"
                class="inline-flex items-center gap-2 rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800 transition"
            >
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14M5 12h14"/>
                </svg>
                Criar usuário
            </a>
        </div>
    </x-slot>

    <div class="max-w-full mx-auto p-6 space-y-4">

        {{-- Flash --}}
        @if (session('success'))
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-800">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-100">

            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="text-base font-semibold text-gray-900">Lista de usuários</h3>
                <span class="text-xs text-gray-500">
                    Total: {{ $usuarios->count() }}
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-left">
                    <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-600">
                        <tr class="border-b border-gray-100">
                            <th class="px-4 py-3 whitespace-nowrap">Nome</th>
                            <th class="px-4 py-3 whitespace-nowrap">Email</th>
                            <th class="px-4 py-3 whitespace-nowrap">Papéis</th>
                            <th class="px-4 py-3 whitespace-nowrap">Permissões</th>
                            <th class="px-4 py-3 whitespace-nowrap text-right">Ações</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100">
                        @forelse ($usuarios as $user)
                            <tr class="hover:bg-gray-50/60">
                                {{-- Nome --}}
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-900 leading-tight">
                                        {{ $user->name }}
                                    </div>
                                </td>

                                {{-- Email --}}
                                <td class="px-4 py-3">
                                    <div class="text-sm text-gray-700">
                                        {{ $user->email }}
                                    </div>
                                </td>

                                {{-- Papéis --}}
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-1.5">
                                        @forelse ($user->roles as $role)
                                            <span class="inline-flex items-center rounded-full bg-blue-50 px-2 py-0.5 text-[11px] font-medium text-blue-700 ring-1 ring-blue-100">
                                                {{ ucfirst($role->name) }}
                                            </span>
                                        @empty
                                            <span class="text-xs text-gray-400">—</span>
                                        @endforelse
                                    </div>
                                </td>

                                {{-- Permissões (compacto) --}}
                                <td class="px-4 py-3 align-top">
                                    <form
                                        action="{{ route('admin.usuarios.permissoes.update', $user) }}"
                                        method="POST"
                                        class="flex items-start gap-3"
                                    >
                                        @csrf

                                        <div class="grid grid-cols-2 gap-x-4 gap-y-1.5">
                                            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                                <input
                                                    type="checkbox"
                                                    name="permissions[]"
                                                    value="pedido.criar"
                                                    class="h-4 w-4 rounded border-gray-300 text-gray-900 focus:ring-gray-900"
                                                    {{ $user->hasDirectPermission('pedido.criar') ? 'checked' : '' }}
                                                >
                                                <span class="leading-none">Pedidos</span>
                                            </label>

                                            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                                <input
                                                    type="checkbox"
                                                    name="permissions[]"
                                                    value="relatorios.acessar"
                                                    class="h-4 w-4 rounded border-gray-300 text-gray-900 focus:ring-gray-900"
                                                    {{ $user->hasDirectPermission('relatorios.acessar') ? 'checked' : '' }}
                                                >
                                                <span class="leading-none">Relatórios</span>
                                            </label>

                                            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                                <input
                                                    type="checkbox"
                                                    name="permissions[]"
                                                    value="gerenciar.usuarios"
                                                    class="h-4 w-4 rounded border-gray-300 text-gray-900 focus:ring-gray-900"
                                                    {{ $user->hasDirectPermission('gerenciar.usuarios') ? 'checked' : '' }}
                                                >
                                                <span class="leading-none">Usuários</span>
                                            </label>

                                            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                                <input
                                                    type="checkbox"
                                                    name="permissions[]"
                                                    value="estoque.gerenciar"
                                                    class="h-4 w-4 rounded border-gray-300 text-gray-900 focus:ring-gray-900"
                                                    {{ $user->hasDirectPermission('estoque.gerenciar') ? 'checked' : '' }}
                                                >
                                                <span class="leading-none">Estoque</span>
                                            </label>

                                            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                                <input
                                                    type="checkbox"
                                                    name="permissions[]"
                                                    value="dashboard.acessar"
                                                    class="h-4 w-4 rounded border-gray-300 text-gray-900 focus:ring-gray-900"
                                                    {{ $user->hasDirectPermission('dashboard.acessar') ? 'checked' : '' }}
                                                >
                                                <span class="leading-none">Dashboard</span>
                                            </label>
                                        </div>

                                        <button
                                            type="submit"
                                            class="shrink-0 inline-flex items-center justify-center rounded-md border border-gray-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 hover:border-gray-300 transition"
                                            title="Salvar permissões"
                                        >
                                            Salvar
                                        </button>
                                    </form>
                                </td>

                                {{-- Ações --}}
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-3">
                                        <a
                                            href="{{ route('admin.usuarios.edit', $user) }}"
                                            class="text-sm font-medium text-blue-700 hover:text-blue-900"
                                        >
                                            Editar
                                        </a>

                                        <form action="{{ route('admin.usuarios.destroy', $user->id) }}" method="POST">
                                            @csrf
                                            @method('DELETE')

                                            <button
                                                type="submit"
                                                class="text-sm font-medium text-red-600 hover:text-red-800"
                                                onclick="return confirm('Tem certeza que deseja excluir?')"
                                            >
                                                Excluir
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-10 text-center text-sm text-gray-500">
                                    Nenhum usuário cadastrado.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</x-app-layout>
