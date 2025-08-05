<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Editar Usuário</h2>
    </x-slot>

    <div class="p-6 max-w-3xl mx-auto space-y-10">
        {{-- Formulário de edição --}}
        <div class="bg-white p-6 rounded shadow">
            <form action="{{ route('admin.usuarios.update', $usuario->id) }}" method="POST" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label for="name" class="block font-medium">Nome</label>
                    <input type="text" name="name" class="w-full border rounded p-2" value="{{ $usuario->name }}" required>
                </div>

                <div>
                    <label for="email" class="block font-medium">Email</label>
                    <input type="email" name="email" class="w-full border rounded p-2" value="{{ $usuario->email }}" required>
                </div>

                <div>
                    <label for="telefone" class="block font-medium">Telefone</label>
                    <input type="text" name="telefone" class="w-full border rounded p-2" value="{{ $usuario->telefone ?? '' }}">
                </div>

                <div>
                    <label for="role" class="block font-medium">Papel (Role)</label>
                    <select name="role" id="role" class="w-full border rounded p-2" required onchange="toggleGestorField()">
                        <option value="">Selecione...</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->name }}" {{ $usuario->roles->contains('name', $role->name) ? 'selected' : '' }}>
                                {{ ucfirst($role->name) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div id="gestor-field" style="display: none;">
                    <label for="gestor_id" class="block font-medium">Vincular a Gestor</label>
                    <select name="gestor_id" class="w-full border rounded p-2">
                        <option value="">Selecione o gestor...</option>
                        @foreach($gestores as $gestor)
                            <option value="{{ $gestor->id }}" {{ $usuario->distribuidor->gestor_id ?? null == $gestor->id ? 'selected' : '' }}>
                                {{ $gestor->nome_completo }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="text-right">
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">Atualizar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleGestorField() {
            const role = document.getElementById('role').value;
            const gestorField = document.getElementById('gestor-field');
            gestorField.style.display = (role === 'distribuidor') ? 'block' : 'none';
        }

        // Executa ao carregar para exibir o campo se for distribuidor
        document.addEventListener('DOMContentLoaded', function () {
            toggleGestorField();
        });
    </script>
</x-app-layout>
