<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold">Criar Novo Usuário</h2>
    </x-slot>

    <div class="p-6 max-w-xl mt-6 mx-auto bg-white rounded shadow space-y-6">
        <form action="{{ route('admin.usuarios.store') }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label for="name">Nome</label>
                <input type="text" name="name" class="w-full border p-2" required>
            </div>

            <div>
                <label for="email">Email</label>
                <input type="email" name="email" class="w-full border p-2" required>
            </div>

            <div>
                <label for="telefone">Telefone</label>
                <input type="text" name="telefone" class="w-full border p-2">
            </div>

            <div>
                <label for="password">Senha</label>
                <input type="password" name="password" class="w-full border p-2" required>
            </div>

            <div>
                <label for="password_confirmation">Confirmar Senha</label>
                <input type="password" name="password_confirmation" class="w-full border p-2" required>
            </div>

            <div>
                <label for="role">Papel (Role)</label>
                <select name="role" id="role" class="w-full border p-2" required onchange="toggleGestorField()">
                    <option value="">Selecione...</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->name }}">{{ ucfirst($role->name) }}</option>
                    @endforeach
                </select>
            </div>

            <div id="gestor-field" style="display: none;">
                <label for="gestor_id">Vincular a Gestor</label>
                <select name="gestor_id" class="w-full border p-2">
                    <option value="">Selecione o gestor...</option>
                    @foreach($gestores as $gestor)
                        <option value="{{ $gestor->id }}">{{ $gestor->nome_completo }}</option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Criar</button>
        </form>
    </div>

    <script>
        function toggleGestorField() {
            const role = document.getElementById('role').value;
            const gestorField = document.getElementById('gestor-field');
            gestorField.style.display = (role === 'distribuidor') ? 'block' : 'none';
        }
    </script>
</x-app-layout>
