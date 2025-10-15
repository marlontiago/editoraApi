<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">
                Cliente: {{ $cliente->razao_social }}
            </h2>

            <div class="flex gap-2">
                <form method="POST" action="{{ route('admin.clientes.destroy', $cliente) }}"
                      onsubmit="return confirm('Tem certeza que deseja remover este cliente?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="inline-flex h-9 items-center rounded-md bg-red-600 px-3 text-sm text-white hover:bg-red-700">
                        Remover
                    </button>
                </form>

                <a href="{{ route('admin.clientes.index') }}"
                   class="inline-flex h-9 items-center rounded-md border px-3 text-sm hover:bg-gray-50">Voltar</a>

                <a href="{{ route('admin.clientes.edit', $cliente) }}"
                   class="inline-flex h-9 items-center rounded-md bg-blue-600 px-3 text-sm text-white hover:bg-blue-700">
                    Editar
                </a>
            </div>
        </div>
    </x-slot>

    <div class="max-w-6xl mx-auto p-6 space-y-6">
        {{-- Dados principais --}}
        <div class="bg-white rounded-lg shadow p-6 grid grid-cols-12 gap-4">
            <div class="col-span-12 md:col-span-6">
                <p><span class="font-medium">Razão Social / Nome:</span> {{ $cliente->razao_social }}</p>

                <p class="mt-2">
                    <span class="font-medium">E-mail (principal):</span>
                    @if(!empty($cliente->email))
                        <a href="mailto:{{ $cliente->email }}" class="text-blue-600 hover:underline">{{ $cliente->email }}</a>
                    @else
                        —
                    @endif
                </p>

                @php
                    $emailsExtras = $cliente->emails_limpos ?? [];
                @endphp
                <p>
                    <span class="font-medium">E-mails adicionais:</span>
                    @if(!empty($emailsExtras))
                        {!! collect($emailsExtras)->map(fn($em) => '<a class="text-blue-600 hover:underline" href="mailto:'.$em.'">'.$em.'</a>')->implode(', ') !!}
                    @else
                        —
                    @endif
                </p>

                <p class="mt-2"><span class="font-medium">CNPJ:</span> {{ $cliente->cnpj_formatado ?: '—' }}</p>
                <p><span class="font-medium">CPF:</span> {{ $cliente->cpf_formatado ?: '—' }}</p>
                <p><span class="font-medium">Inscrição Estadual:</span> {{ $cliente->inscr_estadual ?: '—' }}</p>

                @php
                    $tels = $cliente->telefones_formatados ?? [];
                @endphp
                <p class="mt-2">
                    <span class="font-medium">Telefones:</span>
                    @if(!empty($tels))
                        {{ implode(' | ', $tels) }}
                    @else
                        {{ $cliente->telefone_formatado ?: '—' }}
                    @endif
                </p>
            </div>

            <div class="col-span-12 md:col-span-6">
                {{-- Endereço principal --}}
                <h4 class="font-medium text-gray-800 mb-1">Endereço principal</h4>
                <p><span class="font-medium">Endereço:</span> {{ $cliente->endereco ?: '—' }}, {{ $cliente->numero ?: '—' }}</p>
                <p><span class="font-medium">Bairro:</span> {{ $cliente->bairro ?: '—' }}</p>
                <p><span class="font-medium">Cidade:</span> {{ $cliente->cidade ?: '—' }}</p>
                <p><span class="font-medium">UF:</span> {{ $cliente->uf ?: '—' }}</p>
                <p><span class="font-medium">CEP:</span> {{ $cliente->cep ?: '—' }}</p>

                {{-- Endereço secundário --}}
                <div class="mt-4">
                    <h4 class="font-medium text-gray-800 mb-1">Endereço secundário</h4>
                    <p><span class="font-medium">Endereço:</span> {{ $cliente->endereco2 ?: '—' }}, {{ $cliente->numero2 ?: '—' }}</p>
                    <p><span class="font-medium">Bairro:</span> {{ $cliente->bairro2 ?: '—' }}</p>
                    <p><span class="font-medium">Cidade:</span> {{ $cliente->cidade2 ?: '—' }}</p>
                    <p><span class="font-medium">UF:</span> {{ $cliente->uf2 ?: '—' }}</p>
                    <p><span class="font-medium">CEP:</span> {{ $cliente->cep2 ?: '—' }}</p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
