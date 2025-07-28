<?php

namespace App\Http\Controllers\Gestor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Venda;
use App\Models\Commission;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\RelatorioVendasExport;

class RelatorioVendasController extends Controller
{
    public function index(Request $request)
    {
        [$vendas, $comissoes] = $this->getVendasFiltradas($request);

        return view('gestor.relatorios.vendas', compact('vendas', 'comissoes'));
    }

    public function exportExcel(Request $request)
    {
        return Excel::download(new RelatorioVendasExport($request), 'relatorio_vendas.xlsx');
    }

    public function exportPdf(Request $request)
    {
        [$vendas, $comissoes] = $this->getVendasFiltradas($request);

        $pdf = Pdf::loadView('gestor.relatorios.vendas_pdf', compact('vendas', 'comissoes'));
        return $pdf->download('relatorio_vendas.pdf');
    }

    /**
     * Centraliza a lógica de filtro para reutilizar no index / exportações
     */
    private function getVendasFiltradas(Request $request)
    {
        $gestor = Auth::user()->gestor;

        $distribuidorIds = $gestor->distribuidores->pluck('id');
        $userIds         = $gestor->distribuidores->pluck('user_id');

        $query = Venda::with('distribuidor.user')
            ->whereIn('distribuidor_id', $distribuidorIds);

        if ($request->filled('periodo')) {
            $hoje = Carbon::today();
            if ($request->periodo === 'semana') {
                $query->whereBetween('data', [$hoje->startOfWeek(), $hoje->endOfWeek()]);
            } elseif ($request->periodo === 'mes') {
                $query->whereMonth('data', $hoje->month)->whereYear('data', $hoje->year);
            }
        }

        if ($request->filled('inicio') && $request->filled('fim')) {
            $query->whereBetween('data', [$request->inicio, $request->fim]);
        }

        $vendas = $query->orderByDesc('data')->get();

        // groupBy para pegar rapidamente a última comissão por user
        $comissoes = Commission::whereIn('user_id', $userIds)
            ->orderBy('valid_from')
            ->get()
            ->groupBy('user_id');

        return [$vendas, $comissoes];
    }
}
