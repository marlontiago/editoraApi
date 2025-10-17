{{-- resources/views/admin/distribuidores/show.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">
                Distribuidor: {{ $distribuidor->razao_social }}
            </h2>

            <div class="flex gap-2">
                <form method="POST" action="{{ route('admin.distribuidores.destroy', $distribuidor) }}"
                      onsubmit="return confirm('Tem certeza que deseja remover este distribuidor?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="inline-flex h-9 items-center rounded-md bg-red-600 px-3 text-sm text-white hover:bg-red-700">
                        Remover
                    </button>
                </form>

                <a href="{{ route('admin.distribuidores.index') }}"
                   class="inline-flex h-9 items-center rounded-md border px-3 text-sm hover:bg-gray-50">
                    Voltar
                </a>

                <a href="{{ route('admin.distribuidores.edit', $distribuidor) }}"
                   class="inline-flex h-9 items-center rounded-md bg-blue-600 px-3 text-sm text-white hover:bg-blue-700">
                    Editar
                </a>
            </div>
        </div>
    </x-slot>

    <div class="max-w-6xl mx-auto p-6 space-y-6">

        {{-- Dados principais (mesmo estilo do Gestor) --}}
        <div class="bg-white rounded-lg shadow p-6 grid grid-cols-12 gap-4">
            <div class="col-span-12 md:col-span-6">
                <p><span class="font-medium">Gestor:</span> {{ $distribuidor->gestor?->razao_social ?: '—' }}</p>
                <p><span class="font-medium">Razão Social:</span> {{ $distribuidor->razao_social }}</p>
                <p><span class="font-medium">CNPJ:</span> {{ $distribuidor->cnpj_formatado ?? $distribuidor->cnpj }}</p>
                <p><span class="font-medium">Representante Legal:</span> {{ $distribuidor->representante_legal ?: '—' }}</p>
                <p><span class="font-medium">CPF (representante):</span> {{ $distribuidor->cpf_formatado ?? $distribuidor->cpf ?: '—' }}</p>
                <p><span class="font-medium">RG:</span> {{ $distribuidor->rg_formatado ?? $distribuidor->rg ?: '—' }}</p>

                {{-- Telefones --}}
                @php
                    $telefones = is_array($distribuidor->telefones) ? $distribuidor->telefones : [];
                @endphp
                <p class="mt-2">
                    <span class="font-medium">Telefones:</span>
                    @if(!empty(array_filter($telefones)))
                        {{ collect($telefones)->filter()->implode(', ') }}
                    @else
                        —
                    @endif
                </p>

                {{-- E-mails --}}
                @php
                    $emails = is_array($distribuidor->emails) ? $distribuidor->emails : [];
                @endphp
                <p>
                    <span class="font-medium">E-mails:</span>
                    @if(!empty(array_filter($emails)))
                        {!! collect($emails)->filter()->map(fn($em) => '<a class="text-blue-600 hover:underline" href="mailto:'.$em.'">'.$em.'</a>')->implode(', ') !!}
                    @else
                        —
                    @endif
                </p>
            </div>

            <div class="col-span-12 md:col-span-6">
                <p class="mt-0">
                    <span class="font-medium">Percentual Vendas (aplicado):</span>
                    {{ number_format((float)($distribuidor->percentual_vendas ?? 0), 2, ',', '.') }}%
                </p>
                <p>
                    <span class="font-medium">Contrato Assinado:</span>
                    {{ $distribuidor->contrato_assinado ? 'Sim' : 'Não' }}
                </p>
                <p>
                    <span class="font-medium">Vencimento Contrato (ativo):</span>
                    {{ $distribuidor->vencimento_contrato ? \Illuminate\Support\Carbon::parse($distribuidor->vencimento_contrato)->format('d/m/Y') : '—' }}
                </p>

                <div class="mt-3 flex flex-wrap gap-2">
                    @if($distribuidor->contrato_assinado)
                        <span class="inline-flex items-center rounded-md bg-green-50 px-2.5 py-1 text-xs font-medium text-green-700 border border-green-200">
                            Contrato assinado
                        </span>
                    @else
                        <span class="inline-flex items-center rounded-md bg-yellow-50 px-2.5 py-1 text-xs font-medium text-yellow-700 border border-yellow-200">
                            Contrato pendente
                        </span>
                    @endif

                    @if($distribuidor->vencimento_contrato)
                        @php
                            $ven = \Illuminate\Support\Carbon::parse($distribuidor->vencimento_contrato);
                            $hoje = now();
                            $classe = $ven->isPast()
                                ? 'bg-red-50 text-red-700 border-red-200'
                                : ($ven->diffInDays($hoje) <= 30
                                    ? 'bg-yellow-50 text-yellow-700 border-yellow-200'
                                    : 'bg-gray-50 text-gray-700 border-gray-200');
                        @endphp
                        <span class="inline-flex items-center rounded-md px-2.5 py-1 text-xs font-medium border {{ $classe }}">
                            Vence em: {{ $ven->format('d/m/Y') }}
                        </span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Endereços (mesmo card do Gestor) --}}
        <div class="bg-white rounded-lg shadow p-6 grid grid-cols-12 gap-4">
            <div class="col-span-12 md:col-span-6">
                <h3 class="text-sm font-semibold mb-2">Endereço principal</h3>
                <p><span class="font-medium">Endereço:</span> {{ $distribuidor->endereco ?: '—' }}, {{ $distribuidor->numero ?: '—' }}</p>
                <p><span class="font-medium">Bairro:</span> {{ $distribuidor->bairro ?: '—' }}</p>
                <p><span class="font-medium">Cidade:</span> {{ $distribuidor->cidade ?: '—' }}</p>
                <p><span class="font-medium">UF:</span> {{ $distribuidor->uf ?: '—' }}</p>
                <p><span class="font-medium">CEP:</span> {{ $distribuidor->cep ?: '—' }}</p>
            </div>
            <div class="col-span-12 md:col-span-6">
                <h3 class="text-sm font-semibold mb-2">Endereço secundário</h3>
                <p><span class="font-medium">Endereço:</span> {{ $distribuidor->endereco2 ?: '—' }}, {{ $distribuidor->numero2 ?: '—' }}</p>
                <p><span class="font-medium">Bairro:</span> {{ $distribuidor->bairro2 ?: '—' }}</p>
                <p><span class="font-medium">Cidade:</span> {{ $distribuidor->cidade2 ?: '—' }}</p>
                <p><span class="font-medium">UF:</span> {{ $distribuidor->uf2 ?: '—' }}</p>
                <p><span class="font-medium">CEP:</span> {{ $distribuidor->cep2 ?: '—' }}</p>
            </div>
        </div>

        {{-- Cidades (chips, mesmo visual de UFs do Gestor) --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-2">Cidades de Atuação</h3>
            @php
                $cities = $distribuidor->cities ?? collect();
            @endphp
            @if($cities->isEmpty())
                <p class="text-gray-500">Nenhuma cidade cadastrada.</p>
            @else
                <div class="mt-1 flex flex-wrap gap-2">
                    @foreach($cities as $city)
                        <span class="inline-flex items-center rounded-md bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700 border border-gray-200">
                            {{ $city->name }}@if(!empty($city->uf ?? $city->state)) ({{ strtoupper($city->uf ?? $city->state) }}) @endif
                        </span>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Contratos / Anexos (mesmo padrão do Gestor, com suporte a contrato_cidade) --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-2">Contratos / Aditivos</h3>

            @if($distribuidor->anexos->isNotEmpty())
                <ul class="space-y-2">
                    @foreach($distribuidor->anexos as $anexo)
                        @php $isAtivo = (bool) $anexo->ativo; @endphp
                        <li class="p-3 rounded border flex items-start justify-between {{ $isAtivo ? 'border-blue-300 bg-blue-50' : 'border-gray-200' }}">
                            <div class="text-sm">
                                <div class="mb-1">
                                    <strong>{{ strtoupper($anexo->tipo) }}</strong>
                                    @if($anexo->descricao)
                                        <span class="text-gray-600">— {{ $anexo->descricao }}</span>
                                    @endif

                                    @if($anexo->assinado)
                                        <span class="ml-2 px-2 py-0.5 text-xs rounded bg-green-100 text-green-700">Assinado</span>
                                    @endif

                                    @if($isAtivo)
                                        <span class="ml-2 px-2 py-0.5 text-xs rounded bg-blue-100 text-blue-700">Ativo</span>
                                    @endif
                                </div>

                                {{-- >>> Cidade quando tipo = contrato_cidade (igual ao Gestor) <<< --}}
                                @if($anexo->tipo === 'contrato_cidade')
                                    @php $cidade = $anexo->cidade ?? null; @endphp
                                    <div class="text-gray-800 mb-1">
                                        Cidade:
                                        <strong>
                                            {{ $cidade ? ($cidade->nome ?? $cidade->name ?? ('ID '.$anexo->cidade_id)) : ('ID '.$anexo->cidade_id) }}
                                            @if($cidade && !empty($cidade->uf))
                                                ({{ $cidade->uf }})
                                            @endif
                                        </strong>
                                    </div>
                                @endif

                                <div class="text-gray-700">
                                    <div>
                                        Percentual deste contrato:
                                        <strong>
                                            {{ is_null($anexo->percentual_vendas) ? '—' : number_format($anexo->percentual_vendas, 2, ',', '.') . '%' }}
                                        </strong>
                                    </div>

                                    @if($anexo->data_assinatura)
                                        <div>Assinado em: {{ \Carbon\Carbon::parse($anexo->data_assinatura)->format('d/m/Y') }}</div>
                                    @endif

                                    @if($anexo->data_vencimento)
                                        <div>Vence em: {{ \Carbon\Carbon::parse($anexo->data_vencimento)->format('d/m/Y') }}</div>
                                    @endif
                                </div>
                            </div>

                            <div class="flex items-center gap-2">

                                <a href="{{ route('admin.distribuidores.anexos.edit', [$distribuidor, $anexo]) }}"
                                class="inline-flex h-8 items-center rounded-md border px-3 text-xs hover:bg-gray-50">
                                    Editar
                                </a>
                                @if($anexo->arquivo)
                                    <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($anexo->arquivo) }}"
                                       target="_blank"
                                       class="inline-flex h-8 items-center rounded-md border px-3 text-xs hover:bg-gray-50">
                                        Ver PDF
                                    </a>
                                @endif

                                @unless($isAtivo)
                                    <form method="POST" action="{{ route('admin.distribuidores.anexos.ativar', [$distribuidor, $anexo]) }}">
                                        @csrf
                                        <button type="submit"
                                                class="inline-flex h-8 items-center rounded-md bg-blue-600 px-3 text-xs text-white hover:bg-blue-700"
                                                onclick="return confirm('Ativar este contrato/aditivo?');">
                                            Ativar
                                        </button>
                                    </form>
                                @endunless

                                <form action="{{ route('admin.distribuidores.anexos.destroy', [$distribuidor, $anexo]) }}"
                                      method="POST"
                                      onsubmit="return confirm('Tem certeza que deseja excluir este anexo?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="inline-flex h-8 items-center rounded-md border px-3 text-xs text-red-600 hover:bg-red-50">
                                        Excluir
                                    </button>
                                </form>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="text-gray-500">Nenhum anexo enviado.</p>
            @endif
        </div>

    </div>
</x-app-layout>
