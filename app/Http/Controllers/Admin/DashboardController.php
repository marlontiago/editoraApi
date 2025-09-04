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

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // KPIs básicas
        $totalProdutos = Produto::count();
        $totalGestores = Gestor::count();
        $totalUsuarios = User::count();

        // Listas
        $gestoresList = Gestor::with('user:id,name')->orderBy('razao_social')->get();
        $distribuidoresList = Distribuidor::with('user:id,name')->orderBy('razao_social')->get();
        $gestoresComDistribuidores = Gestor::with([
            'user:id,name',
            'distribuidores.user:id,name',
        ])->orderBy('razao_social')->get();

        // Valida filtros
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

        // Query base de pedidos
        $baseQuery = Pedido::with([
            'gestor.user:id,name',
            'gestor.distribuidores.user:id,name',
            'distribuidor.user:id,name',
            'cidades:id,name',
        ]);

        // Filtro data (coluna pedidos.data)
        if ($dataInicio && $dataFim) {
            $baseQuery->whereBetween('data', [
                Carbon::parse($dataInicio)->toDateString(),
                Carbon::parse($dataFim)->toDateString(),
            ]);
        } elseif ($dataInicio) {
            $baseQuery->where('data', '>=', Carbon::parse($dataInicio)->toDateString());
        } elseif ($dataFim) {
            $baseQuery->where('data', '<=', Carbon::parse($dataFim)->toDateString());
        }

        // Filtros adicionais
        if ($gestorId)       $baseQuery->where('gestor_id', $gestorId);
        if ($distribuidorId) $baseQuery->where('distribuidor_id', $distribuidorId);
        if ($status)         $baseQuery->where('status', $status);

        // KPIs do período
        $totalPedidosPeriodo = (clone $baseQuery)->count();
        $somaPeriodo         = (clone $baseQuery)->sum('valor_total');

        // Lista paginada
        $pedidos = (clone $baseQuery)
            ->latest('id')
            ->paginate(20)
            ->appends($request->only('data_inicio','data_fim','gestor_id','distribuidor_id','status'));

        $somaPagina = $pedidos->getCollection()->sum('valor_total');
        $somaGeralTodosPedidos = Pedido::sum('valor_total');

        return view('admin.dashboard', [
            'pedidos'                   => $pedidos,
            'totalGestores'             => $totalGestores,
            'totalProdutos'             => $totalProdutos,
            'totalUsuarios'             => $totalUsuarios,
            'totalPedidosPeriodo'       => $totalPedidosPeriodo,
            'somaPeriodo'               => $somaPeriodo,
            'somaPagina'                => $somaPagina,
            'somaGeralTodosPedidos'     => $somaGeralTodosPedidos,
            'dataInicio'                => $dataInicio,
            'dataFim'                   => $dataFim,
            'gestorId'                  => $gestorId,
            'distribuidorId'            => $distribuidorId,
            'gestoresList'              => $gestoresList,
            'distribuidoresList'        => $distribuidoresList,
            'gestoresComDistribuidores' => $gestoresComDistribuidores,
            'status'                    => $status,
        ]);
    }

    /** Utilitário: converte range para objetos Carbon (inicio/fim de dia) */
    private function rangeToDates(?string $ini, ?string $fim): array
    {
        $start = $ini ? Carbon::parse($ini)->startOfDay() : null;
        $end   = $fim ? Carbon::parse($fim)->endOfDay()   : null;
        return [$start, $end];
    }

    /** Linha: Notas pagas ao longo do tempo (quantidade e valor) */
    public function chartNotasPagas(Request $request)
    {
        // Quando não houver filtros de data, centraliza no mês atual: -6 a +6 meses
        $hasDateFilters = $request->filled('data_inicio') || $request->filled('data_fim');

        if (!$hasDateFilters) {
            $now   = \Carbon\Carbon::now()->startOfMonth();
            $start = (clone $now)->subMonths(6)->startOfMonth();
            $end   = (clone $now)->addMonths(6)->endOfMonth();
        } else {
            [$start, $end] = $this->rangeToDates($request->input('data_inicio'), $request->input('data_fim'));

            // Se só um lado veio, completa para manter a janela centrada de 13 meses
            $now = \Carbon\Carbon::now()->startOfMonth();
            if (!$start && $end) {
                // fim veio: volta 12 meses pra garantir 13 pontos incluindo fim
                $start = (clone \Carbon\Carbon::parse($end))->startOfMonth()->subMonths(12);
            } elseif ($start && !$end) {
                // início veio: avança 12 meses pra garantir 13 pontos incluindo início
                $end = (clone \Carbon\Carbon::parse($start))->startOfMonth()->addMonths(12)->endOfMonth();
            } elseif (!$start && !$end) {
                // fallback (não deve cair aqui, mas por segurança)
                $start = (clone $now)->subMonths(6)->startOfMonth();
                $end   = (clone $now)->addMonths(6)->endOfMonth();
            }
        }

        // Subquery: data em que a NOTA foi efetivamente liquidada = último pagamento
        $liqSub = DB::table('nota_pagamentos')
            ->selectRaw("
                nota_fiscal_id,
                MAX(COALESCE(data_pagamento::timestamp, created_at)) AS liquidado_em
            ")
            ->groupBy('nota_fiscal_id');

        // Query principal usando a data de liquidação
        $q = NotaFiscal::query()
            ->joinSub($liqSub, 'liq', fn($join) => $join->on('liq.nota_fiscal_id', '=', 'notas_fiscais.id'))
            ->where('notas_fiscais.status_financeiro', 'pago')
            ->whereNotNull('liq.liquidado_em')
            // filtros via pedido (gestor/distribuidor)
            ->when($request->filled('gestor_id'), fn($qq) => $qq->whereHas('pedido', fn($p) => $p->where('gestor_id', $request->gestor_id)))
            ->when($request->filled('distribuidor_id'), fn($qq) => $qq->whereHas('pedido', fn($p) => $p->where('distribuidor_id', $request->distribuidor_id)))
            // período (pela liquidação)
            ->whereBetween('liq.liquidado_em', [$start, $end])
            ->selectRaw("
                date_trunc('month', liq.liquidado_em) AS ym_month,
                COUNT(DISTINCT notas_fiscais.id)      AS qtd,
                SUM(notas_fiscais.valor_total)        AS valor
            ")
            ->groupBy('ym_month')
            ->orderBy('ym_month');

        $rows = $q->get();

        // Monta série mensal contínua do start ao end
        $mapQtd = [];
        $mapVal = [];
        foreach ($rows as $r) {
            $ym = \Carbon\Carbon::parse($r->ym_month)->format('Y-m');
            $mapQtd[$ym] = (int) $r->qtd;
            $mapVal[$ym] = (float) $r->valor;
        }

        $labels = [];
        $serieQtd = [];
        $serieVal = [];

        $cursor   = (clone $start)->startOfMonth();
        $endMonth = (clone $end)->startOfMonth();

        while ($cursor->lte($endMonth)) {
            $ym = $cursor->format('Y-m');       // e.g., 2025-09
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



    /** Pizza: Vendas por Gestor (soma pedidos.valor_total) */
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

    /** Pizza: Vendas por Distribuidor (soma pedidos.valor_total) */
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

    /**
     * Pizza: Comissões por Gestor
     * Base: SUM(notas_fiscais.valor_total * (gestores.percentual_vendas/100))
     * Critério: apenas notas com status_financeiro = 'pago'
     */
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

    /**
     * Pizza: Comissões por Distribuidor
     * Base: SUM(notas_fiscais.valor_total * (distribuidores.percentual_vendas/100))
     * Critério: notas pagas
     */
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

        $q = \App\Models\Pedido::query()
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
            // PostgreSQL: agrupe pela expressão, não pelo alias:
            ->groupByRaw("COALESCE(users.name, clientes.razao_social, CONCAT('Cliente #', clientes.id))")
            ->orderByDesc('total')
            ->limit(20) // opcional: top 20
            ->get();

        return response()->json([
            'labels' => $q->pluck('nome'),
            'series' => $q->pluck('total')->map(fn($v)=>(float)$v),
        ]);
    }

    public function chartVendasPorCidade(Request $request)
{
    [$start, $end] = $this->rangeToDates($request->input('data_inicio'), $request->input('data_fim'));

    // Busca pedidos com as CIDADES relacionadas (many-to-many)
    $pedidos = \App\Models\Pedido::query()
        ->with(['cidades:id,name']) // usa a relação que você já tem no index()
        ->when($start, fn($q) => $q->where('data', '>=', $start->toDateString()))
        ->when($end,   fn($q) => $q->where('data', '<=', $end->toDateString()))
        ->when($request->filled('gestor_id'), fn($q) => $q->where('gestor_id', $request->gestor_id))
        ->when($request->filled('distribuidor_id'), fn($q) => $q->where('distribuidor_id', $request->distribuidor_id))
        ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
        ->get(['id','valor_total']); // campos necessários

    // Agrega por cidade no PHP (funciona independente do nome da tabela pivô)
    $totais = [];
    foreach ($pedidos as $pedido) {
        if ($pedido->cidades && $pedido->cidades->count()) {
            foreach ($pedido->cidades as $cidade) {
                $nome = $cidade->name ?? $cidade->nome ?? 'Sem cidade';
                $totais[$nome] = ($totais[$nome] ?? 0) + (float) $pedido->valor_total;
            }
        } else {
            // pedido sem cidades vinculadas
            $totais['Sem cidade'] = ($totais['Sem cidade'] ?? 0) + (float) $pedido->valor_total;
        }
    }

    // Ordena desc e pega top 20 pra não poluir o donut
    arsort($totais);
    $totais = array_slice($totais, 0, 20, true);

    return response()->json([
        'labels' => array_keys($totais),
        'series' => array_values($totais),
    ]);
}






    // ===== Exportações permanecem
    public function exportExcel(Request $request)
    {
        $request->validate([
            'data_inicio' => ['nullable', 'date'],
            'data_fim'    => ['nullable', 'date', 'after_or_equal:data_inicio'],
            'gestor_id'   => ['nullable', 'integer', 'exists:gestores,id'],
            'distribuidor_id' => ['nullable', 'integer', 'exists:distribuidores,id'],
            'status'          => ['nullable', 'in:em_andamento,finalizado,cancelado'],
        ]);

        $file = 'relatorio-pedidos-dashboard-'.now()->format('Y-m-d_His').'.xlsx';
        return Excel::download(new PedidosDashboardExport($request), $file);
    }

    public function exportPdf(Request $request)
    {
        $pedidos = Pedido::query()
            ->with(['gestor.user:id,name','distribuidor.user:id,name','cidades:id,name'])
            ->when($request->filled('data_inicio'), fn($q)=>$q->where('data','>=',Carbon::parse($request->data_inicio)->toDateString()))
            ->when($request->filled('data_fim'), fn($q)=>$q->where('data','<=',Carbon::parse($request->data_fim)->toDateString()))
            ->when($request->filled('gestor_id'), fn($q)=>$q->where('gestor_id',$request->gestor_id))
            ->when($request->filled('distribuidor_id'), fn($q)=>$q->where('distribuidor_id',$request->distribuidor_id))
            ->when($request->filled('status'), fn($q)=>$q->where('status',$request->status))
            ->orderByDesc('id')
            ->get();

        $nomeGestor = null;
        $nomeDistribuidor = null;
        if ($request->filled('gestor_id')) {
            $nomeGestor = optional(Gestor::find($request->gestor_id)?->user)->name
                        ?? Gestor::find($request->gestor_id)?->razao_social;
        }
        if ($request->filled('distribuidor_id')) {
            $nomeDistribuidor = optional(Distribuidor::find($request->distribuidor_id)?->user)->name
                              ?? Distribuidor::find($request->distribuidor_id)?->razao_social;
        }

        $pdf = Pdf::loadView('admin.reports.pedidos', [
            'pedidos'         => $pedidos,
            'dataInicio'      => $request->input('data_inicio'),
            'dataFim'         => $request->input('data_fim'),
            'gestorId'        => $request->input('gestor_id'),
            'distribuidorId'  => $request->input('distribuidor_id'),
            'nomeGestor'      => $nomeGestor,
            'nomeDistribuidor'=> $nomeDistribuidor,
        ])->setPaper('a4', 'portrait');

        $file = 'relatorio-pedidos-dashboard-'.now()->format('Y-m-d_His').'.pdf';
        return $pdf->download($file);
    }
}
