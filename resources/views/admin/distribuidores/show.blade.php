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
                   class="inline-flex h-9 items-center rounded-md border px-3 text-sm hover:bg-gray-50">Voltar</a>
                <a href="{{ route('admin.distribuidores.edit', $distribuidor) }}"
                   class="inline-flex h-9 items-center rounded-md bg-blue-600 px-3 text-sm text-white hover:bg-blue-700">Editar</a>
            </div>
        </div>
    </x-slot>

    <div class="max-w-6xl mx-auto p-6 space-y-6">

        {{-- Dados principais --}}
        <div class="bg-white rounded-lg shadow p-6 grid grid-cols-12 gap-4">
            <div class="col-span-12 md:col-span-6">
                <p><span class="font-medium">Gestor:</span> {{ $distribuidor->gestor?->razao_social }}</p>
                <p><span class="font-medium">Razão Social:</span> {{ $distribuidor->razao_social }}</p>
                <p><span class="font-medium">CNPJ:</span> {{ $distribuidor->cnpj_formatado ?? $distribuidor->cnpj }}</p>
                <p><span class="font-medium">Representante Legal:</span> {{ $distribuidor->representante_legal }}</p>
                <p><span class="font-medium">CPF:</span> {{ $distribuidor->cpf }}</p>
                <p><span class="font-medium">RG:</span> {{ $distribuidor->rg }}</p>
                <p><span class="font-medium">Telefone:</span> {{ $distribuidor->telefone_formatado ?? $distribuidor->telefone }}</p>
                <p><span class="font-medium">E-mail:</span>
                    @php $emailExib = $distribuidor->email_exibicao; @endphp
                    @if($emailExib && $emailExib !== 'Não informado')
                        <a href="mailto:{{ $emailExib }}" class="text-blue-600 hover:underline">{{ $emailExib }}</a>
                    @else
                        Não informado
                    @endif
                </p>
            </div>

            <div class="col-span-12 md:col-span-6">
                <p><span class="font-medium">Endereço:</span> {{ $distribuidor->endereco }}, {{ $distribuidor->numero }}</p>
                <p><span class="font-medium">Bairro:</span> {{ $distribuidor->bairro }}</p>
                <p><span class="font-medium">Cidade:</span> {{ $distribuidor->cidade }}</p>
                <p><span class="font-medium">UF:</span> {{ $distribuidor->uf }}</p>
                <p><span class="font-medium">CEP:</span> {{ $distribuidor->cep }}</p>
                <p><span class="font-medium">Percentual Vendas:</span> {{ number_format((float)$distribuidor->percentual_vendas, 2, ',', '.') }}%</p>

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
                            $classe = $ven->isPast() ? 'bg-red-50 text-red-700 border-red-200'
                                                     : ($ven->diffInDays($hoje) <= 30 ? 'bg-yellow-50 text-yellow-700 border-yellow-200'
                                                                                      : 'bg-gray-50 text-gray-700 border-gray-200');
                        @endphp
                        <span class="inline-flex items-center rounded-md px-2.5 py-1 text-xs font-medium border {{ $classe }}">
                            Vence em: {{ $ven->format('d/m/Y') }}
                        </span>
                    @else
                        <span class="inline-flex items-center rounded-md bg-gray-50 px-2.5 py-1 text-xs font-medium text-gray-700 border border-gray-200">
                            Vencimento não definido
                        </span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Cidades --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-2">Cidades de Atuação</h3>
            @if($distribuidor->cities->isNotEmpty())
                <ul class="list-disc pl-6">
                    @foreach($distribuidor->cities as $city)
                        <li>{{ $city->name }} {{ $city->uf ? "({$city->uf})" : '' }}</li>
                    @endforeach
                </ul>
            @else
                <p class="text-gray-500">Nenhuma cidade cadastrada.</p>
            @endif
        </div>

        {{-- Contatos --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-2">Contatos</h3>
            @if($distribuidor->contatos->isNotEmpty())
                <ul class="divide-y">
                    @foreach($distribuidor->contatos as $contato)
                        <li class="py-2">
                            <p>
                                <span class="font-medium">{{ $contato->nome }}</span>
                                @if($contato->tipo) <span class="text-gray-600">({{ $contato->tipo }})</span>@endif
                                @if($contato->preferencial)
                                    <span class="ml-2 inline-block px-2 py-0.5 text-xs bg-green-100 text-green-800 rounded">Preferencial</span>
                                @endif
                            </p>
                            <p class="text-sm text-gray-600">
                                @if($contato->email) E-mail: {{ $contato->email }} @endif
                                @if($contato->telefone) {{ $contato->email ? ' | ' : '' }} Tel: {{ $contato->telefone }} @endif
                                @if($contato->whatsapp) {{ ($contato->email || $contato->telefone) ? ' | ' : '' }} Whats: {{ $contato->whatsapp }} @endif
                            </p>
                            @if($contato->cargo)
                                <p class="text-sm text-gray-600">Cargo: {{ $contato->cargo }}</p>
                            @endif
                            @if($contato->observacoes)
                                <p class="text-sm mt-1">{{ $contato->observacoes }}</p>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="text-gray-500">Nenhum contato informado.</p>
            @endif
        </div>

        {{-- Anexos --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-2">Anexos</h3>

            @if($distribuidor->anexos->isNotEmpty())
                <ul class="divide-y">
                    @foreach($distribuidor->anexos as $anexo)
                        <li class="py-2 flex items-center justify-between">
                            <div>
                                <p>
                                    <span class="font-medium">{{ ucfirst($anexo->tipo) }}</span>
                                    @if($anexo->descricao) - {{ $anexo->descricao }} @endif
                                    @if($anexo->assinado)
                                        <span class="ml-2 inline-block px-2 py-0.5 text-xs bg-green-100 text-green-800 rounded">
                                            Assinado
                                        </span>
                                    @endif
                                </p>
                            </div>

                            <div class="flex items-center gap-2">
                                <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($anexo->arquivo) }}"
                                target="_blank"
                                class="inline-flex h-8 items-center rounded-md border px-3 text-xs hover:bg-gray-50">
                                    Ver PDF
                                </a>

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
