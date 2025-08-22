<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pedido;
use App\Models\Produto;
use App\Models\City;
use App\Models\Distribuidor;
use App\Models\Gestor;
use App\Models\Cliente;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

class PedidoController extends Controller
{
    public function index()
    {
        $produtosComEstoqueBaixo = Produto::where('quantidade_estoque', '<=', 100)->get();

        $estoqueParaPedidosEmPotencial = DB::table('pedido_produto as pp')
        ->join('pedidos as pe', 'pe.id', '=', 'pp.pedido_id')
        ->join('produtos as pr', 'pr.id', '=', 'pp.produto_id')
        ->where('pe.status', 'em_andamento')
        ->groupBy('pp.produto_id', 'pr.nome', 'pr.quantidade_estoque')
        ->havingRaw('SUM(pp.quantidade) > pr.quantidade_estoque')
        ->get([
            'pp.produto_id',
            'pr.nome',
            DB::raw('SUM(pp.quantidade) as qtd_em_pedidos'),
            'pr.quantidade_estoque',
            DB::raw('SUM(pp.quantidade) - pr.quantidade_estoque AS excedente'),
        ]);

        $pedidos = Pedido::with(['cidades', 'gestor', 'distribuidor.user', 'cliente'])->latest()->get();

        return view('admin.pedidos.index', compact('pedidos', 'produtosComEstoqueBaixo','estoqueParaPedidosEmPotencial'));
    }

    public function create()
    {
        $gestores       = Gestor::with('user')->orderBy('razao_social')->get();
        $distribuidores = Distribuidor::with('user')->orderBy('razao_social')->get();
        $produtos       = Produto::orderBy('nome')->get();
        $cidades        = City::orderBy('name')->get();
        $clientes       = Cliente::orderBy('razao_social')->get();

        return view('admin.pedidos.create', compact('produtos', 'cidades', 'gestores', 'distribuidores', 'clientes'));
    }

