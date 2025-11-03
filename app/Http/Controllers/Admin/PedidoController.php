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
use App\Models\NotaFiscal;
use App\Models\Colecao;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

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

        // Produtos (somente campos usados no front)
        $produtos = Produto::select('id','titulo','preco','imagem','colecao_id','peso','quantidade_por_caixa')
            ->orderBy('titulo')
            ->get()
            ->map(fn ($p) => [
                'id'         => $p->id,
                'titulo'     => $p->titulo,
                'preco'      => (float) ($p->preco ?? 0),
                'imagem'     => $p->imagem_url,
                'colecao_id' => $p->colecao_id,
                'peso'       => (float) ($p->peso ?? 0),
                'por_caixa'  => (int)   ($p->quantidade_por_caixa ?? 1),
            ])
            ->values();

        // Coleções com seus produtos (para preview na UI)
        $colecoes = Colecao::with(['produtos:id,titulo,preco,imagem,colecao_id,peso,quantidade_por_caixa'])
            ->orderBy('nome')
            ->get()
            ->map(fn ($c) => [
                'id'       => $c->id,
                'nome'     => $c->nome,
                'produtos' => $c->produtos->map(fn ($p) => [
                    'id'        => $p->id,
                    'titulo'    => $p->titulo,
                    'preco'     => (float) ($p->preco ?? 0),
                    'imagem'    => $p->imagem_url,
                    'peso'      => (float) ($p->peso ?? 0),
                    'por_caixa' => (int)   ($p->quantidade_por_caixa ?? 1),
                ])->values(),
            ])
            ->values();

        $cidades   = City::orderBy('name')->get();
        $clientes  = Cliente::orderBy('razao_social')->get();
        $cidadesUF = $cidades->pluck('state')->unique()->sort()->values();
        $cfopLabels = config('cfop.labels', []);

        return view('admin.pedidos.create', compact(
            'produtos','gestores','distribuidores','clientes','cidades','cidadesUF','cfopLabels','colecoes'
        ));
    }

    public function store(Request $request)
{
    // ===== Regras dinâmicas para cidade =====
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

    // ===== Validação =====
    $rules = [
        'data'            => ['required', 'date', 'after_or_equal:' . Carbon::now('America/Sao_Paulo')->toDateString()],
        'cliente_id'      => ['required', 'exists:clientes,id'],
        'gestor_id'       => ['nullable', 'exists:gestores,id'],
        'distribuidor_id' => ['nullable', 'exists:distribuidores,id'],
        'cidade_id'       => $cidadeRules,
        'cfop'            => ['nullable', 'regex:/^\d{4}$/'],

        // Aceita “só coleção”: produtos exigidos apenas se NÃO houver colecao_id
        'produtos'               => ['required_without:colecao_id', 'array', 'min:1'],
        'produtos.*.id'          => ['required_with:produtos', 'exists:produtos,id', 'distinct'],
        'produtos.*.quantidade'  => ['required_with:produtos', 'integer', 'min:1'],
        'produtos.*.desconto'    => ['nullable', 'numeric', 'min:0', 'max:100'],

        // Coleção com nome de tabela dinâmico
        'colecao_id'   => ['nullable', Rule::exists((new Colecao)->getTable(), 'id')],
        'colecao_qtd'  => ['nullable','integer','min:1'],
        'colecao_desc' => ['nullable','numeric','min:0','max:100'],

        'observacoes'  => ['nullable','string','max:2000'],
    ];

    $messages = [
        'data.after_or_equal'         => 'A data do pedido não pode ser anterior à data atual.',
        'cliente_id.required'         => 'Selecione um cliente.',
        'cidade_id.required'          => 'Selecione a cidade da venda (ao escolher um distribuidor).',
        'cidade_id.exists'            => 'A cidade selecionada não pertence ao distribuidor escolhido.',
        'cfop.regex'                  => 'CFOP deve conter exatamente 4 dígitos.',
        'produtos.required_without'   => 'Selecione ao menos um produto ou informe uma coleção.',
        'produtos.*.id.required_with' => 'Informe o produto em cada linha adicionada.',
        'produtos.*.quantidade.min'   => 'Quantidade mínima por item é 1.',
    ];

    $validated = $request->validate($rules, $messages);

    // ===== Monta a fonte de itens =====
    // 1) Se vieram produtos no payload, usa-os.
    // 2) Senão, se vier colecao_id, gera os itens a partir da coleção (com qtd/desc padrão).
    $itens = collect($validated['produtos'] ?? []);

    if ($itens->isEmpty() && !empty($validated['colecao_id'])) {
        $qtdPadrao  = max(1, (int) ($validated['colecao_qtd']  ?? 1));
        $descPadrao = max(0.0, min(100.0, (float) ($validated['colecao_desc'] ?? 0)));

        $colecao = Colecao::with('produtos:id,preco,peso,quantidade_por_caixa')->find($validated['colecao_id']);
        if ($colecao && $colecao->produtos->count()) {
            $itens = $colecao->produtos->map(fn($p) => [
                'id'         => $p->id,
                'quantidade' => $qtdPadrao,
                'desconto'   => $descPadrao,
            ]);
        }
    }

    // Se ainda estiver vazio, é porque não há produtos nem coleção válida
    if ($itens->isEmpty()) {
        return back()
            ->withErrors(['produtos' => 'Nenhum item encontrado. Selecione ao menos um produto ou uma coleção com itens.'])
            ->withInput();
    }

    try {
        DB::beginTransaction();

        $pedido = Pedido::create([
            'cliente_id'      => $validated['cliente_id'],
            'gestor_id'       => $validated['gestor_id']       ?? null,
            'distribuidor_id' => $validated['distribuidor_id'] ?? null,
            'data'            => $validated['data'],
            'status'          => 'em_andamento',
            'observacoes'     => $validated['observacoes']     ?? null,
            'cfop'            => $validated['cfop']            ?? null,
        ]);

        filled($validated['cidade_id'] ?? null)
            ? $pedido->cidades()->sync([$validated['cidade_id']])
            : $pedido->cidades()->sync([]);

        // Consolida itens iguais (caso venham repetidos)
        $consolidados = collect($itens)
            ->groupBy('id')
            ->map(fn ($g) => [
                'id'         => (int) $g->first()['id'],
                'quantidade' => (int) $g->sum('quantidade'),
                'desconto'   => isset($g->first()['desconto']) ? (float) $g->first()['desconto'] : 0.0,
            ])
            ->values();

        $pesoTotal = 0;
        $totalCaixas = 0;
        $valorBruto = 0;
        $valorFinal = 0;

        foreach ($consolidados as $produtoData) {
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

        $pedido->registrarLog('Pedido criado', 'Pedido criado (coleção/produtos).');

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

        $notaAtual = NotaFiscal::where('pedido_id', $pedido->id)->latest('id')->first();
        $notaEmitida = NotaFiscal::where('pedido_id', $pedido->id)->where('status', 'emitida')->latest('id')->first();
        $temNotaFaturada = NotaFiscal::where('pedido_id', $pedido->id)->where('status', 'faturada')->exists();

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

        $cfopLabels = config('cfop.labels', []);

        return view('admin.pedidos.edit', compact(
            'pedido','gestores','distribuidores','produtos','cidades','clientes','itensAtuais','cidadesUF','cfopLabels',
        ));
    }

    public function update(Pedido $pedido, Request $request)
    {
        if ($pedido->status === 'finalizado') {
            return back()->with('error', 'Não é mais possível editar: este pedido já foi finalizado.');
        }

        // Sanitiza produtos
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
            'data'                   => ['required', 'date', 'after_or_equal:' . Carbon::now('America/Sao_Paulo')->toDateString()],
            'cliente_id'             => ['required', 'exists:clientes,id'],
            'gestor_id'              => ['nullable','exists:gestores,id'],
            'distribuidor_id'        => ['nullable','exists:distribuidores,id'],
            'cidade_id'              => ['nullable','integer'],
            'status'                 => ['required', 'in:em_andamento,pre_aprovado,finalizado,cancelado'],
            'cfop'                   => ['nullable', 'regex:/^\d{4}$/'],
            'produtos'               => ['required', 'array', 'min:1'],
            'produtos.*.id'          => ['required', 'exists:produtos,id', 'distinct'],
            'produtos.*.quantidade'  => ['required', 'integer', 'min:1'],
            'produtos.*.desconto'    => ['nullable', 'numeric', 'min:0', 'max:100'],
            'observacoes'            => ['nullable','string','max:2000'],
        ];

        $messages = [
            'cidade_id.required' => 'Selecione a cidade da venda.',
            'cfop.regex'         => 'CFOP deve conter exatamente 4 dígitos.',
        ];

        $validated = $request->validate($rules, $messages);

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
                    'cfop'            => $pedido->cfop,
                ],
                'cidades' => $pedido->cidades->pluck('id')->sort()->values()->all(),
                'itens'   => $pedido->produtos->mapWithKeys(
                    fn($p) => [$p->id => ['q' => (int)$p->pivot->quantidade, 'd_item' => (float)$p->pivot->desconto_item]]
                )->toArray(),
            ];

            $novoStatus = $validated['status'];

            // Cancelamento não mexe em estoque, só registra
            if ($antes['campos']['status'] !== 'cancelado' && $novoStatus === 'cancelado') {
                $pedido->update([
                    'data'            => $validated['data'],
                    'cliente_id'      => $request->cliente_id,
                    'gestor_id'       => $pedido->gestor_id,
                    'distribuidor_id' => $pedido->distribuidor_id,
                    'status'          => 'cancelado',
                    'observacoes'     => $request->observacoes ?? null,
                    'cfop'            => $request->cfop,
                ]);

                $request->filled('cidade_id')
                    ? $pedido->cidades()->sync([$request->cidade_id])
                    : $pedido->cidades()->sync([]);

                $pedido->registrarLog('Pedido cancelado', 'Pedido cancelado (sem movimentação de estoque).', ['antes' => $antes]);

                DB::commit();
                return redirect()->route('admin.pedidos.show', $pedido)->with('success', 'Pedido cancelado.');
            }

            // Atualiza campos principais
            $pedido->update([
                'data'            => $validated['data'],
                'cliente_id'      => $request->cliente_id,
                'gestor_id'       => $pedido->gestor_id,
                'distribuidor_id' => $pedido->distribuidor_id,
                'status'          => $novoStatus,
                'observacoes'     => $request->observacoes ?? null,
                'cfop'            => $request->cfop,
            ]);

            $request->filled('cidade_id')
                ? $pedido->cidades()->sync([$request->cidade_id])
                : $pedido->cidades()->sync([]);

            // Calcula itens novos e valida estoque incremental
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

            // Recalcula agregados + sync pivot
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

            // Logs/diffs omitidos por brevidade...

            DB::commit();
            return redirect()->route('admin.pedidos.show', $pedido)->with('success', 'Pedido atualizado com sucesso!');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Erro ao atualizar: ' . $e->getMessage()])->withInput();
        }
    }

    public function emitirNota(Request $request, Pedido $pedido)
    {
        if (in_array($pedido->status, ['finalizado', 'cancelado'])) {
            return back()->with('error', 'Não é possível pré-visualizar nota para um pedido finalizado ou cancelado.');
        }

        $statusAntes = $pedido->status;

        if ($pedido->status !== 'pre_aprovado') {
            $pedido->update(['status' => 'pre_aprovado']);

            $pedido->registrarLog(
                'Pré-visualização de nota',
                'Status alterado para Pré-aprovado.',
                ['antes' => $statusAntes, 'depois' => 'pre_aprovado']
            );
        }

        $notaExistente = NotaFiscal::where('pedido_id', $pedido->id)
            ->where('status', 'emitida')
            ->first();

        if (!$notaExistente) {
            $nota = NotaFiscal::create([
                'pedido_id'  => $pedido->id,
                'status'     => 'emitida',
                'emitida_em' => now(),
            ]);

            $pedido->registrarLog(
                'Nota de pré-visualização criada',
                'Criada nota de rascunho para permitir faturamento.',
                ['nota_id' => $nota->id]
            );
        }

        return redirect()
            ->route('admin.pedidos.show', $pedido)
            ->with('success', 'Pedido marcado como Pré-aprovado e nota de pré-visualização criada.');
    }
}
