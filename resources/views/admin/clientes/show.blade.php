<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold text-gray-800 leading-tight">
                    Cliente: {{ $cliente->razao_social }}
                </h2>
                <p class="text-sm text-gray-500 mt-1">Dados cadastrais, contatos e endereços.</p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('admin.clientes.index') }}"
                   class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 h-10 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Voltar
                </a>

                <a href="{{ route('admin.clientes.edit', $cliente) }}"
                   class="inline-flex items-center gap-2 rounded-lg bg-gray-900 px-4 h-10 text-sm font-semibold text-white hover:bg-gray-800 transition">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 20h9"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4 12.5-12.5z"/>
                    </svg>
                    Editar
                </a>

                <form method="POST" action="{{ route('admin.clientes.destroy', $cliente) }}"
                      onsubmit="return confirm('Tem certeza que deseja remover este cliente?');"
                      class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-lg border border-red-200 bg-white px-4 h-10 text-sm font-semibold text-red-700 hover:bg-red-50 transition">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6h18"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 6V4h8v2"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 6l-1 14H6L5 6"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 11v6M14 11v6"/>
                        </svg>
                        Remover
                    </button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="max-w-6xl mx-auto p-6 space-y-4">

        {{-- Dados principais --}}
        <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-100 px-6 py-5">
            <div class="grid grid-cols-12 gap-6">

                <div class="col-span-12 md:col-span-6 space-y-2">
                    <div>
                        <div class="text-xs uppercase tracking-wide text-gray-500">Razão Social / Nome</div>
                        <div class="text-sm font-semibold text-gray-900">{{ $cliente->razao_social }}</div>
                    </div>

                    <div>
                        <div class="text-xs uppercase tracking-wide text-gray-500">E-mail (principal)</div>
                        <div class="text-sm">
                            @if(!empty($cliente->email))
                                <a href="mailto:{{ $cliente->email }}" class="text-blue-700 hover:underline">{{ $cliente->email }}</a>
                            @else
                                <span class="text-gray-500">—</span>
                            @endif
                        </div>
                    </div>

                    @php
                        $emailsExtras = $cliente->emails_limpos ?? [];
                    @endphp
                    <div>
                        <div class="text-xs uppercase tracking-wide text-gray-500">E-mails adicionais</div>
                        <div class="text-sm">
                            @if(!empty($emailsExtras))
                                {!! collect($emailsExtras)->map(fn($em) => '<a class="text-blue-700 hover:underline" href="mailto:'.$em.'">'.$em.'</a>')->implode(', ') !!}
                            @else
                                <span class="text-gray-500">—</span>
                            @endif
                        </div>
                    </div>

                    <div class="grid grid-cols-12 gap-3 pt-2">
                        <div class="col-span-12 sm:col-span-4">
                            <div class="text-xs uppercase tracking-wide text-gray-500">CNPJ</div>
                            <div class="text-sm text-gray-900">{{ $cliente->cnpj_formatado ?: '—' }}</div>
                        </div>
                        <div class="col-span-12 sm:col-span-4">
                            <div class="text-xs uppercase tracking-wide text-gray-500">CPF</div>
                            <div class="text-sm text-gray-900">{{ $cliente->cpf_formatado ?: '—' }}</div>
                        </div>
                        <div class="col-span-12 sm:col-span-4">
                            <div class="text-xs uppercase tracking-wide text-gray-500">Inscrição Estadual</div>
                            <div class="text-sm text-gray-900">{{ $cliente->inscr_estadual ?: '—' }}</div>
                        </div>
                    </div>

                    @php
                        $tels = $cliente->telefones_formatados ?? [];
                    @endphp
                    <div class="pt-2">
                        <div class="text-xs uppercase tracking-wide text-gray-500">Telefones</div>
                        <div class="text-sm text-gray-900">
                            @if(!empty($tels))
                                {{ implode(' | ', $tels) }}
                            @else
                                {{ $cliente->telefone_formatado ?: '—' }}
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-span-12 md:col-span-6">
                    <div class="bg-gray-50 rounded-xl border border-gray-200 p-4">
                        <div class="flex items-center justify-between">
                            <h4 class="text-sm font-semibold text-gray-800">Endereço principal</h4>
                        </div>

                        <div class="mt-3 grid grid-cols-12 gap-3 text-sm">
                            <div class="col-span-12">
                                <div class="text-xs uppercase tracking-wide text-gray-500">Endereço</div>
                                <div class="text-gray-900">
                                    {{ $cliente->endereco ?: '—' }}, {{ $cliente->numero ?: '—' }}
                                </div>
                            </div>
                            <div class="col-span-12 sm:col-span-6">
                                <div class="text-xs uppercase tracking-wide text-gray-500">Bairro</div>
                                <div class="text-gray-900">{{ $cliente->bairro ?: '—' }}</div>
                            </div>
                            <div class="col-span-12 sm:col-span-6">
                                <div class="text-xs uppercase tracking-wide text-gray-500">Cidade</div>
                                <div class="text-gray-900">{{ $cliente->cidade ?: '—' }}</div>
                            </div>
                            <div class="col-span-12 sm:col-span-6">
                                <div class="text-xs uppercase tracking-wide text-gray-500">UF</div>
                                <div class="text-gray-900">{{ $cliente->uf ?: '—' }}</div>
                            </div>
                            <div class="col-span-12 sm:col-span-6">
                                <div class="text-xs uppercase tracking-wide text-gray-500">CEP</div>
                                <div class="text-gray-900">{{ $cliente->cep ?: '—' }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 bg-gray-50 rounded-xl border border-gray-200 p-4">
                        <div class="flex items-center justify-between">
                            <h4 class="text-sm font-semibold text-gray-800">Endereço secundário</h4>
                        </div>

                        <div class="mt-3 grid grid-cols-12 gap-3 text-sm">
                            <div class="col-span-12">
                                <div class="text-xs uppercase tracking-wide text-gray-500">Endereço</div>
                                <div class="text-gray-900">
                                    {{ $cliente->endereco2 ?: '—' }}, {{ $cliente->numero2 ?: '—' }}
                                </div>
                            </div>
                            <div class="col-span-12 sm:col-span-6">
                                <div class="text-xs uppercase tracking-wide text-gray-500">Bairro</div>
                                <div class="text-gray-900">{{ $cliente->bairro2 ?: '—' }}</div>
                            </div>
                            <div class="col-span-12 sm:col-span-6">
                                <div class="text-xs uppercase tracking-wide text-gray-500">Cidade</div>
                                <div class="text-gray-900">{{ $cliente->cidade2 ?: '—' }}</div>
                            </div>
                            <div class="col-span-12 sm:col-span-6">
                                <div class="text-xs uppercase tracking-wide text-gray-500">UF</div>
                                <div class="text-gray-900">{{ $cliente->uf2 ?: '—' }}</div>
                            </div>
                            <div class="col-span-12 sm:col-span-6">
                                <div class="text-xs uppercase tracking-wide text-gray-500">CEP</div>
                                <div class="text-gray-900">{{ $cliente->cep2 ?: '—' }}</div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
