<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Gestor;
use App\Models\Distribuidor;
use App\Models\Produto;
use App\Models\Pedido;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PedidosDashboardExport;
use Barryvdh\DomPDF\Facade\Pdf;

class DashboardController extends Controller
{
    public function index(Request $request)
{
    // Quantidades topo
    $totalProdutos = Produto::count();
    $totalGestores = Gestor::count();
    $totalUsuarios = User::count();

    // Listas para selects
    $gestoresList = Gestor::with('user:id,name')->orderBy('razao_social')->get();
    $distribuidoresList = Distribuidor::with('user:id,name')->orderBy('razao_social')->get();
    $gestoresComDistribuidores = Gestor::with([
        'user:id,name',
        'distribuidores.user:id,name',
    ])->orderBy('razao_social')->get();

    // Validação dos filtros
    $request->validate([
        'data_inicio'     => ['nullable', 'date'],
        'data_fim'        => ['nullable', 'date', 'after_or_equal:data_inicio'],
        'gestor_id'       => ['nullable', 'integer', 'exists:gestores,id'],
        'distribuidor_id' => ['nullable', 'integer', 'exists:distribuidores,id'],
        'status'          => ['nullable', 'in:em_andamento,finalizado,cancelado'], // [novo]
    ]);

    // Filtros (variáveis)
    $dataInicio     = $request->input('data_inicio');
    $dataFim        = $request->input('data_fim');
    $gestorId       = $request->input('gestor_id');
    $distribuidorId = $request->input('distribuidor_id');
    $status         = $request->input('status'); // [novo]

    // Query base
    $baseQuery = Pedido::with([
        'gestor.user:id,name',
        'gestor.distribuidores.user:id,name',
        'distribuidor.user:id,name',
        'cidades:id,name',
    ]);

    // Filtro por datas
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

    // Filtros por gestor / distribuidor
    if ($gestorId) {
        $baseQuery->where('gestor_id', $gestorId);
    }
    if ($distribuidorId) {
        $baseQuery->where('distribuidor_id', $distribuidorId);
    }

    // Filtro por status [novo]
    if ($status) {
        $baseQuery->where('status', $status);
    }

    // KPIs do período (respeitam todos os filtros acima)
    $totalPedidosPeriodo = (clone $baseQuery)->count();
    $somaPeriodo         = (clone $baseQuery)->sum('valor_total');

    // Listagem paginada
    $pedidos = (clone $baseQuery)
        ->latest('id')
        ->paginate(20)
        ->appends($request->only('data_inicio','data_fim','gestor_id','distribuidor_id','status')); // [ajuste]

    // Soma da página atual
    $somaPagina = $pedidos->getCollection()->sum('valor_total');

    // Soma geral (sem filtros)
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
        'status'                    => $status, // [novo] envia pra view
    ]);
}

    private function buildBaseQuery(Request $request)
    {
        //Aqui é criado uma query base
        $dataInicio     = $request->input('data_inicio');
        $dataFim        = $request->input('data_fim');
        $gestorId       = $request->input('gestor_id');
        $distribuidorId = $request->input('distribuidor_id');
        $status         = $request->input('status');

        $query = Pedido::with([
            'gestor.user:id,name',
            'gestor.distribuidores.user:id,name',
            'distribuidor.user:id,name',
            'cidades:id,name',
        ]);

        if ($dataInicio && $dataFim) {
            $query->whereBetween('data', [\Carbon\Carbon::parse($dataInicio)->toDateString(), \Carbon\Carbon::parse($dataFim)->toDateString()]);
        } elseif ($dataInicio) {
            $query->where('data', '>=', \Carbon\Carbon::parse($dataInicio)->toDateString());
        } elseif ($dataFim) {
            $query->where('data', '<=', \Carbon\Carbon::parse($dataFim)->toDateString());
        }

        if ($gestorId)       $query->where('gestor_id', $gestorId);
        if ($distribuidorId) $query->where('distribuidor_id', $distribuidorId);
        if ($status) {       $query->where('status', $status); }

        return $query;
    }

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
        // mesma query, mas aqui sem paginação (cuidado com volume muito grande)
        $pedidosQuery = $this->buildBaseQuery($request)->orderByDesc('id');
        $pedidos = $pedidosQuery->get();

        // nomes bonitos nos filtros (opcional)
        $nomeGestor = null;
        $nomeDistribuidor = null;
        if ($request->filled('gestor_id')) {
            $nomeGestor = optional(\App\Models\Gestor::find($request->gestor_id)?->user)->name
                        ?? \App\Models\Gestor::find($request->gestor_id)?->razao_social;
        }
        if ($request->filled('distribuidor_id')) {
            $nomeDistribuidor = optional(\App\Models\Distribuidor::find($request->distribuidor_id)?->user)->name
                                ?? \App\Models\Distribuidor::find($request->distribuidor_id)?->razao_social;
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
