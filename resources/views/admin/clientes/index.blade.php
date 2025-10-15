<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Clientes</h2>
    </x-slot>

    @if ($msg = session('success'))
        <div class="max-w-7xl mx-auto px-6 pt-4">
            <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded">
                {{ $msg }}
            </div>
        </div>
    @endif

    <div class="max-w-full mx-auto p-6">
        <div class="flex items-center justify-between mb-4">
            <a href="{{ route('admin.clientes.create') }}"
               class="inline-flex items-center gap-2 bg-green-600 text-white px-4 py-2 rounded shadow hover:bg-green-700">
                <span class="text-lg leading-none">＋</span> Novo Cliente
            </a>
        </div>

        @if($clientes->count() === 0)
            <div class="bg-white border rounded shadow p-8 text-center text-gray-600">
                Nenhum cliente cadastrado ainda.
            </div>
        @else
            <div class="overflow-x-auto bg-white border rounded shadow">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-gray-700 sticky top-0">
                        <tr class="text-left">
                            <th class="px-4 py-3">Razão Social</th>
                            <th class="px-4 py-3">Documento</th>
                            <th class="px-4 py-3">I.E.</th>
                            <th class="px-4 py-3">E-mail</th>
                            <th class="px-4 py-3">Telefone</th>
                            <th class="px-4 py-3">Cidade / UF</th>
                            <th class="px-4 py-3">CEP</th>
                            <th class="px-4 py-3">Endereço</th>
                            <th class="px-4 py-3 text-right">Ações</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y">
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

                            <tr class="hover:bg-gray-50">
                                {{-- Razão Social --}}
                                <td class="px-4 py-3 font-medium text-gray-900">
                                    <a href="{{ route('admin.clientes.show', $cliente) }}" class="hover:underline">
                                        {{ $cliente->razao_social }}
                                    </a>
                                </td>

                                {{-- Documento --}}
                                <td class="px-4 py-3">
                                    @if($docLabel)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full border text-xs bg-gray-50 text-gray-700 border-gray-200">
                                            {{ $docLabel }}
                                        </span>
                                        <div class="mt-1 text-gray-900">{{ $docValue }}</div>
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
                                <td class="px-4 py-3">
                                    {{ $cidade }} / {{ $uf }}
                                </td>

                                {{-- CEP --}}
                                <td class="px-4 py-3">
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
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <a href="{{ route('admin.clientes.show', $cliente) }}"
                                       class="inline-flex items-center px-3 py-1.5 rounded border border-gray-300 text-gray-700 hover:bg-gray-50 mr-2">
                                        Ver
                                    </a>

                                    <a href="{{ route('admin.clientes.edit', $cliente) }}"
                                       class="inline-flex items-center px-3 py-1.5 rounded border border-blue-600 text-blue-600 hover:bg-blue-50 mr-2">
                                        Editar
                                    </a>

                                    <form action="{{ route('admin.clientes.destroy', $cliente) }}" method="POST" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                                onclick="return confirm('Tem certeza que deseja excluir?')"
                                                class="inline-flex items-center px-3 py-1.5 rounded border border-red-600 text-red-600 hover:bg-red-50">
                                            Excluir
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $clientes->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
