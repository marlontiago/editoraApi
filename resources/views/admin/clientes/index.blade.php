<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold text-gray-800 leading-tight">Clientes</h2>
                <p class="text-sm text-gray-500 mt-1">Cadastro, dados fiscais e contatos.</p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('admin.clientes.create') }}"
                   class="inline-flex items-center gap-2 rounded-lg bg-gray-900 px-4 h-10 text-sm font-semibold text-white hover:bg-gray-800 transition">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14M5 12h14"/>
                    </svg>
                    Novo cliente
                </a>
            </div>
        </div>
    </x-slot>

    <div class="max-w-full mx-auto p-6 space-y-4">

        {{-- Flash --}}
        @if ($msg = session('success'))
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-800">
                {{ $msg }}
            </div>
        @endif

        @if($clientes->count() === 0)
            <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-100 px-5 py-10 text-center text-gray-600">
                Nenhum cliente cadastrado ainda.
            </div>
        @else
            {{-- Tabela --}}
            <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-100 overflow-x-auto">
                <table class="min-w-[1100px] w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600 text-xs uppercase tracking-wide">
                        <tr class="border-b border-gray-100 text-left">
                            <th class="px-4 py-3 whitespace-nowrap">Razão Social</th>
                            <th class="px-4 py-3 whitespace-nowrap">Documento</th>
                            <th class="px-4 py-3 whitespace-nowrap">I.E.</th>
                            <th class="px-4 py-3 whitespace-nowrap">E-mail</th>
                            <th class="px-4 py-3 whitespace-nowrap">Telefone</th>
                            <th class="px-4 py-3 whitespace-nowrap">Cidade / UF</th>
                            <th class="px-4 py-3 whitespace-nowrap">CEP</th>
                            <th class="px-4 py-3 whitespace-nowrap">Endereço</th>
                            <th class="px-4 py-3 whitespace-nowrap text-right">Ações</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100">
                        @foreach($clientes as $cliente)
                            @php
                                // Documento (CNPJ preferencial, senão CPF)
                                $docLabel = $cliente->cnpj ? 'CNPJ' : ($cliente->cpf ? 'CPF' : null);
                                $docValue = $cliente->cnpj ?: ($cliente->cpf ?: '—');

                                // Cidade/UF
                                $cidade = $cliente->cidade ?: '—';
                                $uf     = $cliente->uf ? strtoupper($cliente->uf) : '—';

                                // Endereço compacto
                                $linha1 = trim(($cliente->endereco ?: '')
                                    . ($cliente->numero ? ', '.$cliente->numero : '')
                                    . ($cliente->complemento ? ' - '.$cliente->complemento : ''));
                                $linha2 = trim(
                                    ($cliente->bairro ? $cliente->bairro : '')
                                    . (($cliente->bairro && ($cliente->cidade || $cliente->uf || $cliente->cep)) ? ' • ' : '')
                                    . ($cliente->cidade ? $cliente->cidade : '')
                                    . ($cliente->uf ? '/'.strtoupper($cliente->uf) : '')
                                    . ($cliente->cep ? ' • CEP '.$cliente->cep : '')
                                );
                                $enderecoCompleto = $linha1 ?: '—';
                            @endphp

                            <tr class="hover:bg-gray-50/60">
                                {{-- Razão Social --}}
                                <td class="px-4 py-3 font-medium text-gray-900">
                                    <a href="{{ route('admin.clientes.show', $cliente) }}" class="hover:underline">
                                        {{ $cliente->razao_social }}
                                    </a>
                                </td>

                                {{-- Documento --}}
                                <td class="px-4 py-3">
                                    @if($docLabel)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-700">
                                            {{ $docLabel }}
                                        </span>
                                        <div class="mt-1 text-gray-900 whitespace-nowrap">{{ $docValue }}</div>
                                    @else
                                        —
                                    @endif
                                </td>

                                {{-- I.E. --}}
                                <td class="px-4 py-3">
                                    {{ $cliente->inscr_estadual ?: '—' }}
                                </td>

                                {{-- E-mail --}}
                                <td class="px-4 py-3">
                                    @if($cliente->email)
                                        <a href="mailto:{{ $cliente->email }}" class="text-blue-700 hover:underline">
                                            {{ $cliente->email }}
                                        </a>
                                    @else
                                        —
                                    @endif
                                </td>

                                {{-- Telefone (lista nova com fallback no legado) --}}
                                <td class="px-4 py-3">
                                    @php
                                        $tels = $cliente->telefones_formatados; // accessor do Model (com fallback no campo legado)
                                        $tel  = $tels[0] ?? (trim((string)$cliente->telefone_formatado) !== '' ? $cliente->telefone_formatado : null);
                                    @endphp
                                    {{ $tel ?? '—' }}
                                </td>

                                {{-- Cidade / UF --}}
                                <td class="px-4 py-3 whitespace-nowrap">
                                    {{ $cidade }} / {{ $uf }}
                                </td>

                                {{-- CEP --}}
                                <td class="px-4 py-3 whitespace-nowrap">
                                    {{ $cliente->cep ?: '—' }}
                                </td>

                                {{-- Endereço (compacto) --}}
                                <td class="px-4 py-3">
                                    <div class="text-gray-900 truncate max-w-[28ch]" title="{{ $enderecoCompleto }}">
                                        {{ $enderecoCompleto }}
                                    </div>
                                    @if($linha2)
                                        <div class="text-xs text-gray-500 truncate max-w-[36ch]" title="{{ $linha2 }}">
                                            {{ $linha2 }}
                                        </div>
                                    @endif
                                </td>

                                {{-- Ações --}}
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('admin.clientes.show', $cliente) }}"
                                           class="inline-flex items-center justify-center rounded-md border border-gray-200 p-2 text-gray-700 hover:bg-gray-50 transition"
                                           title="Ver">
                                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15a3 3 0 100-6 3 3 0 000 6z"/>
                                            </svg>
                                        </a>

                                        <a href="{{ route('admin.clientes.edit', $cliente) }}"
                                           class="inline-flex items-center justify-center rounded-md border border-blue-200 p-2 text-blue-700 hover:bg-blue-50 transition"
                                           title="Editar">
                                            <x-heroicon-o-pencil-square class="w-5 h-5" />
                                        </a>

                                        <form action="{{ route('admin.clientes.destroy', $cliente) }}" method="POST" class="inline"
                                              onsubmit="return confirm('Tem certeza que deseja excluir?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="inline-flex items-center justify-center rounded-md border border-red-200 p-2 text-red-700 hover:bg-red-50 transition"
                                                    title="Excluir">
                                                <x-heroicon-o-trash class="w-5 h-5" />
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Paginação --}}
            <div class="flex justify-end">
                {{ $clientes->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
