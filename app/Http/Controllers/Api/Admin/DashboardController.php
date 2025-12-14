<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exports\PedidosDashboardExport;
use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class DashboardController extends Controller
{
    public function __construct(private DashboardService $service)
    {
    }

    // GET /api/admin/dashboard
    public function index(Request $request)
    {
        $payload = $this->service->getDashboardPayload($request);

        // Pra API, devolvemos só os dados (sem view):
        // Converte pedidos paginados para array (inclui links/meta do paginator)
        $pedidos = $payload['pedidos'];

        $payload['pedidos'] = [
            'data' => $pedidos->items(),
            'meta' => [
                'current_page' => $pedidos->currentPage(),
                'per_page'     => $pedidos->perPage(),
                'total'        => $pedidos->total(),
                'last_page'    => $pedidos->lastPage(),
            ],
        ];

        return response()->json([
            'ok'   => true,
            'data' => $payload,
        ]);
    }

    public function chartNotasPagas(Request $request)
    {
        return response()->json($this->service->chartNotasPagas($request));
    }

    public function chartVendasPorGestor(Request $request)
    {
        return response()->json($this->service->chartVendasPorGestor($request));
    }

    public function chartVendasPorDistribuidor(Request $request)
    {
        return response()->json($this->service->chartVendasPorDistribuidor($request));
    }

    public function chartVendasPorCliente(Request $request)
    {
        return response()->json($this->service->chartVendasPorCliente($request));
    }

    public function chartVendasPorCidade(Request $request)
    {
        return response()->json($this->service->chartVendasPorCidade($request));
    }

    public function exportExcel(Request $request)
    {
        $file = 'relatorio-pedidos-dashboard-'.now()->format('Y-m-d_His').'.xlsx';

        return Excel::download(new PedidosDashboardExport($request), $file);
    }

    public function exportPdf(Request $request)
    {
        $ctx = $this->service->queryPedidosForExport($request);

        $pedidos = $ctx['query']->get();

        $names = $this->service->resolveExportNames(
            $ctx['gestorId'] ? (int) $ctx['gestorId'] : null,
            $ctx['distribuidorId'] ? (int) $ctx['distribuidorId'] : null
        );

        $pdf = Pdf::loadView('admin.reports.pedidos', [
            'pedidos'          => $pedidos,
            'dataInicio'       => $ctx['dataInicio'],
            'dataFim'          => $ctx['dataFim'],
            'gestorId'         => $ctx['gestorId'],
            'distribuidorId'   => $ctx['distribuidorId'],
            'nomeGestor'       => $names['nomeGestor'] ?? null,
            'nomeDistribuidor' => $names['nomeDistribuidor'] ?? null,
            'status'           => $ctx['status'],
        ])->setPaper('a4', 'portrait');

        $file = 'relatorio-pedidos-dashboard-'.now()->format('Y-m-d_His').'.pdf';
        return $pdf->download($file);
    }
}
