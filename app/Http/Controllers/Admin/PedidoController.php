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
        $pedidos = Pedido::with(['cidades', 'gestor', 'distribuidor.user', 'cliente'])->latest()->get();
        return view('admin.pedidos.index', compact('pedidos'));
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

        $messages = [
            'data.after_or_equal' => 'A data do pedido não pode ser anterior à data atual.',
            'cliente_id.required' => 'Selecione um cliente.',
            'cidade_id.required'  => 'Selecione a cidade da venda (ao escolher um distribuidor).',
            'cidade_id.exists'    => 'A cidade selecionada não pertence ao distribuidor escolhido.',
        ];

        $validated = $request->validate($rules, $messages);

        // Regras condicionais para cidade
        if (filled($request->gestor_id) || filled($request->distribuidor_id)) {
            if (blank($request->cidade_id)) {
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

        try {
            DB::beginTransaction();

            $pedido = Pedido::create([
                'cliente_id'      => $request->cliente_id,
                'gestor_id'       => $request->gestor_id,
                'distribuidor_id' => $request->distribuidor_id,
                'data'            => $request->data,
                'status'          => 'em_andamento',
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

                $produto->decrement('quantidade_estoque', $qtd);

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
        if ($pedido->status === 'finalizado') {
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

        // normaliza produtos
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

        $request->merge(['produtos' => $produtosLimpos]);

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
            // snapshot antes
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

            // cancelamento → repõe estoque
            if ($antes['campos']['status'] !== 'cancelado' && $novoStatus === 'cancelado') {
                $ids = array_keys($antes['itens']);
                if (!empty($ids)) {
                    $produtosLock = Produto::whereIn('id', $ids)->lockForUpdate()->get()->keyBy('id');
                    $detalhesReposicao = [];
                    foreach ($antes['itens'] as $pid => $info) {
                        $qtd = (int)$info['q'];
                        if ($qtd <= 0) continue;
                        $prod = $produtosLock[$pid] ?? null;
                        if ($prod) {
                            $prod->increment('quantidade_estoque', $qtd);
                            $detalhesReposicao[] = "{$prod->nome} (+{$qtd} un)";
                        }
                    }
                }

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
                    !empty($detalhesReposicao) ? ('Estoque devolvido: ' . implode(', ', $detalhesReposicao)) : 'Pedido cancelado.',
                    ['antes' => $antes]
                );

                DB::commit();
                return redirect()->route('admin.pedidos.show', $pedido)->with('success', 'Pedido cancelado e estoque devolvido.');
            }

            // atualização normal
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

            // agrega duplicatas
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

            // valida disponibilidade
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

            // aplica deltas no estoque
            foreach ($envolvidos as $pid) {
                $qAntes  = (int)($antesQtd[$pid]           ?? 0);
                $qDepois = (int)($depoisItens[$pid]['qtd'] ?? 0);
                $delta   = $qDepois - $qAntes;
                if ($delta === 0) continue;
                if ($delta > 0) {
                    Produto::whereKey($pid)->decrement('quantidade_estoque', $delta);
                } else {
                    Produto::whereKey($pid)->increment('quantidade_estoque', -$delta);
                }
            }

            // recalcula totais
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

            // snapshot depois p/ log
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

            $mensagens = [];
            foreach (['data','cliente_id','gestor_id','distribuidor_id','status'] as $k) {
                $vAntes  = (string)($antes['campos'][$k] ?? '');
                $vDepois = (string)($depois['campos'][$k] ?? '');
                if ($vAntes !== $vDepois) $mensagens[] = ucfirst(str_replace('_',' ',$k))." alterado.";
            }

            $acaoLog = match ($pedido->status) {
                'finalizado' => 'Pedido finalizado',
                'cancelado'  => 'Pedido cancelado',
                default      => 'Pedido atualizado',
            };

            $pedido->registrarLog(
                $acaoLog,
                $mensagens ? implode(' | ', $mensagens) : 'Atualização sem mudanças relevantes',
                ['antes' => $antes, 'depois' => $depois]
            );

            DB::commit();
            return redirect()->route('admin.pedidos.show', $pedido)->with('success', 'Pedido atualizado com sucesso!');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Erro ao atualizar: ' . $e->getMessage()])->withInput();
        }
    }
}
