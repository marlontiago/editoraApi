<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">
                Gestor: {{ $gestor->razao_social }}
            </h2>

            <div class="flex items-center gap-2">
                <a href="{{ route('admin.gestores.index') }}"
                   class="inline-flex h-9 items-center rounded-md border px-3 text-sm hover:bg-gray-50">
                    Voltar
                </a>
                <a href="{{ route('admin.gestores.edit', $gestor) }}"
                   class="inline-flex h-9 items-center rounded-md bg-blue-600 px-3 text-sm font-medium text-white hover:bg-blue-700">
                    Editar
                </a>

                <form action="{{ route('admin.gestores.destroy', $gestor) }}" method="POST"
                      onsubmit="return confirm('Tem certeza que deseja excluir este gestor? Esta ação não pode ser desfeita.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="inline-flex items-center rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500">
                        Excluir
                    </button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="max-w-6xl mx-auto p-6 space-y-6">
        {{-- alerts --}}
        @if (session('success'))
            <div class="rounded-md border border-green-300 bg-green-50 p-4 text-green-800">
                {{ session('success') }}
            </div>
        @endif

        {{-- Card: Informações principais --}}
        <div class="bg-white shadow rounded-lg p-6 grid grid-cols-12 gap-4">
            <div class="col-span-12 md:col-span-6">
                <h3 class="text-sm font-semibold text-gray-700 mb-2">Dados do Gestor</h3>
                <dl class="text-sm text-gray-700 space-y-1">
                    <div class="flex"><dt class="w-40 font-medium">Razão social</dt><dd class="flex-1">{{ $gestor->razao_social }}</dd></div>
                    <div class="flex"><dt class="w-40 font-medium">CNPJ</dt><dd class="flex-1">{{ $gestor->cnpj }}</dd></div>
                    <div class="flex"><dt class="w-40 font-medium">Representante</dt><dd class="flex-1">{{ $gestor->representante_legal }}</dd></div>
                    <div class="flex"><dt class="w-40 font-medium">CPF</dt><dd class="flex-1">{{ $gestor->cpf }}</dd></div>
                    @if($gestor->rg)
                    <div class="flex"><dt class="w-40 font-medium">RG</dt><dd class="flex-1">{{ $gestor->rg }}</dd></div>
                    @endif
                    @if($gestor->telefone)
                    <div class="flex"><dt class="w-40 font-medium">Telefone</dt><dd class="flex-1">{{ $gestor->telefone }}</dd></div>
                    @endif
                    <div class="flex"><dt class="w-40 font-medium">E-mail</dt><dd class="flex-1">
                        {{-- accessor definido no model Gestor --}}
                        {{ $gestor->email_exibicao }}
                    </dd></div>
                    @if($gestor->estado_uf)
                    <div class="flex"><dt class="w-40 font-medium">UF de atuação</dt><dd class="flex-1">{{ $gestor->estado_uf }}</dd></div>
                    @endif
                </dl>
            </div>

            <div class="col-span-12 md:col-span-6">
                <h3 class="text-sm font-semibold text-gray-700 mb-2">Condições comerciais</h3>
                <dl class="text-sm text-gray-700 space-y-1">
                    <div class="flex"><dt class="w-40 font-medium">Percentual vendas</dt>
                        <dd class="flex-1">{{ number_format((float)($gestor->percentual_vendas ?? 0), 2, ',', '.') }} %</dd>
                    </div>

                    {{-- Contrato válido até ... (se houver vencimento) --}}
                    <div class="flex items-center">
                        <dt class="w-40 font-medium">Validade do contrato</dt>
                        <dd class="flex-1">
                            @if($gestor->vencimento_contrato)
                                @php
                                    $vence = \Carbon\Carbon::parse($gestor->vencimento_contrato);
                                    $restamDias = now()->diffInDays($vence, false);
                                @endphp
                                <span>
                                    Contrato válido até
                                    <strong>{{ $vence->format('d/m/Y') }}</strong>
                                </span>

                                @if($restamDias < 0)
                                    <span class="ml-2 inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700">
                                        Vencido há {{ abs($restamDias) }} dia(s)
                                    </span>
                                @elseif($restamDias === 0)
                                    <span class="ml-2 inline-flex items-center rounded-full bg-yellow-100 px-2 py-0.5 text-xs font-medium text-yellow-700">
                                        Vence hoje
                                    </span>
                                @else
                                    <span class="ml-2 inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700">
                                        Faltam {{ (int)$restamDias }} dia(s)
                                    </span>
                                @endif
                            @else
                                <span class="text-gray-500">—</span>
                            @endif
                        </dd>
                    </div>

                    {{-- Status contrato assinado (derivado dos anexos) --}}
                    <div class="flex items-center">
                        <dt class="w-40 font-medium">Contrato assinado</dt>
                        <dd class="flex-1">
                            @if($gestor->contrato_assinado)
                                <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700">Sim</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700">Não</span>
                            @endif
                        </dd>
                    </div>
                </dl>
            </div>

            {{-- Endereço --}}
            <div class="col-span-12">
                <h3 class="text-sm font-semibold text-gray-700 mb-2">Endereço</h3>
                <div class="rounded-md border p-4 text-sm text-gray-700 grid grid-cols-12 gap-3">
                    <div class="col-span-12 md:col-span-6">
                        <div><span class="font-medium">Endereço:</span> {{ $gestor->endereco ?? '—' }}</div>
                        <div><span class="font-medium">Número:</span> {{ $gestor->numero ?? '—' }}</div>
                        <div><span class="font-medium">Complemento:</span> {{ $gestor->complemento ?? '—' }}</div>
                    </div>
                    <div class="col-span-12 md:col-span-6">
                        <div><span class="font-medium">Bairro:</span> {{ $gestor->bairro ?? '—' }}</div>
                        <div><span class="font-medium">Cidade/UF:</span> {{ $gestor->cidade ? $gestor->cidade : '—' }}{{ $gestor->uf ? ' - '.$gestor->uf : '' }}</div>
                        <div><span class="font-medium">CEP:</span> {{ $gestor->cep ?? '—' }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Card: Anexos --}}
        <div class="bg-white shadow rounded-lg p-6">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-gray-700">Anexos</h3>
                <a href="{{ route('admin.gestores.edit', $gestor) }}"
                   class="inline-flex h-9 items-center rounded-md border px-3 text-sm hover:bg-gray-50">
                    Gerenciar anexos
                </a>
            </div>

            @if($gestor->anexos->isEmpty())
                <p class="text-sm text-gray-600">Nenhum anexo enviado.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-600 border-b">
                                <th class="py-2 pr-4">Tipo</th>
                                <th class="py-2 pr-4">Descrição</th>
                                <th class="py-2 pr-4">Assinado</th>
                                <th class="py-2 pr-4">Arquivo</th>
                            </tr>
                        </thead>
                        <tbody class="align-top">
                            @foreach($gestor->anexos as $anexo)
                                <tr class="border-b last:border-0">
                                    <td class="py-2 pr-4 capitalize">{{ $anexo->tipo }}</td>
                                    <td class="py-2 pr-4">{{ $anexo->descricao ?? '—' }}</td>
                                    <td class="py-2 pr-4">
                                        @if($anexo->assinado)
                                            <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700">Assinado</span>
                                        @else
                                            <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700">Não assinado</span>
                                        @endif
                                    </td>
                                    <td class="py-2 pr-4">
                                        @php
                                            // Se estiver usando disk 'public'
                                            $url = $anexo->arquivo ? asset('storage/'.$anexo->arquivo) : null;
                                        @endphp
                                        @if($url)
                                            <a href="{{ $url }}" target="_blank"
                                               class="text-blue-600 hover:underline">Baixar PDF</a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
