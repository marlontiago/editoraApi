<?php

namespace App\Http\Controllers\Distribuidor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Venda;
use App\Models\Commission;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $distribuidor = Auth::user()->distribuidor;
        

        // Busca percentual atual do distribuidor
        $percentual = Commission::where('user_id', Auth::id())
            ->latest()
            ->value('percentage') ?? 0;

        // Aplica os mesmos filtros do relatório
        $vendasQuery = Venda::with('produtos')
            ->where('distribuidor_id', $distribuidor->id);

        if ($request->filled('periodo')) {
            $hoje = Carbon::today();
            if ($request->periodo === 'semana') {
                $inicioSemana = Carbon::now()->startOfWeek(Carbon::MONDAY)->toDateString();
                $fimSemana = Carbon::now()->endOfWeek(Carbon::SUNDAY)->toDateString();
                $vendasQuery->whereBetween('data', [$inicioSemana, $fimSemana]);
            } elseif ($request->periodo === 'mes') {
                $vendasQuery->whereMonth('data', $hoje->month)->whereYear('data', $hoje->year);
            }
        }

        if ($request->filled('inicio') && $request->filled('fim')) {
            $vendasQuery->whereBetween('data', [$request->inicio, $request->fim]);
        }

        $vendas = $vendasQuery->orderByDesc('data')->take(10)->get();

        // Totais (com base no resultado filtrado mostrado no dashboard)
        $totalVendas   = $vendas->sum('valor_total');
        $totalComissao = $vendas->sum(fn ($v) => ($percentual / 100) * $v->valor_total);

        return view('distribuidor.dashboard', compact(
            'vendas',
            'totalVendas',
            'totalComissao',
            'percentual'
        ));
    }
}