    public function store(Request $request)
    {
        // Regras dinâmicas para cidade
        $cidadeRules = ['nullable', 'integer'];

        if ($request->filled('distribuidor_id')) {
            $cidadeRules[] = 'required';
            $cidadeRules[] = Rule::exists('city_distribuidor', 'city_id')
                ->where(fn ($q) => $q->where('distribuidor_id', $request->distribuidor_id));
        } else {
            if ($request->filled('cidade_id')) {
                $cidadeRules[] = 'exists:cities,id';
            }
        }

        //Define as regras de validação, em $cidadeRules utiliza a função acima.

        $rules = [
            'data'                   => ['required', 'date', 'after_or_equal:' . Carbon::now('America/Sao_Paulo')->toDateString()],
            'cliente_id'             => ['required', 'exists:clientes,id'],
            'gestor_id'              => ['nullable', 'exists:gestores,id'],
            'distribuidor_id'        => ['nullable', 'exists:distribuidores,id'],
            'cidade_id'              => $cidadeRules,
            'produtos'               => ['required', 'array', 'min:1'],
            'produtos.*.id'          => ['required', 'exists:produtos,id', 'distinct'],
            'produtos.*.quantidade'  => ['required', 'integer', 'min:1'],
            'produtos.*.desconto'    => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];

        //Define as mensagens de erro personalizadas para as regras de validação.

        $messages = [
            'data.after_or_equal' => 'A data do pedido não pode ser anterior à data atual.',
            'cliente_id.required' => 'Selecione um cliente.',
            'cidade_id.required'  => 'Selecione a cidade da venda (ao escolher um distribuidor).',
            'cidade_id.exists'    => 'A cidade selecionada não pertence ao distribuidor escolhido.',
        ];

        // Valida os dados do pedido
        $validated = $request->validate($rules, $messages);

        // Regras condicionais para cidade
        if (filled($request->gestor_id) || filled($request->distribuidor_id)) {
            if (blank($request->cidade_id)) {
                return back()->withErrors(['cidade_id' => 'Selecione a cidade da venda.'])->withInput();
            }
        }

        // Filtro para verificar se a cidade pertence ao distribuidor ou gestor selecionado
        // Se a cidade foi selecionada, verifica se pertence ao distribuidor ou gestor
        // Se o distribuidor foi selecionado, verifica se a cidade pertence a ele
        // Se o gestor foi selecionado, verifica se a cidade pertence à UF do gestor

        if (filled($request->cidade_id)) {
            if (filled($request->distribuidor_id)) {
                $pertence = DB::table('city_distribuidor')
                    ->where('city_id', $request->cidade_id)
                    ->where('distribuidor_id', $request->distribuidor_id)
                    ->exists();

                if (!$pertence) {
                    return back()->withErrors([
                        'cidade_id' => 'A cidade selecionada não pertence ao distribuidor escolhido.',
                    ])->withInput();
                }
            } elseif (filled($request->gestor_id)) {
                $gestor = Gestor::find($request->gestor_id);
                if ($gestor) {
                    $okUF = City::whereKey($request->cidade_id)
                        ->whereRaw('UPPER(state) = ?', [strtoupper($gestor->estado_uf)])
                        ->exists();

                    if (!$okUF) {
                        return back()->withErrors([
                            'cidade_id' => 'A cidade selecionada não pertence à UF do gestor.',
                        ])->withInput();
                    }
                }
            }
        }

        // Inicia a transação

        try {
            DB::beginTransaction();

            // Cria o pedido
            $pedido = Pedido::create([
                'cliente_id'      => $request->cliente_id,
                'gestor_id'       => $request->gestor_id,
                'distribuidor_id' => $request->distribuidor_id,
                'data'            => $request->data,
                'status'          => 'em_andamento',
            ]);

            // Se a cidade foi selecionada, associa ao pedido

            if ($request->filled('cidade_id')) {
                $pedido->cidades()->sync([$request->cidade_id]);
            } else {
                $pedido->cidades()->sync([]);
            }

            // Snapshot inicial para log

            $pesoTotal = 0;
            $totalCaixas = 0;
            $valorBruto = 0;
            $valorFinal = 0;

            // Agrega duplicatas

            $itens = collect($validated['produtos'])
                ->groupBy('id')
                ->map(fn ($g) => [
                    'id'         => (int) $g->first()['id'],
                    'quantidade' => (int) $g->sum('quantidade'),
                    'desconto'   => isset($g->first()['desconto']) ? (float) $g->first()['desconto'] : 0.0,
                ])
                ->values()
                ->all();

            // Valida disponibilidade de estoque e calcula totais
            // Para cada item, verifica se há estoque suficiente e calcula os totais
            // Se não houver estoque suficiente, lança uma exceção
            // Se houver estoque suficiente, adiciona o item ao pedido

            foreach ($itens as $produtoData) {
                $produto  = Produto::whereKey($produtoData['id'])->lockForUpdate()->firstOrFail();
                $qtd      = (int) $produtoData['quantidade'];
                $descItem = (float) ($produtoData['desconto'] ?? 0.0);

                // estoque
                $disponivel = (int) $produto->quantidade_estoque;
                if ($disponivel < $qtd) {
                    throw new \RuntimeException(
                        "Estoque insuficiente para o produto {$produto->nome}. Disponível: {$disponivel}, solicitado: {$qtd}"
                    );
                }

                $precoUnit   = (float) $produto->preco;
                $subBruto    = $precoUnit * $qtd;
                $precoDesc   = $precoUnit * (1 - ($descItem / 100));
                $subDesc     = $precoDesc * $qtd;
                $pesoItem    = (float) ($produto->peso ?? 0) * $qtd;
                $porCaixa    = max(1, (int) $produto->quantidade_por_caixa);
                $caixas      = (int) ceil($qtd / $porCaixa);

                $pedido->produtos()->attach($produto->id, [
                    'quantidade'           => $qtd,
                    'preco_unitario'       => $precoUnit,
                    'desconto_item'        => $descItem,
                    'desconto_aplicado'    => $descItem,
                    'subtotal'             => $subDesc,
                    'peso_total_produto'   => $pesoItem,
                    'caixas'               => $caixas,
                ]);
                
                $pesoTotal   += $pesoItem;
                $totalCaixas += $caixas;
                $valorBruto  += $subBruto;
                $valorFinal  += $subDesc;
            }

            // Atualiza totais do pedido
            $pedido->update([
                'peso_total'   => $pesoTotal,
                'total_caixas' => $totalCaixas,
                'valor_bruto'  => $valorBruto,
                'valor_total'  => $valorFinal,
            ]);

            $pedido->registrarLog('Pedido criado', 'Pedido criado.');

            DB::commit();
            return redirect()->route('admin.pedidos.index')->with('success', 'Pedido criado com sucesso!');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Erro ao criar pedido: ' . $e->getMessage()])->withInput();
        }
    }

