<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Criar Novo Usuário</h2>
    </x-slot>

    <div class="max-w-6xl mx-auto p-6">
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

        <div class="bg-white shadow rounded-lg p-6">
            <form action="{{ route('admin.usuarios.store') }}" method="POST" class="grid grid-cols-12 gap-4" id="user-form">
                @csrf

                {{-- Nome --}}
                <div class="col-span-12 md:col-span-6">
                    <label for="name" class="block text-sm font-medium text-gray-700">Nome <span class="text-red-600">*</span></label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Email --}}
                <div class="col-span-12 md:col-span-6">
                    <label for="email" class="block text-sm font-medium text-gray-700">Email <span class="text-red-600">*</span></label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                    @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Telefone --}}
                <div class="col-span-12 md:col-span-6">
                    <label for="telefone" class="block text-sm font-medium text-gray-700">Telefone</label>
                    <input type="text" id="telefone" name="telefone" value="{{ old('telefone') }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('telefone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Senha --}}
                <div class="col-span-12 md:col-span-6">
                    <label for="password" class="block text-sm font-medium text-gray-700">Senha <span class="text-red-600">*</span></label>
                    <input type="password" id="password" name="password"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                    @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Confirmar Senha --}}
                <div class="col-span-12 md:col-span-6">
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirmar Senha <span class="text-red-600">*</span></label>
                    <input type="password" id="password_confirmation" name="password_confirmation"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                    @error('password_confirmation') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Papel (Role) --}}
                <div class="col-span-12 md:col-span-6">
                    <label for="role" class="block text-sm font-medium text-gray-700">Papel (Role) <span class="text-red-600">*</span></label>
                    <select id="role" name="role" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            onchange="toggleGestorField()">
                        <option value="">Selecione...</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->name }}" @selected(old('role') === $role->name)>{{ ucfirst($role->name) }}</option>
                        @endforeach
                    </select>
                    @error('role') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Vincular a Gestor (mostra só quando role=distribuidor) --}}
                @php $showGestor = old('role') === 'distribuidor'; @endphp
                <div id="gestor-field" class="col-span-12 md:col-span-6 {{ $showGestor ? '' : 'hidden' }}">
                    <label for="gestor_id" class="block text-sm font-medium text-gray-700">Vincular a Gestor</label>
                    <select id="gestor_id" name="gestor_id"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Selecione o gestor...</option>
                        @foreach($gestores as $gestor)
                            <option value="{{ $gestor->id }}" @selected(old('gestor_id') == $gestor->id)>{{ $gestor->nome_completo }}</option>
                        @endforeach
                    </select>
                    @error('gestor_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Ações --}}
                <div class="col-span-12 flex items-center justify-end gap-3 pt-2">
                    <a href="{{ route('admin.usuarios.index') }}"
                       class="inline-flex h-10 items-center rounded-md border px-4 text-sm hover:bg-gray-50">
                        Cancelar
                    </a>
                    <button type="submit"
                            class="inline-flex h-10 items-center rounded-md bg-blue-600 px-5 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        Criar
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Script simples para exibir/ocultar o campo de Gestor --}}
    <script>
        function toggleGestorField() {
            const role = document.getElementById('role').value;
            const gestorField = document.getElementById('gestor-field');
            if (role === 'distribuidor') {
                gestorField.classList.remove('hidden');
            } else {
                gestorField.classList.add('hidden');
            }
        }
        // garante estado correto ao carregar (caso volte com erro de validação)
        document.addEventListener('DOMContentLoaded', toggleGestorField);
    </script>
</x-app-layout>
