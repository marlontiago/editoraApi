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
use App\Models\NotaFiscal;

class PedidoController extends Controller
{
    /** Helper para resposta de erro JSON padrão */
    protected function jsonError(string $message, array $errors = [], int $status = 422)
    {
        return response()->json([
            'message' => $message,
            'errors'  => $errors ?: null,
        ], $status);
    }

    public function index(Request $request)
    {
        $query = Pedido::with(['cidades', 'gestor', 'distribuidor.user', 'cliente'])
            ->latest();

        // filtros simples opcionais (ex.: ?status=finalizado)
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->wantsJson()) {
            $perPage = max(1, min((int) $request->input('per_page', 15), 100));
            return response()->json($query->paginate($perPage));
        }

        // --- fluxo original (Blade) ---
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

        $pedidos = $query->get();

        return view('admin.pedidos.index', compact('pedidos', 'produtosComEstoqueBaixo', 'estoqueParaPedidosEmPotencial'));
    }

    public function create(Request $request)
    {
        $gestores       = Gestor::with('user')->orderBy('razao_social')->get();
        $distribuidores = Distribuidor::with('user')->orderBy('razao_social')->get();
        $produtos       = Produto::orderBy('nome')->get();
        $cidades        = City::orderBy('name')->get();
        $clientes       = Cliente::orderBy('razao_social')->get();
        $cidadesUF      = $cidades->pluck('state')->unique()->sort()->values();

        if ($request->wantsJson()) {
            // Útil para o front montar selects (metadata do formulário)
            return response()->json([
                'gestores'       => $gestores,
                'distribuidores' => $distribuidores,
                'produtos'       => $produtos,
                'cidades'        => $cidades,
                'clientes'       => $clientes,
                'ufs'            => $cidadesUF,
            ]);
        }

        return view('admin.pedidos.create', compact('produtos', 'cidades', 'gestores', 'distribuidores', 'clientes', 'cidadesUF'));
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
            'observacoes'            => ['nullable','string','max:2000'],
        ];

        $messages = [
            'data.after_or_equal' => 'A data do pedido não pode ser anterior à data atual.',
            'cliente_id.required' => 'Selecione um cliente.',
            'cidade_id.required'  => 'Selecione a cidade da venda (ao escolher um distribuidor).',
            'cidade_id.exists'    => 'A cidade selecionada não pertence ao distribuidor escolhido.',
        ];

        $validated = $request->validate($rules, $messages);

        // Regras condicionais complementares
        if (filled($request->gestor_id) || filled($request->distribuidor_id)) {
            if (blank($request->cidade_id)) {
                if ($request->wantsJson()) {
                    return $this->jsonError('Validação falhou.', ['cidade_id' => ['Selecione a cidade da venda.']]);
                }
                return back()->withErrors(['cidade_id' => 'Selecione a cidade da venda.'])->withInput();
            }
        }

        if (filled($request->cidade_id)) {
            if (filled($request->distribuidor_id)) {
                $pertence = DB::table('city_distribuidor')
                    ->where('city_id', $request->cidade_id)
                    ->where('distribuidor_id', $request->distribuidor_id)
                    ->exists();

                if (!$pertence) {
                    if ($request->wantsJson()) {
                        return $this->jsonError('Validação falhou.', ['cidade_id' => ['A cidade selecionada não pertence ao distribuidor escolhido.']]);
                    }
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
                        if ($request->wantsJson()) {
                            return $this->jsonError('Validação falhou.', ['cidade_id' => ['A cidade selecionada não pertence à UF do gestor.']]);
                        }
                        return back()->withErrors([
                            'cidade_id' => 'A cidade selecionada não pertence à UF do gestor.',
                        ])->withInput();
                    }
                }
            }
        }

        try {
            DB::beginTransaction();

            $pedido = Pedido::create([
                'cliente_id'      => $request->cliente_id,
                'gestor_id'       => $request->gestor_id,
                'distribuidor_id' => $request->distribuidor_id,
                'data'            => $request->data,
                'status'          => 'em_andamento',
                'observacoes'     => $request->observacoes ?? null,
            ]);

            if ($request->filled('cidade_id')) {
                $pedido->cidades()->sync([$request->cidade_id]);
            } else {
                $pedido->cidades()->sync([]);
            }

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

            foreach ($itens as $produtoData) {
                $produto  = Produto::whereKey($produtoData['id'])->lockForUpdate()->firstOrFail();
                $qtd      = (int) $produtoData['quantidade'];
                $descItem = (float) ($produtoData['desconto'] ?? 0.0);

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

            $pedido->update([
                'peso_total'   => $pesoTotal,
                'total_caixas' => $totalCaixas,
                'valor_bruto'  => $valorBruto,
                'valor_total'  => $valorFinal,
            ]);

            $pedido->registrarLog('Pedido criado', 'Pedido criado.');

            DB::commit();

            if ($request->wantsJson()) {
                return response()->json($pedido->load(['cidades','gestor','distribuidor.user','cliente','produtos']), 201);
            }

            return redirect()->route('admin.pedidos.index')->with('success', 'Pedido criado com sucesso!');
        } catch (\Throwable $e) {
            DB::rollBack();
            if ($request->wantsJson()) {
                return $this->jsonError('Erro ao criar pedido: '.$e->getMessage(), [], 400);
            }
            return back()->withErrors(['error' => 'Erro ao criar pedido: ' . $e->getMessage()])->withInput();
        }
    }

    public function show(Request $request, Pedido $pedido)
    {
        $pedido->load([
            'produtos' => function ($q) {
                $q->withPivot([
                    'quantidade',
                    'preco_unitario',
                    'desconto_aplicado',
                    'subtotal',
                    'peso_total_produto',
                    'caixas',
                ]);
            },
            'cliente',
            'gestor',
            'distribuidor.user',
            'cidades',
            'logs.user',
        ]);

        // Nota mais recente (qualquer status) e nota emitida ativa
        $notaAtual = NotaFiscal::where('pedido_id', $pedido->id)->latest('id')->first();
        $notaEmitida = NotaFiscal::where('pedido_id', $pedido->id)->where('status', 'emitida')->latest('id')->first();
        $temNotaFaturada = NotaFiscal::where('pedido_id', $pedido->id)->where('status', 'faturada')->exists();

        if ($request->wantsJson()) {
            return response()->json([
                'pedido'           => $pedido,
                'nota_atual'       => $notaAtual,
                'nota_emitida'     => $notaEmitida,
                'tem_nota_faturada'=> $temNotaFaturada,
            ]);
        }

        return view('admin.pedidos.show', compact('pedido', 'notaAtual', 'notaEmitida','temNotaFaturada'));
    }

    public function exportar(Request $request, Pedido $pedido, string $tipo)
    {
        if ($request->wantsJson()) {
            return $this->jsonError('Exportação em PDF é apenas via painel (HTML).', [], 406);
        }

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

    public function edit(Request $request, Pedido $pedido)
    {
        if ($pedido->status === 'finalizado') {
            if ($request->wantsJson()) {
                return $this->jsonError('Pedido finalizado não pode mais ser editado.', [], 409);
            }
            return redirect()
                ->route('admin.pedidos.show', $pedido)
                ->with(['error' => 'Pedido finalizado não pode mais ser editado.']);
        }

        $pedido->load(['cidades', 'produtos', 'gestor', 'distribuidor.user', 'cliente']);

        $gestores       = Gestor::with('user')->orderBy('razao_social')->get();
        $distribuidores = Distribuidor::with('user')->orderBy('razao_social')->get();
        $produtos       = Produto::orderBy('nome')->get();
        $cidades        = City::orderBy('name')->get();
        $clientes       = Cliente::orderBy('razao_social')->get();
        $cidadesUF      = $cidades->pluck('state')->unique()->sort()->values();

        $itensAtuais = $pedido->produtos->mapWithKeys(fn($p) => [
            $p->id => [
                'quantidade'     => (int) $p->pivot->quantidade,
                'preco_unitario' => (float) $p->pivot->preco_unitario,
                'desconto_item'  => (float) $p->pivot->desconto_item,
            ]
        ])->toArray();

        if ($request->wantsJson()) {
            // Metadata para tela de edição no front
            return response()->json([
                'pedido'         => $pedido,
                'itensAtuais'    => $itensAtuais,
                'gestores'       => $gestores,
                'distribuidores' => $distribuidores,
                'produtos'       => $produtos,
                'cidades'        => $cidades,
                'clientes'       => $clientes,
                'ufs'            => $cidadesUF,
            ]);
        }

        return view('admin.pedidos.edit', compact(
            'pedido','gestores','distribuidores','produtos','cidades','clientes','itensAtuais','cidadesUF',
        ));
    }

    public function update(Pedido $pedido, Request $request)
    {
        if ($pedido->status === 'finalizado') {
            if ($request->wantsJson()) {
                return $this->jsonError('Não é mais possível editar: este pedido já foi finalizado.', [], 409);
            }
            return back()->with('error', 'Não é mais possível editar: este pedido já foi finalizado.');
        }

        // Limpa itens recebidos
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

        // Congela gestor/distribuidor para os valores originais do pedido
        $request->merge([
            'gestor_id'       => $pedido->gestor_id,
            'distribuidor_id' => $pedido->distribuidor_id,
            'produtos'        => $produtosLimpos,
        ]);

        $rules = [
            'data'                   => ['required', 'date', 'after_or_equal:' . \Carbon\Carbon::now('America/Sao_Paulo')->toDateString()],
            'cliente_id'             => ['required', 'exists:clientes,id'],
            'gestor_id'              => ['nullable','exists:gestores,id'],
            'distribuidor_id'        => ['nullable','exists:distribuidores,id'],
            'cidade_id'              => ['nullable','integer'],
            'status'                 => ['required', 'in:em_andamento,finalizado,cancelado'],
            'produtos'               => ['required', 'array', 'min:1'],
            'produtos.*.id'          => ['required', 'exists:produtos,id', 'distinct'],
            'produtos.*.quantidade'  => ['required', 'integer', 'min:1'],
            'produtos.*.desconto'    => ['nullable', 'numeric', 'min:0', 'max:100'],
            'observacoes'            => ['nullable','string','max:2000'],
        ];

        $messages = [
            'cidade_id.required' => 'Selecione a cidade da venda.',
        ];

        $validated = $request->validate($rules, $messages);

        // Regras de cidade conforme cenário "congelado"
        if (filled($pedido->distribuidor_id)) {
            if (blank($request->cidade_id)) {
                if ($request->wantsJson()) {
                    return $this->jsonError('Validação falhou.', ['cidade_id' => ['Selecione a cidade da venda.']]);
                }
                return back()->withErrors(['cidade_id' => 'Selecione a cidade da venda.'])->withInput();
            }
            $pertence = DB::table('city_distribuidor')
                ->where('city_id', $request->cidade_id)
                ->where('distribuidor_id', $pedido->distribuidor_id)
                ->exists();
            if (!$pertence) {
                if ($request->wantsJson()) {
                    return $this->jsonError('Validação falhou.', ['cidade_id' => ['A cidade selecionada não pertence ao distribuidor deste pedido.']]);
                }
                return back()->withErrors([
                    'cidade_id' => 'A cidade selecionada não pertence ao distribuidor deste pedido.',
                ])->withInput();
            }
        } elseif (filled($pedido->gestor_id) && filled($request->cidade_id)) {
            $okUF = City::whereKey($request->cidade_id)
                ->whereRaw('UPPER(state) = ?', [strtoupper(optional($pedido->gestor)->estado_uf)])
                ->exists();
            if (!$okUF) {
                if ($request->wantsJson()) {
                    return $this->jsonError('Validação falhou.', ['cidade_id' => ['A cidade selecionada não pertence à UF do gestor deste pedido.']]);
                }
                return back()->withErrors([
                    'cidade_id' => 'A cidade selecionada não pertence à UF do gestor deste pedido.',
                ])->withInput();
            }
        }

        DB::beginTransaction();
        try {
            // ===== Snapshot ANTES =====
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

            // ===== Cancelamento (log e return) =====
            if (($antes['campos']['status'] !== 'cancelado') && $novoStatus === 'cancelado') {
                $pedido->update([
                    'data'            => $validated['data'],
                    'cliente_id'      => $request->cliente_id,
                    'gestor_id'       => $pedido->gestor_id,
                    'distribuidor_id' => $pedido->distribuidor_id,
                    'status'          => 'cancelado',
                    'observacoes'     => $request->observacoes ?? null,
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

                if ($request->wantsJson()) {
                    return response()->json($pedido->fresh(['cidades','cliente','gestor','distribuidor.user','produtos']));
                }

                return redirect()
                    ->route('admin.pedidos.show', $pedido)
                    ->with('success', 'Pedido cancelado.');
            }

            // ===== Atualização principal =====
            $pedido->update([
                'data'            => $validated['data'],
                'cliente_id'      => $request->cliente_id,
                'gestor_id'       => $pedido->gestor_id,
                'distribuidor_id' => $pedido->distribuidor_id,
                'status'          => $novoStatus,
                'observacoes'     => $request->observacoes ?? null,
            ]);

            $request->filled('cidade_id')
                ? $pedido->cidades()->sync([$request->cidade_id])
                : $pedido->cidades()->sync([]);

            // ===== Itens (valida estoque para aumentos) =====
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

            $envolvidos   = array_values(array_unique(array_merge($antesIds, $depoisIds)));
            $produtosLock = Produto::whereIn('id', $envolvidos)->lockForUpdate()->get()->keyBy('id');

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

            $pesoTotal = 0; $totalCaixas = 0; $valorBruto = 0; $valorFinal = 0;
            $sync = [];

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

            $pedido->produtos()->sync($sync);

            $pedido->update([
                'peso_total'   => $pesoTotal,
                'total_caixas' => $totalCaixas,
                'valor_bruto'  => $valorBruto,
                'valor_total'  => $valorFinal,
            ]);

            // ===== Snapshot DEPOIS =====
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

            // ===== Monta DIFF legível (linha do tempo) =====
            $labelCampo = [
                'data'            => 'Data',
                'cliente_id'      => 'Cliente',
                'gestor_id'       => 'Gestor',
                'distribuidor_id' => 'Distribuidor',
                'status'          => 'Status',
            ];

            $mensagens = [];

            // Data (normaliza YYYY-MM-DD)
            $rawAntes  = $antes['campos']['data']  ?? null;
            $rawDepois = $depois['campos']['data'] ?? null;

            $norm = function ($v) {
                if (!$v) return null;
                return \Carbon\Carbon::parse($v)->toDateString();
            };

            $antesNorm  = $norm($rawAntes);
            $depoisNorm = $norm($rawDepois);

            if ($antesNorm !== $depoisNorm) {
                $mensagens[] = sprintf(
                    '%s alterada: %s → %s',
                    $labelCampo['data'],
                    $rawAntes  ? \Carbon\Carbon::parse($rawAntes)->format('d/m/Y')  : '-',
                    $rawDepois ? \Carbon\Carbon::parse($rawDepois)->format('d/m/Y') : '-'
                );
            }

            foreach (['cliente_id','gestor_id','distribuidor_id','status'] as $k) {
                $vAntes  = $antes['campos'][$k]  ?? null;
                $vDepois = $depois['campos'][$k] ?? null;
                if ((string)$vAntes !== (string)$vDepois) {
                    $nomeAntes = $vAntes;
                    $nomeDepois = $vDepois;

                    switch ($k) {
                        case 'cliente_id':
                            $nomeAntes  = $vAntes  ? Cliente::find($vAntes)?->razao_social : '-';
                            $nomeDepois = $vDepois ? Cliente::find($vDepois)?->razao_social : '-';
                            break;
                        case 'gestor_id':
                            $nomeAntes  = $vAntes  ? Gestor::find($vAntes)?->razao_social : '-';
                            $nomeDepois = $vDepois ? Gestor::find($vDepois)?->razao_social : '-';
                            break;
                        case 'distribuidor_id':
                            $nomeAntes  = $vAntes  ? Distribuidor::find($vAntes)?->razao_social : '-';
                            $nomeDepois = $vDepois ? Distribuidor::find($vDepois)?->razao_social : '-';
                            break;
                        case 'status':
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

            $antesCidadeIds  = $antes['cidades'] ?? [];
            $depoisCidadeIds = $depois['cidades'] ?? [];
            $antesCidade  = count($antesCidadeIds)  ? $antesCidadeIds[0]  : null;
            $depoisCidade = count($depoisCidadeIds) ? $depoisCidadeIds[0] : null;

            if ((string)$antesCidade !== (string)$depoisCidade) {
                $nomeAntes  = $antesCidade  ? City::find($antesCidade)?->name   : '-';
                $nomeDepois = $depoisCidade ? City::find($depoisCidade)?->name : '-';
                $mensagens[] = "Cidade alterada: {$nomeAntes} → {$nomeDepois}";
            }

            $antesItens  = $antes['itens']  ?? [];
            $depoisItensSnap = $depois['itens'] ?? [];

            $idsAntes  = array_map('intval', array_keys($antesItens));
            $idsDepois = array_map('intval', array_keys($depoisItensSnap));

            $adicionados = array_values(array_diff($idsDepois, $idsAntes));
            $removidos   = array_values(array_diff($idsAntes,  $idsDepois));
            $comuns      = array_values(array_intersect($idsAntes, $idsDepois));

            $nomeProduto = function ($id) {
                return Produto::find($id)?->nome ?? "Produto #{$id}";
            };

            foreach ($adicionados as $pid) {
                $q  = (int)($depoisItensSnap[$pid]['q']    ?? 0);
                $dp = (float)($depoisItensSnap[$pid]['d_item'] ?? 0);
                $mensagens[] = sprintf('Item adicionado: %s (qtd %d, desc %.2f%%)', $nomeProduto($pid), $q, $dp);
            }
            foreach ($removidos as $pid) {
                $q  = (int)($antesItens[$pid]['q']    ?? 0);
                $da = (float)($antesItens[$pid]['d_item'] ?? 0);
                $mensagens[] = sprintf('Item removido: %s (qtd %d, desc %.2f%%)', $nomeProduto($pid), $q, $da);
            }
            foreach ($comuns as $pid) {
                $qA = (int)($antesItens[$pid]['q']    ?? 0);
                $qD = (int)($depoisItensSnap[$pid]['q']   ?? 0);
                $dA = (float)($antesItens[$pid]['d_item']  ?? 0);
                $dD = (float)($depoisItensSnap[$pid]['d_item'] ?? 0);

                $mudouQtd = ($qA !== $qD);
                $mudouDes = (abs($dA - $dD) > 0.00001);

                if ($mudouQtd || $mudouDes) {
                    $partes = [];
                    if ($mudouQtd) $partes[] = "qtd {$qA} → {$qD}";
                    if ($mudouDes) $partes[] = sprintf('desc %.2f%% → %.2f%%', $dA, $dD);
                    $mensagens[] = sprintf('Item alterado: %s (%s)', $nomeProduto($pid), implode(', ', $partes));
                }
            }

            $acaoLog = match ($pedido->status) {
                'finalizado' => 'Pedido finalizado',
                'cancelado'  => 'Pedido cancelado',
                default      => 'Pedido atualizado',
            };

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
                        'atualizados' => array_values(array_filter($comuns, function ($pid) use ($antesItens, $depoisItensSnap) {
                            $qA = (int)($antesItens[$pid]['q']    ?? 0);
                            $qD = (int)($depoisItensSnap[$pid]['q']   ?? 0);
                            $dA = (float)($antesItens[$pid]['d_item']  ?? 0);
                            $dD = (float)($depoisItensSnap[$pid]['d_item'] ?? 0);
                            return $qA !== $qD || abs($dA - $dD) > 0.00001;
                        })),
                    ],
                ],
            ];

            $pedido->registrarLog(
                $acaoLog,
                $mensagens ? implode(' | ', $mensagens) : 'Atualização sem mudanças relevantes',
                $meta
            );

            DB::commit();

            if ($request->wantsJson()) {
                return response()->json($pedido->fresh(['cidades','cliente','gestor','distribuidor.user','produtos']));
            }

            return redirect()->route('admin.pedidos.show', $pedido)->with('success', 'Pedido atualizado com sucesso!');
        } catch (\Throwable $e) {
            DB::rollBack();
            if ($request->wantsJson()) {
                return $this->jsonError('Erro ao atualizar: '.$e->getMessage(), [], 400);
            }
            return back()->withErrors(['error' => 'Erro ao atualizar: ' . $e->getMessage()])->withInput();
        }
    }
}
