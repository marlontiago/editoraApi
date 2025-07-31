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
        $gestor = Auth::user()->gestor;

        [$vendas, $comissoes] = $this->getVendasFiltradas($request);

        $distribuidores = $gestor->distribuidores()->with('user')->get();

        return view('gestor.relatorios.vendas', [
            'vendas' => $vendas,
            'comissoes' => $comissoes,
            'distribuidores' => $distribuidores,
            'gestor' => $gestor,
        ]);
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

        //Filtro por período (semana ou mês)
        if ($request->filled('periodo')) {
            $hoje = Carbon::now();
            if ($request->periodo === 'semana') {
                $inicioSemana = $hoje->copy()->startOfWeek();
                $fimSemana = $hoje->copy()->endOfWeek();
                $query->whereBetween('data', [$inicioSemana, $fimSemana]);
            } elseif ($request->periodo === 'mes') {
                $query->whereMonth('data', $hoje->month)->whereYear('data', $hoje->year);
            }
        }

        //Filtro por intervalo de datas personalizado
        if ($request->filled('inicio') && $request->filled('fim')) {
            $query->whereBetween('data', [$request->inicio, $request->fim]);
        }

        //Filtro por distribuidor (user_id)
        if ($request->filled('user_id')) {
            $query->whereHas('distribuidor', function ($q) use ($request) {
                $q->where('user_id', $request->user_id);
            });
        }

        $vendas = $query->orderByDesc('data')->get();

        // 🔹 GroupBy para pegar rapidamente a última comissão por user
        $comissoes = Commission::whereIn('user_id', $userIds)
            ->get()
            ->groupBy('user_id');

        return [$vendas, $comissoes];
    }

}
