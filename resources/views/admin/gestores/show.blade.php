{{-- resources/views/admin/gestores/show.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">
                Gestor: {{ $gestor->razao_social }}
            </h2>

            <div class="flex gap-2">
                <form method="POST" action="{{ route('admin.gestores.destroy', $gestor) }}"
                      onsubmit="return confirm('Tem certeza que deseja remover este gestor?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="inline-flex h-9 items-center rounded-md bg-red-600 px-3 text-sm text-white hover:bg-red-700">
                        Remover
                    </button>
                </form>

                <a href="{{ route('admin.gestores.index') }}"
                   class="inline-flex h-9 items-center rounded-md border px-3 text-sm hover:bg-gray-50">Voltar</a>

                <a href="{{ route('admin.gestores.edit', $gestor) }}"
                   class="inline-flex h-9 items-center rounded-md bg-blue-600 px-3 text-sm text-white hover:bg-blue-700">Editar</a>
            </div>
        </div>
    </x-slot>

    <div class="max-w-6xl mx-auto p-6 space-y-6">

        {{-- Dados principais --}}
        <div class="bg-white rounded-lg shadow p-6 grid grid-cols-12 gap-4">
            <div class="col-span-12 md:col-span-6">
                <p><span class="font-medium">Razão Social:</span> {{ $gestor->razao_social }}</p>
                <p><span class="font-medium">CNPJ:</span> {{ $gestor->cnpj_formatado }}</p>
                <p><span class="font-medium">Representante Legal:</span> {{ $gestor->representante_legal }}</p>
                <p><span class="font-medium">CPF (representante):</span> {{ $gestor->cpf_formatado }}</p>
                <p><span class="font-medium">RG:</span> {{ $gestor->rg_formatado }}</p>

                {{-- Telefones --}}
                @php
                    $telefones = $gestor->telefones ?: [];
                    if (empty($telefones) && $gestor->telefone) $telefones = [$gestor->telefone]; // compat
                @endphp
                <p class="mt-2">
                    <span class="font-medium">Telefones:</span>
                    @if(!empty($telefones))
                        {{ collect($telefones)->filter()->implode(', ') }}
                    @else
                        —
                    @endif
                </p>

                {{-- E-mails --}}
                @php
                    $emails = $gestor->emails ?: [];
                    if (empty($emails) && $gestor->email) $emails = [$gestor->email]; // compat
                @endphp
                <p>
                    <span class="font-medium">E-mails:</span>
                    @if(!empty($emails))
                        {!! collect($emails)->filter()->map(fn($em) => '<a class="text-blue-600 hover:underline" href="mailto:'.$em.'">'.$em.'</a>')->implode(', ') !!}
                    @else
                        —
                    @endif
                </p>
            </div>

            <div class="col-span-12 md:col-span-6">
                {{-- UFs de Atuação (da relação ufs) --}}
                @php
                    $ufsAtuacao = $gestor->ufs->pluck('uf')->sort()->values();
                @endphp
                <div>
                    <span class="font-medium">UF(s) de Atuação:</span>
                    @if($ufsAtuacao->isEmpty())
                        <span>—</span>
                    @else
                        <div class="mt-1 flex flex-wrap gap-2">
                            @foreach($ufsAtuacao as $uf)
                                <span class="inline-flex items-center rounded-md bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700 border border-gray-200">
                                    {{ $uf }}
                                </span>
                            @endforeach
                        </div>
                    @endif
                </div>

                <p class="mt-2">
                    <span class="font-medium">Percentual Vendas (aplicado):</span>
                    {{ number_format((float)$gestor->percentual_vendas, 2, ',', '.') }}%
                </p>
                <p>
                    <span class="font-medium">Contrato Assinado:</span>
                    {{ $gestor->contrato_assinado ? 'Sim' : 'Não' }}
                </p>
                <p>
                    <span class="font-medium">Vencimento Contrato (ativo):</span>
                    {{ optional($gestor->vencimento_contrato)->format('d/m/Y') ?: '—' }}
                </p>

                <div class="mt-3 flex flex-wrap gap-2">
                    @if($gestor->contrato_assinado)
                        <span class="inline-flex items-center rounded-md bg-green-50 px-2.5 py-1 text-xs font-medium text-green-700 border border-green-200">
                            Contrato assinado
                        </span>
                    @else
                        <span class="inline-flex items-center rounded-md bg-yellow-50 px-2.5 py-1 text-xs font-medium text-yellow-700 border border-yellow-200">
                            Contrato pendente
                        </span>
                    @endif

                    @if($gestor->vencimento_contrato)
                        @php
                            $ven = \Illuminate\Support\Carbon::parse($gestor->vencimento_contrato);
                            $hoje = now();
                            $classe = $ven->isPast() ? 'bg-red-50 text-red-700 border-red-200'
                                : ($ven->diffInDays($hoje) <= 30 ? 'bg-yellow-50 text-yellow-700 border-yellow-200'
                                : 'bg-gray-50 text-gray-700 border-gray-200');
                        @endphp
                        <span class="inline-flex items-center rounded-md px-2.5 py-1 text-xs font-medium border {{ $classe }}">
                            Vence em: {{ $ven->format('d/m/Y') }}
                        </span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Endereços --}}
        <div class="bg-white rounded-lg shadow p-6 grid grid-cols-12 gap-4">
            <div class="col-span-12 md:col-span-6">
                <h3 class="text-sm font-semibold mb-2">Endereço principal</h3>
                <p><span class="font-medium">Endereço:</span> {{ $gestor->endereco ?: '—' }}, {{ $gestor->numero ?: '—' }}</p>
                <p><span class="font-medium">Bairro:</span> {{ $gestor->bairro ?: '—' }}</p>
                <p><span class="font-medium">Cidade:</span> {{ $gestor->cidade ?: '—' }}</p>
                <p><span class="font-medium">UF:</span> {{ $gestor->uf ?: '—' }}</p>
                <p><span class="font-medium">CEP:</span> {{ $gestor->cep ?: '—' }}</p>
            </div>
            <div class="col-span-12 md:col-span-6">
                <h3 class="text-sm font-semibold mb-2">Endereço secundário</h3>
                <p><span class="font-medium">Endereço:</span> {{ $gestor->endereco2 ?: '—' }}, {{ $gestor->numero2 ?: '—' }}</p>
                <p><span class="font-medium">Bairro:</span> {{ $gestor->bairro2 ?: '—' }}</p>
                <p><span class="font-medium">Cidade:</span> {{ $gestor->cidade2 ?: '—' }}</p>
                <p><span class="font-medium">UF:</span> {{ $gestor->uf2 ?: '—' }}</p>
                <p><span class="font-medium">CEP:</span> {{ $gestor->cep2 ?: '—' }}</p>
            </div>
        </div>

        {{-- Contratos / Anexos --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-2">Contratos / Aditivos</h3>

            @if($gestor->anexos->isNotEmpty())
                <ul class="space-y-2">
                    @foreach($gestor->anexos as $anexo)
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

                                {{-- >>> AQUI: Cidade para contrato_cidade <<< --}}
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
                                @if($anexo->arquivo)
                                    <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($anexo->arquivo) }}"
                                       target="_blank"
                                       class="inline-flex h-8 items-center rounded-md border px-3 text-xs hover:bg-gray-50">
                                        Ver PDF
                                    </a>
                                @endif

                                @unless($isAtivo)
                                    <form method="POST" action="{{ route('admin.gestores.anexos.ativar', [$gestor, $anexo]) }}">
                                        @csrf
                                        <button type="submit"
                                                class="inline-flex h-8 items-center rounded-md bg-blue-600 px-3 text-xs text-white hover:bg-blue-700"
                                                onclick="return confirm('Ativar este contrato/aditivo?');">
                                            Ativar
                                        </button>
                                    </form>
                                @endunless

                                <form action="{{ route('admin.gestores.anexos.destroy', [$gestor, $anexo]) }}"
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
