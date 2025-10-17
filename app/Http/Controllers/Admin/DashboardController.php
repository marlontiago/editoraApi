<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Gestor;
use App\Models\Distribuidor;
use App\Models\Produto;
use App\Models\Pedido;
use App\Models\User;
use App\Models\NotaFiscal;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PedidosDashboardExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use App\Models\City;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index(Request $request)
{
    // ---- paginação/ordenação seguras ----
    $perPage = min(max((int)$request->integer('per_page', 20), 5), 100);
    $orderBy = in_array($request->get('order_by'), ['id','data','created_at','valor_total']) ? $request->get('order_by') : 'id';
    $dir     = $request->get('dir') === 'asc' ? 'asc' : 'desc';

    // ---- contadores (pode usar Cache facade) ----
    $totalProdutos = Cache::remember('dash.total_produtos', now()->addMinutes(5), fn() => Produto::count());
    $totalGestores = Cache::remember('dash.total_gestores', now()->addMinutes(5), fn() => Gestor::count());
    $totalUsuarios = Cache::remember('dash.total_usuarios', now()->addMinutes(5), fn() => User::count());

    // ---- listas auxiliares leves ----
    $produtosComEstoqueBaixo = Produto::query()
        ->where('quantidade_estoque', '<=', 100)
        ->orderBy('quantidade_estoque')
        ->limit(10)
        ->get(['id','titulo','quantidade_estoque']);

    $estoqueParaPedidosEmPotencial = DB::table('pedido_produto as pp')
        ->join('pedidos as pe', 'pe.id', '=', 'pp.pedido_id')
        ->join('produtos as pr', 'pr.id', '=', 'pp.produto_id')
        ->where('pe.status', 'em_andamento')
        ->groupBy('pp.produto_id', 'pr.titulo', 'pr.quantidade_estoque')
        ->havingRaw('SUM(pp.quantidade) > pr.quantidade_estoque')
        ->orderByRaw('SUM(pp.quantidade) - pr.quantidade_estoque DESC')
        ->limit(10)
        ->get([
            'pp.produto_id',
            DB::raw('pr.titulo as titulo'),
            DB::raw('SUM(pp.quantidade) as qtd_em_pedidos'),
            'pr.quantidade_estoque',
            DB::raw('SUM(pp.quantidade) - pr.quantidade_estoque AS excedente'),
        ]);

    // ---- contratos vencendo/vencidos ----
    $gestoresVencendo = Gestor::vencendoEmAte(30)
        ->orderBy('vencimento_contrato')
        ->limit(10)
        ->get(['id','razao_social','vencimento_contrato'])
        ->map(function ($g) {
            $g->dias_restantes = Carbon::today()->diffInDays(Carbon::parse($g->vencimento_contrato), false);
            return $g;
        });

    $gestoresVencidos = Gestor::vencidos()
        ->orderBy('vencimento_contrato')
        ->limit(10)
        ->get(['id','razao_social','vencimento_contrato'])
        ->map(function ($g) {
            $g->dias_restantes = Carbon::today()->diffInDays(Carbon::parse($g->vencimento_contrato), false);
            return $g;
        });

    $distribuidoresVencendo = Distribuidor::vencendoEmAte(30)
        ->with('gestor:id,razao_social')
        ->orderBy('vencimento_contrato')
        ->limit(10)
        ->get(['id','razao_social','vencimento_contrato','gestor_id'])
        ->map(function ($d) {
            $d->dias_restantes = Carbon::today()->diffInDays(Carbon::parse($d->vencimento_contrato), false);
            return $d;
        });

    $distribuidoresVencidos = Distribuidor::vencidos()
        ->with('gestor:id,razao_social')
        ->orderBy('vencimento_contrato')
        ->limit(10)
        ->get(['id','razao_social','vencimento_contrato','gestor_id'])
        ->map(function ($d) {
            $d->dias_restantes = Carbon::today()->diffInDays(Carbon::parse($d->vencimento_contrato), false);
            return $d;
        });

    // ---- combos da UI ----
    $gestoresList       = Gestor::with('user:id,name')->orderBy('razao_social')->get(['id','user_id','razao_social']);
    $distribuidoresList = Distribuidor::with('user:id,name')->orderBy('razao_social')->get(['id','user_id','razao_social']);
    $cidadesList        = City::orderBy('name')->get(['id','name']);

    // ---- validação ----
    $request->validate([
        'data_inicio'     => ['nullable', 'date'],
        'data_fim'        => ['nullable', 'date', 'after_or_equal:data_inicio'],
        'gestor_id'       => ['nullable', 'integer', 'exists:gestores,id'],
        'distribuidor_id' => ['nullable', 'integer', 'exists:distribuidores,id'],
        'status'          => ['nullable', 'in:em_andamento,finalizado,cancelado'],
        'cidades'         => ['sometimes','array'],
        'cidades.*'       => ['integer','exists:cidades,id'],
    ]);

    $dataInicio     = $request->input('data_inicio');
    $dataFim        = $request->input('data_fim');
    $gestorId       = $request->input('gestor_id');
    $distribuidorId = $request->input('distribuidor_id');
    $status         = $request->input('status');
    $cidadesIds     = $request->input('cidades', []);

    // ---- query base ----
    $baseQuery = Pedido::query()
        ->with([
            'gestor:id,user_id,razao_social',
            'gestor.user:id,name',
            'distribuidor:id,user_id,razao_social',
            'distribuidor.user:id,name',
            'cidades:id,name',
        ])
        ->select(['id','gestor_id','distribuidor_id','valor_total','status','data','created_at']);

    // Datas com início/fim do dia
    if ($dataInicio && $dataFim) {
        $baseQuery->whereBetween('data', [
            Carbon::parse($dataInicio)->startOfDay(),
            Carbon::parse($dataFim)->endOfDay(),
        ]);
    } elseif ($dataInicio) {
        $baseQuery->where('data', '>=', Carbon::parse($dataInicio)->startOfDay());
    } elseif ($dataFim) {
        $baseQuery->where('data', '<=', Carbon::parse($dataFim)->endOfDay());
    }

    if ($gestorId)       $baseQuery->where('gestor_id', $gestorId);
    if ($distribuidorId) $baseQuery->where('distribuidor_id', $distribuidorId);
    if ($status)         $baseQuery->where('status', $status);

    if (!empty($cidadesIds)) {
        $baseQuery->whereHas('cidades', fn($q) => $q->whereIn('cidades.id', $cidadesIds));
    }

    // ---- agregados (subquery para evitar erro de GROUP BY) ----
    $sub = (clone $baseQuery)
        ->without(['gestor','distribuidor','cidades'])
        ->select(['id','valor_total']);

    // remove ORDER BY herdado para a subquery
    $sub->getQuery()->orders = null;

    $aggregates = DB::query()
        ->fromSub($sub, 'p')
        ->selectRaw('COUNT(*) AS total, COALESCE(SUM(valor_total),0) AS soma')
        ->first();

    $totalPedidosPeriodo = (int) ($aggregates->total ?? 0);
    $somaPeriodo         = (float) ($aggregates->soma ?? 0.0);

    // ---- paginação ----
    $pedidos = (clone $baseQuery)
        ->orderBy($orderBy, $dir)
        ->paginate($perPage)
        ->appends($request->query());

    $somaPagina = $pedidos->getCollection()->sum('valor_total');

    // ---- soma geral (fora de filtros) ----
    $somaGeralTodosPedidos = Cache::remember('dash.soma_geral_pedidos', now()->addMinutes(5), fn() =>
        (float) Pedido::query()->sum('valor_total')
    );

    return view('admin.dashboard', [
        'pedidos'                       => $pedidos,
        'totalGestores'                 => $totalGestores,
        'totalProdutos'                 => $totalProdutos,
        'totalUsuarios'                 => $totalUsuarios,
        'totalPedidosPeriodo'           => $totalPedidosPeriodo,
        'somaPeriodo'                   => $somaPeriodo,
        'somaPagina'                    => $somaPagina,
        'somaGeralTodosPedidos'         => $somaGeralTodosPedidos,
        'dataInicio'                    => $dataInicio,
        'dataFim'                       => $dataFim,
        'gestorId'                      => $gestorId,
        'distribuidorId'                => $distribuidorId,
        'gestoresList'                  => $gestoresList,
        'distribuidoresList'            => $distribuidoresList,
        'status'                        => $status,
        'cidadesList'                   => $cidadesList,
        'cidadesIds'                    => $cidadesIds,
        'produtosComEstoqueBaixo'       => $produtosComEstoqueBaixo,
        'estoqueParaPedidosEmPotencial' => $estoqueParaPedidosEmPotencial,
        'gestoresVencendo'              => $gestoresVencendo,
        'gestoresVencidos'              => $gestoresVencidos,
        'distribuidoresVencendo'        => $distribuidoresVencendo,
        'distribuidoresVencidos'        => $distribuidoresVencidos,
        'perPage'                       => $perPage,
        'orderBy'                       => $orderBy,
        'dir'                           => $dir,
    ]);
}



    private function rangeToDates(?string $ini, ?string $fim): array
    {
        $start = $ini ? Carbon::parse($ini)->startOfDay() : null;
        $end   = $fim ? Carbon::parse($fim)->endOfDay()   : null;
        return [$start, $end];
    }

    public function chartNotasPagas(Request $request)
    {
        $hasDateFilters = $request->filled('data_inicio') || $request->filled('data_fim');

        if (!$hasDateFilters) {
            $now   = Carbon::now()->startOfMonth();
            $start = (clone $now)->subMonths(6)->startOfMonth();
            $end   = (clone $now)->addMonths(6)->endOfMonth();
        } else {
            [$start, $end] = $this->rangeToDates($request->input('data_inicio'), $request->input('data_fim'));
            $now = Carbon::now()->startOfMonth();
            if (!$start && $end) {
                $start = (clone Carbon::parse($end))->startOfMonth()->subMonths(12);
            } elseif ($start && !$end) {
                $end = (clone Carbon::parse($start))->startOfMonth()->addMonths(12)->endOfMonth();
            } elseif (!$start && !$end) {
                $start = (clone $now)->subMonths(6)->startOfMonth();
                $end   = (clone $now)->addMonths(6)->endOfMonth();
            }
        }

        $liqSub = DB::table('nota_pagamentos')
            ->selectRaw("
                nota_fiscal_id,
                MAX(COALESCE(data_pagamento::timestamp, created_at)) AS liquidado_em
            ")
            ->groupBy('nota_fiscal_id');

        $q = NotaFiscal::query()
            ->joinSub($liqSub, 'liq', fn($join) => $join->on('liq.nota_fiscal_id', '=', 'notas_fiscais.id'))
            ->where('notas_fiscais.status_financeiro', 'pago')
            ->whereNotNull('liq.liquidado_em')
            ->when($request->filled('gestor_id'), fn($qq) => $qq->whereHas('pedido', fn($p) => $p->where('gestor_id', $request->gestor_id)))
            ->when($request->filled('distribuidor_id'), fn($qq) => $qq->whereHas('pedido', fn($p) => $p->where('distribuidor_id', $request->distribuidor_id)))
            ->whereBetween('liq.liquidado_em', [$start, $end])
            ->selectRaw("
                date_trunc('month', liq.liquidado_em) AS ym_month,
                COUNT(DISTINCT notas_fiscais.id)      AS qtd,
                SUM(notas_fiscais.valor_total)        AS valor
            ")
            ->groupBy('ym_month')
            ->orderBy('ym_month');

        $rows = $q->get();

        $mapQtd = [];
        $mapVal = [];
        foreach ($rows as $r) {
            $ym = Carbon::parse($r->ym_month)->format('Y-m');
            $mapQtd[$ym] = (int) $r->qtd;
            $mapVal[$ym] = (float) $r->valor;
        }

        $labels = [];
        $serieQtd = [];
        $serieVal = [];

        $cursor   = (clone $start)->startOfMonth();
        $endMonth = (clone $end)->startOfMonth();

        while ($cursor->lte($endMonth)) {
            $ym = $cursor->format('Y-m');
            $labels[]   = $ym;
            $serieQtd[] = $mapQtd[$ym] ?? 0;
            $serieVal[] = $mapVal[$ym] ?? 0.0;
            $cursor->addMonth();
        }

        return response()->json([
            'labels' => $labels,
            'series' => [
                'quantidade' => $serieQtd,
                'valor'      => $serieVal,
            ],
        ]);
    }

    public function chartVendasPorGestor(Request $request)
    {
        [$start, $end] = $this->rangeToDates($request->input('data_inicio'), $request->input('data_fim'));

        $q = Pedido::query()
            ->join('gestores', 'gestores.id', '=', 'pedidos.gestor_id')
            ->leftJoin('users', 'users.id', '=', 'gestores.user_id')
            ->selectRaw("COALESCE(users.name, gestores.razao_social, CONCAT('Gestor #', gestores.id)) as nome, SUM(pedidos.valor_total) as total")
            ->when($start, fn($qq) => $qq->where('pedidos.data', '>=', $start->toDateString()))
            ->when($end,   fn($qq) => $qq->where('pedidos.data', '<=', $end->toDateString()))
            ->when($request->filled('distribuidor_id'), fn($qq) => $qq->where('pedidos.distribuidor_id', $request->distribuidor_id))
            ->when($request->filled('status'), fn($qq) => $qq->where('pedidos.status', $request->status))
            ->groupBy('nome')
            ->orderByDesc('total')
            ->get();

        return response()->json([
            'labels' => $q->pluck('nome'),
            'series' => $q->pluck('total')->map(fn($v)=>(float)$v),
        ]);
    }

    public function chartVendasPorDistribuidor(Request $request)
    {
        [$start, $end] = $this->rangeToDates($request->input('data_inicio'), $request->input('data_fim'));

        $q = Pedido::query()
            ->join('distribuidores', 'distribuidores.id', '=', 'pedidos.distribuidor_id')
            ->leftJoin('users', 'users.id', '=', 'distribuidores.user_id')
            ->selectRaw("COALESCE(users.name, distribuidores.razao_social, CONCAT('Distribuidor #', distribuidores.id)) as nome, SUM(pedidos.valor_total) as total")
            ->when($start, fn($qq) => $qq->where('pedidos.data', '>=', $start->toDateString()))
            ->when($end,   fn($qq) => $qq->where('pedidos.data', '<=', $end->toDateString()))
            ->when($request->filled('gestor_id'), fn($qq) => $qq->where('pedidos.gestor_id', $request->gestor_id))
            ->when($request->filled('status'), fn($qq) => $qq->where('pedidos.status', $request->status))
            ->groupBy('nome')
            ->orderByDesc('total')
            ->get();

        return response()->json([
            'labels' => $q->pluck('nome'),
            'series' => $q->pluck('total')->map(fn($v)=>(float)$v),
        ]);
    }

    public function chartComissoesGestores(Request $request)
    {
        [$start, $end] = $this->rangeToDates($request->input('data_inicio'), $request->input('data_fim'));

        $q = NotaFiscal::query()
            ->join('pedidos', 'pedidos.id', '=', 'notas_fiscais.pedido_id')
            ->join('gestores', 'gestores.id', '=', 'pedidos.gestor_id')
            ->leftJoin('users', 'users.id', '=', 'gestores.user_id')
            ->where('notas_fiscais.status_financeiro', 'pago')
            ->whereNotNull('notas_fiscais.pago_em')
            ->when($start, fn($qq) => $qq->where('notas_fiscais.pago_em', '>=', $start))
            ->when($end,   fn($qq) => $qq->where('notas_fiscais.pago_em', '<=', $end))
            ->when($request->filled('gestor_id'), fn($qq)=>$qq->where('pedidos.gestor_id', $request->gestor_id))
            ->when($request->filled('distribuidor_id'), fn($qq)=>$qq->where('pedidos.distribuidor_id', $request->distribuidor_id))
            ->selectRaw("
                COALESCE(users.name, gestores.razao_social, CONCAT('Gestor #', gestores.id)) as nome,
                SUM(notas_fiscais.valor_total * (gestores.percentual_vendas/100)) as total
            ")
            ->groupBy('nome')
            ->orderByDesc('total')
            ->get();

        return response()->json([
            'labels' => $q->pluck('nome'),
            'series' => $q->pluck('total')->map(fn($v)=>(float)$v),
        ]);
    }

    public function chartComissoesDistribuidores(Request $request)
    {
        [$start, $end] = $this->rangeToDates($request->input('data_inicio'), $request->input('data_fim'));

        $q = NotaFiscal::query()
            ->join('pedidos', 'pedidos.id', '=', 'notas_fiscais.pedido_id')
            ->join('distribuidores', 'distribuidores.id', '=', 'pedidos.distribuidor_id')
            ->leftJoin('users', 'users.id', '=', 'distribuidores.user_id')
            ->where('notas_fiscais.status_financeiro', 'pago')
            ->whereNotNull('notas_fiscais.pago_em')
            ->when($start, fn($qq) => $qq->where('notas_fiscais.pago_em', '>=', $start))
            ->when($end,   fn($qq) => $qq->where('notas_fiscais.pago_em', '<=', $end))
            ->when($request->filled('gestor_id'), fn($qq)=>$qq->where('pedidos.gestor_id', $request->gestor_id))
            ->when($request->filled('distribuidor_id'), fn($qq)=>$qq->where('pedidos.distribuidor_id', $request->distribuidor_id))
            ->selectRaw("
                COALESCE(users.name, distribuidores.razao_social, CONCAT('Distribuidor #', distribuidores.id)) as nome,
                SUM(notas_fiscais.valor_total * (distribuidores.percentual_vendas/100)) as total
            ")
            ->groupBy('nome')
            ->orderByDesc('total')
            ->get();

        return response()->json([
            'labels' => $q->pluck('nome'),
            'series' => $q->pluck('total')->map(fn($v)=>(float)$v),
        ]);
    }

    public function chartVendasPorCliente(Request $request)
    {
        [$start, $end] = $this->rangeToDates($request->input('data_inicio'), $request->input('data_fim'));

        $q = Pedido::query()
            ->join('clientes', 'clientes.id', '=', 'pedidos.cliente_id')
            ->leftJoin('users', 'users.id', '=', 'clientes.user_id')
            ->selectRaw("
                COALESCE(users.name, clientes.razao_social, CONCAT('Cliente #', clientes.id)) as nome,
                SUM(pedidos.valor_total) as total
            ")
            ->when($start, fn($qq) => $qq->where('pedidos.data', '>=', $start))
            ->when($end,   fn($qq) => $qq->where('pedidos.data', '<=', $end))
            ->when($request->filled('gestor_id'), fn($qq) => $qq->where('pedidos.gestor_id', $request->gestor_id))
            ->when($request->filled('distribuidor_id'), fn($qq) => $qq->where('pedidos.distribuidor_id', $request->distribuidor_id))
            ->when($request->filled('status'), fn($qq) => $qq->where('pedidos.status', $request->status))
            ->groupByRaw("COALESCE(users.name, clientes.razao_social, CONCAT('Cliente #', clientes.id))")
            ->orderByDesc('total')
            ->limit(20)
            ->get();

        return response()->json([
            'labels' => $q->pluck('nome'),
            'series' => $q->pluck('total')->map(fn($v)=>(float)$v),
        ]);
    }

    public function chartVendasPorCidade(Request $request)
    {
        [$start, $end] = $this->rangeToDates($request->input('data_inicio'), $request->input('data_fim'));

        $pedidos = Pedido::query()
            ->with(['cidades:id,name'])
            ->when($start, fn($q) => $q->where('data', '>=', $start->toDateString()))
            ->when($end,   fn($q) => $q->where('data', '<=', $end->toDateString()))
            ->when($request->filled('gestor_id'), fn($q) => $q->where('gestor_id', $request->gestor_id))
            ->when($request->filled('distribuidor_id'), fn($q) => $q->where('distribuidor_id', $request->distribuidor_id))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->get(['id','valor_total']);

        $totais = [];
        foreach ($pedidos as $pedido) {
            if ($pedido->cidades && $pedido->cidades->count()) {
                foreach ($pedido->cidades as $cidade) {
                    $nome = $cidade->name ?? $cidade->nome ?? 'Sem cidade';
                    $totais[$nome] = ($totais[$nome] ?? 0) + (float) $pedido->valor_total;
                }
            } else {
                $totais['Sem cidade'] = ($totais['Sem cidade'] ?? 0) + (float) $pedido->valor_total;
            }
        }

        arsort($totais);
        $totais = array_slice($totais, 0, 20, true);

        return response()->json([
            'labels' => array_keys($totais),
            'series' => array_values($totais),
        ]);
    }

    public function exportExcel(Request $request)
    {
        $request->validate([
            'data_inicio'     => ['nullable', 'date'],
            'data_fim'        => ['nullable', 'date', 'after_or_equal:data_inicio'],
            'gestor_id'       => ['nullable', 'integer', 'exists:gestores,id'],
            'distribuidor_id' => ['nullable', 'integer', 'exists:distribuidores,id'],
            'status'          => ['nullable', 'in:em_andamento,finalizado,cancelado'],
        ]);

        $file = 'relatorio-pedidos-dashboard-'.now()->format('Y-m-d_His').'.xlsx';
        return Excel::download(new PedidosDashboardExport($request), $file);
    }

    public function exportPdf(Request $request)
    {
        $request->validate([
            'data_inicio'     => ['nullable', 'date'],
            'data_fim'        => ['nullable', 'date', 'after_or_equal:data_inicio'],
            'gestor_id'       => ['nullable', 'integer', 'exists:gestores,id'],
            'distribuidor_id' => ['nullable', 'integer', 'exists:distribuidores,id'],
            'status'          => ['nullable', 'in:em_andamento,finalizado,cancelado'],
        ]);

        $dataInicio     = $request->input('data_inicio');
        $dataFim        = $request->input('data_fim');
        $gestorId       = $request->input('gestor_id');
        $distribuidorId = $request->input('distribuidor_id');
        $status         = $request->input('status');

        $q = Pedido::query()->with([
            'gestor.user:id,name',
            'distribuidor.user:id,name',
            'cidades:id,name',
        ]);

        if ($dataInicio && $dataFim) {
            $q->whereBetween('data', [
                Carbon::parse($dataInicio)->toDateString(),
                Carbon::parse($dataFim)->toDateString(),
            ]);
        } elseif ($dataInicio) {
            $q->where('data', '>=', Carbon::parse($dataInicio)->toDateString());
        } elseif ($dataFim) {
            $q->where('data', '<=', Carbon::parse($dataFim)->toDateString());
        }

        if ($gestorId)       { $q->where('gestor_id', $gestorId); }
        if ($distribuidorId) { $q->where('distribuidor_id', $distribuidorId); }
        if ($status)         { $q->where('status', $status); }

        $pedidos = $q->orderByDesc('id')->get();

        $gestor = $gestorId ? Gestor::with('user:id,name')->find($gestorId) : null;
        $distribuidor = $distribuidorId ? Distribuidor::with('user:id,name')->find($distribuidorId) : null;
        $nomeGestor = $gestor?->user?->name ?? $gestor?->razao_social;
        $nomeDistribuidor = $distribuidor?->user?->name ?? $distribuidor?->razao_social;

        $pdf = Pdf::loadView('admin.reports.pedidos', [
            'pedidos'          => $pedidos,
            'dataInicio'       => $dataInicio,
            'dataFim'          => $dataFim,
            'gestorId'         => $gestorId,
            'distribuidorId'   => $distribuidorId,
            'nomeGestor'       => $nomeGestor,
            'nomeDistribuidor' => $nomeDistribuidor,
            'status'           => $status,
        ])->setPaper('a4', 'portrait');

        $file = 'relatorio-pedidos-dashboard-'.now()->format('Y-m-d_His').'.pdf';
        return $pdf->download($file);
    }
}
