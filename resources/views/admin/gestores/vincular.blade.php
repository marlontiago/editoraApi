<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold">Vincular Distribuidores a Gestores</h2>
    </x-slot>

    <div class="p-6">
        @if(session('success'))
            <div class="bg-green-200 p-4 rounded mb-4 text-green-800">
                {{ session('success') }}
            </div>
        @endif

        <form action="{{ route('admin.gestores.vincular.salvar') }}" method="POST">
            @csrf

            <div class="mb-4">
                <label class="block font-semibold">Selecione o Gestor:</label>
                <select name="gestor_id" class="w-full border rounded p-2">
                    @foreach($gestores as $gestor)
                        <option value="{{ $gestor->id }}">
                            {{ $gestor->razao_social }} ({{ $gestor->user->email }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mb-4">
                <label class="block font-semibold">Distribuidores:</label>
                @foreach($distribuidores as $distribuidor)
                    <div>
                        <label>
                            <input type="checkbox" name="distribuidores[]" value="{{ $distribuidor->id }}">
                            {{ $distribuidor->user->name }} ({{ $distribuidor->user->email }})
                        </label>
                    </div>
                @endforeach
            </div>

            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Vincular</button>
        </form>
    </div>
</x-app-layout>
