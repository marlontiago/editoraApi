{{-- resources/views/admin/gestores/show.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold text-gray-800 leading-tight">
                    Gestor: {{ $gestor->razao_social }}
                </h2>
                <p class="text-sm text-gray-500 mt-1">Dados cadastrais, endereços e anexos de contrato.</p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('admin.gestores.index') }}"
                   class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 h-10 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Voltar
                </a>

                <a href="{{ route('admin.gestores.edit', $gestor) }}"
                   class="inline-flex items-center gap-2 rounded-lg bg-gray-900 px-4 h-10 text-sm font-semibold text-white hover:bg-gray-800 transition">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 20h9"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4 12.5-12.5z"/>
                    </svg>
                    Editar
                </a>

                <form method="POST" action="{{ route('admin.gestores.destroy', $gestor) }}"
                      onsubmit="return confirm('Tem certeza que deseja remover este gestor?');"
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
                    <div class="grid grid-cols-12 gap-3">
                        <div class="col-span-12">
                            <div class="text-xs uppercase tracking-wide text-gray-500">Razão Social</div>
                            <div class="text-sm font-semibold text-gray-900">{{ $gestor->razao_social }}</div>
                        </div>

                        <div class="col-span-12 sm:col-span-6">
                            <div class="text-xs uppercase tracking-wide text-gray-500">CNPJ</div>
                            <div class="text-sm text-gray-900">{{ $gestor->cnpj_formatado }}</div>
                        </div>

                        <div class="col-span-12 sm:col-span-6">
                            <div class="text-xs uppercase tracking-wide text-gray-500">Representante Legal</div>
                            <div class="text-sm text-gray-900">{{ $gestor->representante_legal }}</div>
                        </div>

                        <div class="col-span-12 sm:col-span-6">
                            <div class="text-xs uppercase tracking-wide text-gray-500">CPF (representante)</div>
                            <div class="text-sm text-gray-900">{{ $gestor->cpf_formatado }}</div>
                        </div>

                        <div class="col-span-12 sm:col-span-6">
                            <div class="text-xs uppercase tracking-wide text-gray-500">RG</div>
                            <div class="text-sm text-gray-900">{{ $gestor->rg_formatado }}</div>
                        </div>
                    </div>

                    {{-- Telefones --}}
                    @php
                        $telefones = $gestor->telefones ?: [];
                        if (empty($telefones) && $gestor->telefone) $telefones = [$gestor->telefone]; // compat
                    @endphp
                    <div class="pt-2">
                        <div class="text-xs uppercase tracking-wide text-gray-500">Telefones</div>
                        <div class="text-sm text-gray-900">
                            @if(!empty($telefones))
                                {{ collect($telefones)->filter()->implode(', ') }}
                            @else
                                <span class="text-gray-500">—</span>
                            @endif
                        </div>
                    </div>

                    {{-- E-mails --}}
                    @php
                        $emails = $gestor->emails ?: [];
                        if (empty($emails) && $gestor->email) $emails = [$gestor->email]; // compat
                    @endphp
                    <div>
                        <div class="text-xs uppercase tracking-wide text-gray-500">E-mails</div>
                        <div class="text-sm">
                            @if(!empty($emails))
                                {!! collect($emails)->filter()->map(fn($em) => '<a class="text-blue-700 hover:underline" href="mailto:'.$em.'">'.$em.'</a>')->implode(', ') !!}
                            @else
                                <span class="text-gray-500">—</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-span-12 md:col-span-6 space-y-3">
                    {{-- UFs de Atuação (da relação ufs) --}}
                    @php
                        $ufsAtuacao = $gestor->ufs->pluck('uf')->sort()->values();
                    @endphp
                    <div>
                        <div class="text-xs uppercase tracking-wide text-gray-500">UF(s) de Atuação</div>
                        @if($ufsAtuacao->isEmpty())
                            <div class="text-sm text-gray-500">—</div>
                        @else
                            <div class="mt-2 flex flex-wrap gap-2">
                                @foreach($ufsAtuacao as $uf)
                                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-700">
                                        {{ $uf }}
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="grid grid-cols-12 gap-3 pt-1">
                        <div class="col-span-12 sm:col-span-6">
                            <div class="text-xs uppercase tracking-wide text-gray-500">Percentual Vendas (aplicado)</div>
                            <div class="text-sm text-gray-900">
                                {{ number_format((float)$gestor->percentual_vendas, 2, ',', '.') }}%
                            </div>
                        </div>

                        <div class="col-span-12 sm:col-span-6">
                            <div class="text-xs uppercase tracking-wide text-gray-500">Contrato Assinado</div>
                            <div class="text-sm text-gray-900">
                                {{ $gestor->contrato_assinado ? 'Sim' : 'Não' }}
                            </div>
                        </div>

                        <div class="col-span-12">
                            <div class="text-xs uppercase tracking-wide text-gray-500">Vencimento Contrato (ativo)</div>
                            <div class="text-sm text-gray-900">
                                {{ optional($gestor->vencimento_contrato)->format('d/m/Y') ?: '—' }}
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2 pt-1">
                        @if($gestor->contrato_assinado)
                            <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700">
                                Contrato assinado
                            </span>
                        @else
                            <span class="inline-flex items-center rounded-full bg-yellow-100 px-2 py-0.5 text-xs font-medium text-yellow-800">
                                Contrato pendente
                            </span>
                        @endif

                        @if($gestor->vencimento_contrato)
                            @php
                                $ven = \Illuminate\Support\Carbon::parse($gestor->vencimento_contrato);
                                $hoje = now();
                                $classe = $ven->isPast()
                                    ? 'bg-red-100 text-red-700'
                                    : ($ven->diffInDays($hoje) <= 30 ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-700');
                            @endphp
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $classe }}">
                                Vence em: {{ $ven->format('d/m/Y') }}
                            </span>
                        @endif
                    </div>
                </div>

            </div>
        </div>

        {{-- Endereços --}}
        <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-100 px-6 py-5">
            <div class="grid grid-cols-12 gap-6">
                <div class="col-span-12 md:col-span-6">
                    <h3 class="text-sm font-semibold text-gray-800">Endereço principal</h3>
                    <div class="mt-3 grid grid-cols-12 gap-3 text-sm">
                        <div class="col-span-12">
                            <div class="text-xs uppercase tracking-wide text-gray-500">Endereço</div>
                            <div class="text-gray-900">{{ $gestor->endereco ?: '—' }}, {{ $gestor->numero ?: '—' }}</div>
                        </div>
                        <div class="col-span-12 sm:col-span-6">
                            <div class="text-xs uppercase tracking-wide text-gray-500">Bairro</div>
                            <div class="text-gray-900">{{ $gestor->bairro ?: '—' }}</div>
                        </div>
                        <div class="col-span-12 sm:col-span-6">
                            <div class="text-xs uppercase tracking-wide text-gray-500">Cidade</div>
                            <div class="text-gray-900">{{ $gestor->cidade ?: '—' }}</div>
                        </div>
                        <div class="col-span-12 sm:col-span-6">
                            <div class="text-xs uppercase tracking-wide text-gray-500">UF</div>
                            <div class="text-gray-900">{{ $gestor->uf ?: '—' }}</div>
                        </div>
                        <div class="col-span-12 sm:col-span-6">
                            <div class="text-xs uppercase tracking-wide text-gray-500">CEP</div>
                            <div class="text-gray-900">{{ $gestor->cep ?: '—' }}</div>
                        </div>
                    </div>
                </div>

                <div class="col-span-12 md:col-span-6">
                    <h3 class="text-sm font-semibold text-gray-800">Endereço secundário</h3>
                    <div class="mt-3 grid grid-cols-12 gap-3 text-sm">
                        <div class="col-span-12">
                            <div class="text-xs uppercase tracking-wide text-gray-500">Endereço</div>
                            <div class="text-gray-900">{{ $gestor->endereco2 ?: '—' }}, {{ $gestor->numero2 ?: '—' }}</div>
                        </div>
                        <div class="col-span-12 sm:col-span-6">
                            <div class="text-xs uppercase tracking-wide text-gray-500">Bairro</div>
                            <div class="text-gray-900">{{ $gestor->bairro2 ?: '—' }}</div>
                        </div>
                        <div class="col-span-12 sm:col-span-6">
                            <div class="text-xs uppercase tracking-wide text-gray-500">Cidade</div>
                            <div class="text-gray-900">{{ $gestor->cidade2 ?: '—' }}</div>
                        </div>
                        <div class="col-span-12 sm:col-span-6">
                            <div class="text-xs uppercase tracking-wide text-gray-500">UF</div>
                            <div class="text-gray-900">{{ $gestor->uf2 ?: '—' }}</div>
                        </div>
                        <div class="col-span-12 sm:col-span-6">
                            <div class="text-xs uppercase tracking-wide text-gray-500">CEP</div>
                            <div class="text-gray-900">{{ $gestor->cep2 ?: '—' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Contratos / Anexos --}}
        <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-100 px-6 py-5">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800">Contratos / Aditivos</h3>
                    <p class="text-sm text-gray-500 mt-1">Anexos enviados, status e ações.</p>
                </div>
            </div>

            @if($gestor->anexos->isNotEmpty())
                <ul class="mt-4 space-y-3">
                    @foreach($gestor->anexos as $anexo)
                        @php $isAtivo = (bool) $anexo->ativo; @endphp

                        <li class="rounded-xl border p-4 flex flex-col md:flex-row md:items-start md:justify-between gap-4
                                   {{ $isAtivo ? 'border-blue-200 bg-blue-50' : 'border-gray-200 bg-white' }}">
                            <div class="text-sm min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-semibold text-gray-900">{{ strtoupper($anexo->tipo) }}</span>

                                    @if($anexo->descricao)
                                        <span class="text-gray-600">— {{ $anexo->descricao }}</span>
                                    @endif

                                    @if($anexo->assinado)
                                        <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700">
                                            Assinado
                                        </span>
                                    @endif

                                    @if($isAtivo)
                                        <span class="inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700">
                                            Ativo
                                        </span>
                                    @endif
                                </div>

                                {{-- Cidade para contrato_cidade --}}
                                @if($anexo->tipo === 'contrato_cidade')
                                    @php $cidade = $anexo->cidade ?? null; @endphp
                                    <div class="mt-2 text-gray-800">
                                        Cidade:
                                        <strong>
                                            {{ $cidade ? ($cidade->nome ?? $cidade->name ?? ('ID '.$anexo->cidade_id)) : ('ID '.$anexo->cidade_id) }}
                                            @if($cidade && !empty($cidade->uf))
                                                ({{ $cidade->uf }})
                                            @endif
                                        </strong>
                                    </div>
                                @endif

                                <div class="mt-2 text-gray-700 space-y-1">
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

                            <div class="flex items-center gap-2 shrink-0">
                                {{-- Editar (sempre visível) --}}
                                <a href="{{ route('admin.gestores.anexos.edit', [$gestor, $anexo]) }}"
                                   class="inline-flex items-center justify-center h-9 rounded-lg border border-gray-200 bg-white px-3 text-xs font-semibold text-gray-700 hover:bg-gray-50 transition">
                                    Editar
                                </a>

                                {{-- Ver PDF (se existir) --}}
                                @if($anexo->arquivo)
                                    <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($anexo->arquivo) }}"
                                       target="_blank"
                                       class="inline-flex items-center justify-center h-9 rounded-lg border border-gray-200 bg-white px-3 text-xs font-semibold text-gray-700 hover:bg-gray-50 transition">
                                        Ver PDF
                                    </a>
                                @endif

                                {{-- Ativar: somente se não for contrato_cidade e não estiver ativo --}}
                                @unless($isAtivo || $anexo->tipo === 'contrato_cidade')
                                    <form method="POST" action="{{ route('admin.gestores.anexos.ativar', [$gestor, $anexo]) }}">
                                        @csrf
                                        <button type="submit"
                                                class="inline-flex items-center justify-center h-9 rounded-lg bg-gray-900 px-3 text-xs font-semibold text-white hover:bg-gray-800 transition"
                                                onclick="return confirm('Ativar este contrato/aditivo?');">
                                            Ativar
                                        </button>
                                    </form>
                                @endunless

                                {{-- Excluir --}}
                                <form action="{{ route('admin.gestores.anexos.destroy', [$gestor, $anexo]) }}"
                                      method="POST"
                                      onsubmit="return confirm('Tem certeza que deseja excluir este anexo?');">
                                    @csrf
                                    @method('DELETE')

                                    @php
                                        $venAnexo = $anexo->data_vencimento ? \Carbon\Carbon::parse($anexo->data_vencimento) : null;
                                    @endphp

                                    <button type="submit"
                                            class="inline-flex items-center justify-center h-9 rounded-lg border border-red-200 bg-white px-3 text-xs font-semibold text-red-700 hover:bg-red-50 transition">
                                        Excluir
                                    </button>
                                </form>
                            </div>

                            @if($venAnexo && $venAnexo->isPast())
                                <div class="md:col-span-12">
                                    <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700">
                                        Contrato vencido
                                    </span>
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="mt-4 text-gray-500">Nenhum anexo enviado.</p>
            @endif
        </div>

    </div>
</x-app-layout>
