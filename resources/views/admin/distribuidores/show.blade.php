<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">
                Distribuidor: {{ $distribuidor->razao_social }}
            </h2>

            <div class="flex items-center gap-2">
                <a href="{{ route('admin.distribuidores.index') }}"
                   class="inline-flex h-9 items-center rounded-md border px-3 text-sm hover:bg-gray-50">
                    Voltar
                </a>
                <a href="{{ route('admin.distribuidores.edit', $distribuidor) }}"
                   class="inline-flex h-9 items-center rounded-md bg-blue-600 px-3 text-sm font-medium text-white hover:bg-blue-700">
                    Editar
                </a>

                <form action="{{ route('admin.distribuidores.destroy', $distribuidor) }}" method="POST"
                      onsubmit="return confirm('Tem certeza que deseja excluir este distribuidor? Esta ação não pode ser desfeita.');">
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
        @if (session('success'))
            <div class="rounded-md border border-green-300 bg-green-50 p-4 text-green-800">
                {{ session('success') }}
            </div>
        @endif

        {{-- Card: Informações principais --}}
        <div class="bg-white shadow rounded-lg p-6 grid grid-cols-12 gap-4">
            <div class="col-span-12 md:col-span-6">
                <h3 class="text-sm font-semibold text-gray-700 mb-2">Dados do Distribuidor</h3>
                <dl class="text-sm text-gray-700 space-y-1">
                    <div class="flex">
                        <dt class="w-44 font-medium">Razão social</dt>
                        <dd class="flex-1">{{ $distribuidor->razao_social }}</dd>
                    </div>
                    <div class="flex">
                        <dt class="w-44 font-medium">CNPJ</dt>
                        <dd class="flex-1">{{ $distribuidor->cnpj }}</dd>
                    </div>
                    <div class="flex">
                        <dt class="w-44 font-medium">Representante</dt>
                        <dd class="flex-1">{{ $distribuidor->representante_legal }}</dd>
                    </div>
                    <div class="flex">
                        <dt class="w-44 font-medium">CPF</dt>
                        <dd class="flex-1">{{ $distribuidor->cpf }}</dd>
                    </div>
                    @if($distribuidor->rg)
                        <div class="flex">
                            <dt class="w-44 font-medium">RG</dt>
                            <dd class="flex-1">{{ $distribuidor->rg }}</dd>
                        </div>
                    @endif
                    @if($distribuidor->telefone)
                        <div class="flex">
                            <dt class="w-44 font-medium">Telefone</dt>
                            <dd class="flex-1">{{ $distribuidor->telefone }}</dd>
                        </div>
                    @endif
                    <div class="flex">
                        <dt class="w-44 font-medium">E-mail</dt>
                        <dd class="flex-1">
                            @php
                                $email = optional($distribuidor->user)->email;
                                $emailExibicao = ($email && !str_contains($email, '@placeholder.local')) ? $email : 'Não informado';
                            @endphp
                            {{ $emailExibicao }}
                        </dd>
                    </div>

                    @if($distribuidor->gestor)
                        <div class="flex">
                            <dt class="w-44 font-medium">Gestor</dt>
                            <dd class="flex-1">
                                {{ $distribuidor->gestor->razao_social }}
                                @if($distribuidor->gestor->estado_uf) — {{ $distribuidor->gestor->estado_uf }} @endif
                                <a href="{{ route('admin.gestores.show', $distribuidor->gestor) }}"
                                   class="ml-2 text-blue-600 hover:underline">ver gestor</a>
                            </dd>
                        </div>
                    @endif

                    {{-- Cidades de atuação (embaixo do Gestor, no mesmo card) --}}
                    <div class="flex items-start">
                        <dt class="w-44 font-medium">Cidades de atuação</dt>
                        <dd class="flex-1">
                            @php
                                $cities = ($distribuidor->cities ?? collect())
                                    ->sortBy(fn($c) => sprintf('%s-%s', strtoupper($c->state ?? ''), $c->name ?? ''))
                                    ->values();
                            @endphp

                            @if($cities->isEmpty())
                                <span class="text-gray-500">Nenhuma cidade vinculada.</span>
                            @else
                                <div class="flex flex-wrap gap-1">
                                    @foreach($cities as $city)
                                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-800">
                                            {{ $city->name }} — {{ strtoupper($city->state ?? '-') }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </dd>
                    </div>
                </dl>
            </div>

            <div class="col-span-12 md:col-span-6">
                <h3 class="text-sm font-semibold text-gray-700 mb-2">Condições comerciais</h3>
                <dl class="text-sm text-gray-700 space-y-1">
                    <div class="flex">
                        <dt class="w-44 font-medium">Percentual vendas</dt>
                        <dd class="flex-1">{{ number_format((float)($distribuidor->percentual_vendas ?? 0), 2, ',', '.') }} %</dd>
                    </div>

                    <div class="flex items-center">
                        <dt class="w-44 font-medium">Validade do contrato</dt>
                        <dd class="flex-1">
                            @if($distribuidor->vencimento_contrato)
                                @php
                                    $vence = \Carbon\Carbon::parse($distribuidor->vencimento_contrato);
                                    $restamDias = now()->diffInDays($vence, false);
                                @endphp
                                <span>Contrato válido até <strong>{{ $vence->format('d/m/Y') }}</strong></span>
                                @if($restamDias < 0)
                                    <span class="ml-2 inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700">Vencido há {{ abs($restamDias) }} dia(s)</span>
                                @elseif($restamDias === 0)
                                    <span class="ml-2 inline-flex items-center rounded-full bg-yellow-100 px-2 py-0.5 text-xs font-medium text-yellow-700">Vence hoje</span>
                                @else
                                    <span class="ml-2 inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700">Faltam {{ (int)$restamDias }} dia(s)</span>
                                @endif
                            @else
                                <span class="text-gray-500">—</span>
                            @endif
                        </dd>
                    </div>

                    <div class="flex items-center">
                        <dt class="w-44 font-medium">Contrato assinado</dt>
                        <dd class="flex-1">
                            @if($distribuidor->contrato_assinado)
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
                        <div><span class="font-medium">Endereço:</span> {{ $distribuidor->endereco ?? '—' }}</div>
                        <div><span class="font-medium">Número:</span> {{ $distribuidor->numero ?? '—' }}</div>
                        <div><span class="font-medium">Complemento:</span> {{ $distribuidor->complemento ?? '—' }}</div>
                    </div>
                    <div class="col-span-12 md:col-span-6">
                        <div><span class="font-medium">Bairro:</span> {{ $distribuidor->bairro ?? '—' }}</div>
                        <div><span class="font-medium">Cidade/UF:</span> {{ $distribuidor->cidade ? $distribuidor->cidade : '—' }}{{ $distribuidor->uf ? ' - '.$distribuidor->uf : '' }}</div>
                        <div><span class="font-medium">CEP:</span> {{ $distribuidor->cep ?? '—' }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Card: Anexos --}}
        <div class="bg-white shadow rounded-lg p-6">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-gray-700">Anexos</h3>
                <a href="{{ route('admin.distribuidores.edit', $distribuidor) }}"
                   class="inline-flex h-9 items-center rounded-md border px-3 text-sm hover:bg-gray-50">
                    Gerenciar anexos
                </a>
            </div>

            @if(($distribuidor->anexos ?? collect())->isEmpty())
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
                            @foreach($distribuidor->anexos as $anexo)
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
                                        @php $url = $anexo->arquivo ? asset('storage/'.$anexo->arquivo) : null; @endphp
                                        @if($url)
                                            <a href="{{ $url }}" target="_blank" class="text-blue-600 hover:underline">Baixar PDF</a>
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