    public function show(Pedido $pedido)
    {
        $pedido->load([
            'cidades',
            'gestor',
            'distribuidor.user',
            'cliente',
            'produtos',
            'logs.user',
        ]);

        return view('admin.pedidos.show', compact('pedido'));
    }

    public function exportar(Pedido $pedido, string $tipo)
    {
        $pedido->load(['produtos', 'cidades', 'gestor', 'distribuidor.user', 'cliente']);

        if (!in_array($tipo, ['relatorio', 'orcamento'])) abort(404);

        $view = $tipo === 'relatorio'
            ? 'admin.pedidos.pdf.relatorio'
            : 'admin.pedidos.pdf.orcamento';

        $pdf = Pdf::loadView($view, [
            'pedido' => $pedido,
            'tipo'   => $tipo,
        ])
        ->setPaper('a4')
        ->setOptions([
            'defaultFont'     => 'DejaVu Sans',
            'isRemoteEnabled' => true,
        ]);

        return $pdf->download("pedido-{$pedido->id}-{$tipo}.pdf");
    }

    public function edit(Pedido $pedido)
    {
        // Verifica se o pedido já foi finalizado
        // Se sim, não permite edição e redireciona com mensagem de erro
        if ($pedido->status === 'finalizado') {
            return redirect()
                ->route('admin.pedidos.show', $pedido)
                ->with(['error' => 'Pedido finalizado não pode mais ser editado.']);
        }

        // Carrega os relacionamentos necessários e prepara os dados para a view

        $pedido->load(['cidades', 'produtos', 'gestor', 'distribuidor.user', 'cliente']);

        // Carrega gestores, distribuidores, produtos, cidades e clientes para a view
        $gestores       = Gestor::with('user')->orderBy('razao_social')->get();
        $distribuidores = Distribuidor::with('user')->orderBy('razao_social')->get();
        $produtos       = Produto::orderBy('nome')->get();
        $cidades        = City::orderBy('name')->get();
        $clientes       = Cliente::orderBy('razao_social')->get();

        // Prepara os itens atuais do pedido para edição
        // Mapeia os produtos do pedido para um array com as informações necessárias

        $itensAtuais = $pedido->produtos->mapWithKeys(fn($p) => [
            $p->id => [
                'quantidade'     => (int) $p->pivot->quantidade,
                'preco_unitario' => (float) $p->pivot->preco_unitario,
                'desconto_item'  => (float) $p->pivot->desconto_item,
            ]
        ])->toArray();

        return view('admin.pedidos.edit', compact(
            'pedido','gestores','distribuidores','produtos','cidades','clientes','itensAtuais'
        ));
    }

