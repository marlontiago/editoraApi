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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class PedidoController extends Controller
{
    public function index()
    {
        $produtosComEstoqueBaixo = Produto::where('quantidade_estoque', '<=', 100)->get();

        $estoqueParaPedidosEmPotencial = DB::table('pedido_produto as pp')
            ->join('pedidos as pe', 'pe.id', '=', 'pp.pedido_id')
            ->join('produtos as pr', 'pr.id', '=', 'pp.produto_id')
            ->where('pe.status', 'em_andamento')
            ->groupBy('pp.produto_id', 'pr.titulo', 'pr.quantidade_estoque')
            ->havingRaw('SUM(pp.quantidade) > pr.quantidade_estoque')
            ->get([
                'pp.produto_id',
                DB::raw('pr.titulo as titulo'),
                DB::raw('SUM(pp.quantidade) as qtd_em_pedidos'),
                'pr.quantidade_estoque',
                DB::raw('SUM(pp.quantidade) - pr.quantidade_estoque AS excedente'),
            ]);

        $pedidos = Pedido::with(['cidades', 'gestor', 'distribuidor.user', 'cliente'])->latest()->paginate(10);

        return view('admin.pedidos.index', compact('pedidos', 'produtosComEstoqueBaixo','estoqueParaPedidosEmPotencial'));
    }

    public function create()
    {
        $gestores       = Gestor::with('user')->orderBy('razao_social')->get();
        $distribuidores = Distribuidor::with('user')->orderBy('razao_social')->get();

        // Seleciona apenas colunas existentes
        $produtos = Produto::select('id', 'titulo', 'preco', 'imagem')
            ->orderBy('titulo')
            ->get()
            ->map(fn ($p) => [
                'id'     => $p->id,
                'titulo' => $p->titulo,
                'preco'  => (float) ($p->preco ?? 0),
                'imagem' => $p->imagem_url, // usa o accessor
            ])
            ->values();

        $cidades   = City::orderBy('name')->get();
        $clientes  = Cliente::orderBy('razao_social')->get();
        $cidadesUF = $cidades->pluck('state')->unique()->sort()->values();

        return view('admin.pedidos.create', compact(
            'produtos',
            'cidades',
            'gestores',
            'distribuidores',
            'clientes',
            'cidadesUF'
        ));
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

        // Verificações de cidade
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
            } else {
                $ocupada = DB::table('city_distribuidor')
                    ->where('city_id', $request->cidade_id)
                    ->exists();

                if ($ocupada) {
                    $distName = DB::table('city_distribuidor')
                        ->join('distribuidores','distribuidores.id','=','city_distribuidor.distribuidor_id')
                        ->where('city_distribuidor.city_id', $request->cidade_id)
                        ->value('distribuidores.razao_social');

                    return back()->withErrors([
                        'cidade_id' => $distName
                            ? "Esta cidade é atendida pelo distribuidor {$distName}. Selecione esse distribuidor para vender nesta cidade, ou escolha outra cidade/UF."
                            : 'Esta cidade é atendida por um distribuidor. Selecione o distribuidor correspondente ou escolha outra cidade.',
                    ])->withInput();
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
                $produto  = Produto::findOrFail($produtoData['id']);
                $qtd      = (int) $produtoData['quantidade'];
                $descItem = (float) ($produtoData['desconto'] ?? 0.0);

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
            return redirect()->route('admin.pedidos.index')->with('success', 'Pedido criado com sucesso!');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Erro ao criar pedido: ' . $e->getMessage()])->withInput();
        }
    }

    public function show(Pedido $pedido)
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

        $notaAtual = NotaFiscal::where('pedido_id', $pedido->id)
            ->latest('id')
            ->first();

        $notaEmitida = NotaFiscal::where('pedido_id', $pedido->id)
            ->where('status', 'emitida')
            ->latest('id')
            ->first();

        $temNotaFaturada = NotaFiscal::where('pedido_id', $pedido->id)
            ->where('status', 'faturada')
            ->exists();

        return view('admin.pedidos.show', compact('pedido', 'notaAtual', 'notaEmitida','temNotaFaturada'));
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
        if ($pedido->status === 'finalizado') {
            return redirect()
                ->route('admin.pedidos.show', $pedido)
                ->with(['error' => 'Pedido finalizado não pode mais ser editado.']);
        }

        $pedido->load(['cidades', 'produtos', 'gestor', 'distribuidor.user', 'cliente']);

        $gestores       = Gestor::with('user')->orderBy('razao_social')->get();
        $distribuidores = Distribuidor::with('user')->orderBy('razao_social')->get();
        $produtos       = Produto::orderBy('titulo')->get();
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

        return view('admin.pedidos.edit', compact(
            'pedido','gestores','distribuidores','produtos','cidades','clientes','itensAtuais','cidadesUF',
        ));
    }

    public function update(Pedido $pedido, Request $request)
    {
        if ($pedido->status === 'finalizado') {
            return back()->with('error', 'Não é mais possível editar: este pedido já foi finalizado.');
        }

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

        if (filled($pedido->distribuidor_id)) {
            if (blank($request->cidade_id)) {
                return back()->withErrors(['cidade_id' => 'Selecione a cidade da venda.'])->withInput();
            }
            $pertence = DB::table('city_distribuidor')
                ->where('city_id', $request->cidade_id)
                ->where('distribuidor_id', $pedido->distribuidor_id)
                ->exists();
            if (!$pertence) {
                return back()->withErrors([
                    'cidade_id' => 'A cidade selecionada não pertence ao distribuidor deste pedido.',
                ])->withInput();
            }
        } else {
            if (filled($request->cidade_id)) {
                $ocupada = DB::table('city_distribuidor')
                    ->where('city_id', $request->cidade_id)
                    ->exists();

                if ($ocupada) {
                    $distName = DB::table('city_distribuidor')
                        ->join('distribuidores','distribuidores.id','=','city_distribuidor.distribuidor_id')
                        ->where('city_distribuidor.city_id', $request->cidade_id)
                        ->value('distribuidores.razao_social');

                    return back()->withErrors([
                        'cidade_id' => $distName
                            ? "Esta cidade é atendida pelo distribuidor {$distName}. Selecione esse distribuidor ou escolha outra cidade."
                            : 'Esta cidade é atendida por um distribuidor. Selecione o distribuidor correspondente ou escolha outra cidade.',
                    ])->withInput();
                }
            }
        }

        DB::beginTransaction();
        try {
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

            if ($antes['campos']['status'] !== 'cancelado' && $novoStatus === 'cancelado') {
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
                return redirect()
                    ->route('admin.pedidos.show', $pedido)
                    ->with('success', 'Pedido cancelado.');
            }

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
                        throw new \RuntimeException("Estoque insuficiente para o produto {$p->titulo}. Disponível: {$disp}, necessário: {$delta}");
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

            $labelCampo = [
                'data'            => 'Data',
                'cliente_id'      => 'Cliente',
                'gestor_id'       => 'Gestor',
                'distribuidor_id' => 'Distribuidor',
                'status'          => 'Status',
            ];

            $mensagens = [];

            $rawAntes  = $depois['campos']['data'] ? $antes['campos']['data'] ?? null : null;
            $rawDepois = $depois['campos']['data'] ?? null;

            $norm = function ($v) { return $v ? \Carbon\Carbon::parse($v)->toDateString() : null; };

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
                return Produto::find($id)?->titulo ?? "Produto #{$id}";
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
            return redirect()->route('admin.pedidos.show', $pedido)->with('success', 'Pedido atualizado com sucesso!');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Erro ao atualizar: ' . $e->getMessage()])->withInput();
        }
    }
}