    public function update(Pedido $pedido, Request $request)
{
    if ($pedido->status === 'finalizado') {
        return back()->with('error', 'Não é mais possível editar: este pedido já foi finalizado.');
    }

    // Limpa e valida os produtos do pedido
    // Filtra os produtos para garantir que apenas aqueles com ID e quantidade válidos sejam processados
    // Mapeia os produtos para um formato consistente, garantindo que cada produto tenha ID, quantidade e desconto

    $produtosLimpos = collect($request->input('produtos', []))
        ->filter(function ($row) {
            $id = $row['id'] ?? null;
            $q  = $row['quantidade'] ?? null;
            return is_numeric($id) && (int)$id > 0 && is_numeric($q) && (int)$q > 0;
        })
        ->map(fn ($r) => [
            'id'        => (int) $r['id'],
            'quantidade'=> (int) $r['quantidade'],
            'desconto'  => isset($r['desconto']) ? (float) $r['desconto'] : 0.0,
        ])
        ->values()
        ->all();        
    
    // Atualiza a requisição com os produtos limpos
    // Isso garante que apenas os produtos válidos sejam processados na validação e atualização do pedido
    $request->merge(['produtos' => $produtosLimpos]);

    // Regras dinâmicas para cidade
    // Define as regras de validação para a cidade com base no distribuidor selecionado

    $cidadeRules = ['nullable', 'integer'];
    if ($request->filled('distribuidor_id')) {
        $cidadeRules[] = 'required';
        $cidadeRules[] = Rule::exists('city_distribuidor', 'city_id')
            ->where(fn ($q) => $q->where('distribuidor_id', $request->distribuidor_id));
    } else {
        if ($request->filled('cidade_id')) {
            $cidadeRules[] = 'exists:cities,id';
        }
    }

    $rules = [
        'data'                   => ['required', 'date', 'after_or_equal:' . Carbon::now('America/Sao_Paulo')->toDateString()],
        'cliente_id'             => ['required', 'exists:clientes,id'],
        'gestor_id'              => ['nullable', 'exists:gestores,id'],
        'distribuidor_id'        => ['nullable', 'exists:distribuidores,id'],
        'cidade_id'              => $cidadeRules,
        'status'                 => ['required', 'in:em_andamento,finalizado,cancelado'],
        'produtos'               => ['required', 'array', 'min:1'],
        'produtos.*.id'          => ['required', 'exists:produtos,id', 'distinct'],
        'produtos.*.quantidade'  => ['required', 'integer', 'min:1'],
        'produtos.*.desconto'    => ['nullable', 'numeric', 'min:0', 'max:100'],
    ];

    $messages = [
        'cidade_id.required' => 'Selecione a cidade da venda (ao escolher um distribuidor).',
        'cidade_id.exists'   => 'A cidade selecionada não pertence ao distribuidor escolhido.',
    ];

    $validated = $request->validate($rules, $messages);

    // Regras condicionais para cidade
    if (filled($request->gestor_id) || filled($request->distribuidor_id)) {
        if (blank($request->cidade_id)) {
            return back()->withErrors(['cidade_id' => 'Selecione a cidade da venda.'])->withInput();
        }
    }

    // Filtro para verificar se a cidade pertence ao distribuidor ou gestor selecionado

    if (filled($request->cidade_id)) {
        if (filled($request->distribuidor_id)) {
            $pertence = DB::table('city_distribuidor')
                ->where('city_id', $request->cidade_id)
                ->where('distribuidor_id', $request->distribuidor_id)
                ->exists();

            if (!$pertence) {
                return back()->withErrors([
                    'cidade_id' => 'A cidade selecionada não pertence ao distribuidor escolhido.',
                ])->withInput();
            }
        } elseif (filled($request->gestor_id)) {
            $gestor = Gestor::find($request->gestor_id);
            if ($gestor) {
                $okUF = City::whereKey($request->cidade_id)
                    ->whereRaw('UPPER(state) = ?', [strtoupper($gestor->estado_uf)])
                    ->exists();

                if (!$okUF) {
                    return back()->withErrors([
                        'cidade_id' => 'A cidade selecionada não pertence à UF do gestor.',
                    ])->withInput();
                }
            }
        }
    }

    DB::beginTransaction();
    try {
        // Snapshot antes da atualização para log
        $pedido->load(['produtos', 'cidades']);
        $antes = [
            'campos' => [
                'data'            => $pedido->data,
                'cliente_id'      => $pedido->cliente_id,
                'gestor_id'       => $pedido->gestor_id,
                'distribuidor_id' => $pedido->distribuidor_id,
                'status'          => $pedido->status,
            ],
            'cidades' => $pedido->cidades->pluck('id')->sort()->values()->all(),
            'itens'   => $pedido->produtos->mapWithKeys(
                fn($p) => [$p->id => ['q' => (int)$p->pivot->quantidade, 'd_item' => (float)$p->pivot->desconto_item]]
            )->toArray(),
        ];

        $novoStatus = $validated['status'];

        // Se o status for cancelado, verifica se o pedido não está finalizado
        // Se não estiver, atualiza o status e registra o log de cancelamento
        // Se o status for diferente de cancelado, atualiza normalmente

        if ($antes['campos']['status'] !== 'cancelado' && $novoStatus === 'cancelado') {

            $pedido->update([
                'data'            => $validated['data'],
                'cliente_id'      => $request->cliente_id,
                'gestor_id'       => $request->gestor_id,
                'distribuidor_id' => $request->distribuidor_id,
                'status'          => 'cancelado',
            ]);

            $request->filled('cidade_id')
                ? $pedido->cidades()->sync([$request->cidade_id])
                : $pedido->cidades()->sync([]);

            $pedido->registrarLog(
                'Pedido cancelado',
                'Pedido cancelado (sem movimentação de estoque).',
                ['antes' => $antes]
            );

            DB::commit();
            return redirect()
                ->route('admin.pedidos.show', $pedido)
                ->with('success', 'Pedido cancelado.');
        }

        // Atualiza os dados principais do pedido
        // Sincroniza a cidade se fornecida
        $pedido->update([
            'data'            => $validated['data'],
            'cliente_id'      => $request->cliente_id,
            'gestor_id'       => $request->gestor_id,
            'distribuidor_id' => $request->distribuidor_id,
            'status'          => $novoStatus,
        ]);

        $request->filled('cidade_id')
            ? $pedido->cidades()->sync([$request->cidade_id])
            : $pedido->cidades()->sync([]);

        // Processa os itens do pedido
        // Agrupa os itens por ID para somar quantidades e aplicar descontos

        $depoisItens = collect($validated['produtos'])
            ->groupBy(fn ($it) => (int)$it['id'])
            ->map(fn ($group) => [
                'qtd'    => (int) $group->sum('quantidade'),
                'd_item' => (float) ($group->first()['desconto'] ?? 0.0),
            ])
            ->toArray();
        
        $antesQtd   = array_map(fn($arr) => (int)$arr['q'], $antes['itens']);
        $antesIds   = array_keys($antesQtd);
        $depoisIds  = array_keys($depoisItens);

        $envolvidos  = array_values(array_unique(array_merge($antesIds, $depoisIds)));
        $produtosLock = Produto::whereIn('id', $envolvidos)->lockForUpdate()->get()->keyBy('id');

        // Valida estoque para aumentos de quantidade
        // Para cada produto envolvido, verifica se a quantidade foi aumentada
        // Se foi aumentada, verifica se há estoque suficiente
        // Se não houver estoque suficiente, lança uma exceção

        foreach ($envolvidos as $pid) {
            $qAntes  = (int)($antesQtd[$pid]           ?? 0);
            $qDepois = (int)($depoisItens[$pid]['qtd'] ?? 0);
            $delta   = $qDepois - $qAntes;
            if ($delta > 0) {
                $p = $produtosLock[$pid] ?? null;
                if (!$p) throw new \RuntimeException("Produto {$pid} não encontrado.");
                $disp = (int) $p->quantidade_estoque;
                if ($disp < $delta) {
                    throw new \RuntimeException("Estoque insuficiente para o produto {$p->nome}. Disponível: {$disp}, necessário: {$delta}");
                }
            }
        }

        // recalcula totais
        $pesoTotal = 0; $totalCaixas = 0; $valorBruto = 0; $valorFinal = 0;
        $sync = [];

        // Para cada item depois da atualização, calcula os totais e prepara os dados para sincronização

        foreach ($depoisItens as $pid => $info) {
            $qtd      = (int) $info['qtd'];
            $descItem = (float) $info['d_item'];
            $produto  = $produtosLock[$pid] ?? Produto::findOrFail($pid);

            $precoUnit = (float) $produto->preco;
            $subBruto  = $precoUnit * $qtd;
            $precoDesc = $precoUnit * (1 - ($descItem / 100));
            $subDesc   = $precoDesc * $qtd;

            $pesoItem  = (float) ($produto->peso ?? 0) * $qtd;
            $caixas    = (int) ceil($qtd / max(1, (int)$produto->quantidade_por_caixa));

            $sync[$pid] = [
                'quantidade'           => $qtd,
                'preco_unitario'       => $precoUnit,
                'desconto_item'        => $descItem,
                'desconto_aplicado'    => $descItem,
                'subtotal'             => $subDesc,
                'peso_total_produto'   => $pesoItem,
                'caixas'               => $caixas,
            ];

            $pesoTotal   += $pesoItem;
            $totalCaixas += $caixas;
            $valorBruto  += $subBruto;
            $valorFinal  += $subDesc;
        }

        // Sincroniza os produtos do pedido com os novos dados
        $pedido->produtos()->sync($sync);

        // Atualiza os totais do pedido
        $pedido->update([
            'peso_total'   => $pesoTotal,
            'total_caixas' => $totalCaixas,
            'valor_bruto'  => $valorBruto,
            'valor_total'  => $valorFinal,
        ]);

        // Snapshot depois da atualização para log
        $pedido->load(['produtos','cidades']);
        $depois = [
            'campos' => [
                'data'            => $pedido->data,
                'cliente_id'      => $pedido->cliente_id,
                'gestor_id'       => $pedido->gestor_id,
                'distribuidor_id' => $pedido->distribuidor_id,
                'status'          => $pedido->status,
            ],
            'cidades' => $pedido->cidades->pluck('id')->sort()->values()->all(),
            'itens' => $pedido->produtos->mapWithKeys(fn($p) => [
                $p->id => [
                    'q'      => (int)$p->pivot->quantidade,
                    'd_item' => (float)$p->pivot->desconto_item
                ]
            ])->toArray(),
        ];

        // ===== DIFERENÇAS (campos, cidade, itens) =====

        // 1) Campos simples
        $labelCampo = [
            'data'            => 'Data',
            'cliente_id'      => 'Cliente',
            'gestor_id'       => 'Gestor',
            'distribuidor_id' => 'Distribuidor',
            'status'          => 'Status',
        ];

        $mensagens = [];

        // Data (formata dd/mm/yyyy)
        if (($antes['campos']['data'] ?? null) !== ($depois['campos']['data'] ?? null)) {
            $mensagens[] = sprintf(
                '%s alterada: %s → %s',
                $labelCampo['data'],
                \Carbon\Carbon::parse($antes['campos']['data'])->format('d/m/Y'),
                \Carbon\Carbon::parse($depois['campos']['data'])->format('d/m/Y')
            );
        }

        // Demais campos (resolve nomes em vez de IDs)
        foreach (['cliente_id','gestor_id','distribuidor_id','status'] as $k) {
            $vAntes  = $antes['campos'][$k]  ?? null;
            $vDepois = $depois['campos'][$k] ?? null;
            if ((string)$vAntes !== (string)$vDepois) {
                $nomeAntes = $vAntes;
                $nomeDepois = $vDepois;

                switch ($k) {
                    case 'cliente_id':
                        $nomeAntes  = $vAntes  ? \App\Models\Cliente::find($vAntes)?->razao_social : '-';
                        $nomeDepois = $vDepois ? \App\Models\Cliente::find($vDepois)?->razao_social : '-';
                        break;
                    case 'gestor_id':
                        $nomeAntes  = $vAntes  ? \App\Models\Gestor::find($vAntes)?->razao_social : '-';
                        $nomeDepois = $vDepois ? \App\Models\Gestor::find($vDepois)?->razao_social : '-';
                        break;
                    case 'distribuidor_id':
                        $nomeAntes  = $vAntes  ? \App\Models\Distribuidor::find($vAntes)?->razao_social : '-';
                        $nomeDepois = $vDepois ? \App\Models\Distribuidor::find($vDepois)?->razao_social : '-';
                        break;
                    case 'status':
                        // traduz status para label bonitinho
                        $labels = [
                            'em_andamento' => 'Em andamento',
                            'finalizado'   => 'Finalizado',
                            'cancelado'    => 'Cancelado',
                        ];
                        $nomeAntes  = $labels[$vAntes]  ?? $vAntes  ?? '-';
                        $nomeDepois = $labels[$vDepois] ?? $vDepois ?? '-';
                        break;
                }

                $mensagens[] = sprintf('%s alterado: %s → %s', $labelCampo[$k], ($nomeAntes ?? '-'), ($nomeDepois ?? '-'));
            }
        }


        // 2) Cidade (você sincroniza no máximo 1 cidade)
        $antesCidadeIds  = $antes['cidades'] ?? [];
        $depoisCidadeIds = $depois['cidades'] ?? [];

        $antesCidade  = count($antesCidadeIds)  ? $antesCidadeIds[0]  : null;
        $depoisCidade = count($depoisCidadeIds) ? $depoisCidadeIds[0] : null;

        if ((string)$antesCidade !== (string)$depoisCidade) {
            // Opcional: resolver nomes para melhorar a leitura
            $nomeAntes  = $antesCidade  ? \App\Models\City::find($antesCidade)?->name   : '-';
            $nomeDepois = $depoisCidade ? \App\Models\City::find($depoisCidade)?->name : '-';
            $mensagens[] = "Cidade alterada: {$nomeAntes} → {$nomeDepois}";
        }

        // 3) Itens (adicionados, removidos, atualizados qtd/desc)
        // $antes['itens'] = [id => ['q'=>int,'d_item'=>float]]
        // $depois['itens'] = [id => ['q'=>int,'d_item'=>float]]

        $antesItens  = $antes['itens']  ?? [];
        $depoisItens = $depois['itens'] ?? [];

        $idsAntes  = array_map('intval', array_keys($antesItens));
        $idsDepois = array_map('intval', array_keys($depoisItens));

        $adicionados = array_values(array_diff($idsDepois, $idsAntes));
        $removidos   = array_values(array_diff($idsAntes,  $idsDepois));
        $comuns      = array_values(array_intersect($idsAntes, $idsDepois));

        $nomeProduto = function ($id) {
            return \App\Models\Produto::find($id)?->nome ?? "Produto #{$id}";
        };

        // Adicionados
        foreach ($adicionados as $pid) {
            $q  = (int)($depoisItens[$pid]['q']    ?? 0);
            $dp = (float)($depoisItens[$pid]['d_item'] ?? 0);
            $mensagens[] = sprintf('Item adicionado: %s (qtd %d, desc %.2f%%)', $nomeProduto($pid), $q, $dp);
        }

        // Removidos
        foreach ($removidos as $pid) {
            $q  = (int)($antesItens[$pid]['q']    ?? 0);
            $da = (float)($antesItens[$pid]['d_item'] ?? 0);
            $mensagens[] = sprintf('Item removido: %s (qtd %d, desc %.2f%%)', $nomeProduto($pid), $q, $da);
        }

        // Atualizados (quantidade e/ou desconto)
        foreach ($comuns as $pid) {
            $qA = (int)($antesItens[$pid]['q']    ?? 0);
            $qD = (int)($depoisItens[$pid]['q']   ?? 0);
            $dA = (float)($antesItens[$pid]['d_item']  ?? 0);
            $dD = (float)($depoisItens[$pid]['d_item'] ?? 0);

            $mudouQtd = ($qA !== $qD);
            $mudouDes = (abs($dA - $dD) > 0.00001);

            if ($mudouQtd || $mudouDes) {
                $partes = [];
                if ($mudouQtd) $partes[] = "qtd {$qA} → {$qD}";
                if ($mudouDes) $partes[] = sprintf('desc %.2f%% → %.2f%%', $dA, $dD);
                $mensagens[] = sprintf('Item alterado: %s (%s)', $nomeProduto($pid), implode(', ', $partes));
            }
        }

        // ===== Ação do log com base no status final =====
        $acaoLog = match ($pedido->status) {
            'finalizado' => 'Pedido finalizado',
            'cancelado'  => 'Pedido cancelado',
            default      => 'Pedido atualizado',
        };

        // ===== Meta estruturado para auditoria (JSON) =====
        $meta = [
            'antes'  => $antes,
            'depois' => $depois,
            'diff'   => [
                'campos' => [
                    'data'            => [$antes['campos']['data'] ?? null, $depois['campos']['data'] ?? null],
                    'cliente_id'      => [$antes['campos']['cliente_id'] ?? null, $depois['campos']['cliente_id'] ?? null],
                    'gestor_id'       => [$antes['campos']['gestor_id'] ?? null, $depois['campos']['gestor_id'] ?? null],
                    'distribuidor_id' => [$antes['campos']['distribuidor_id'] ?? null, $depois['campos']['distribuidor_id'] ?? null],
                    'status'          => [$antes['campos']['status'] ?? null, $depois['campos']['status'] ?? null],
                ],
                'cidade' => [$antesCidade, $depoisCidade],
                'itens'  => [
                    'adicionados' => array_values($adicionados),
                    'removidos'   => array_values($removidos),
                    'atualizados' => array_values(array_filter($comuns, function ($pid) use ($antesItens, $depoisItens) {
                        $qA = (int)($antesItens[$pid]['q']    ?? 0);
                        $qD = (int)($depoisItens[$pid]['q']   ?? 0);
                        $dA = (float)($antesItens[$pid]['d_item']  ?? 0);
                        $dD = (float)($depoisItens[$pid]['d_item'] ?? 0);
                        return $qA !== $qD || abs($dA - $dD) > 0.00001;
                    })),
                ],
            ],
        ];

        // ===== Registrar log (linhas legíveis em $mensagens e JSON em $meta) =====
        $pedido->registrarLog(
            $acaoLog,
            $mensagens ? implode(' | ', $mensagens) : 'Atualização sem mudanças relevantes',
            $meta
        );

        DB::commit();
        return redirect()->route('admin.pedidos.show', $pedido)->with('success', 'Pedido atualizado com sucesso!');
    } catch (\Throwable $e) {
        DB::rollBack();
        return back()->withErrors(['error' => 'Erro ao atualizar: ' . $e->getMessage()])->withInput();
    }
}

}
