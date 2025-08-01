<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Venda;
use App\Models\Commission;
use Maatwebsite\Excel\Concerns\FromView;
use Carbon\Carbon;

class RelatorioVendasExport implements FromView
{
    public function __construct(private Request $request)
    {
    }

    public function view(): View
    {
        $gestor = Auth::user()->gestor;

        $distribuidorIds = $gestor->distribuidores->pluck('id');
        $userIds         = $gestor->distribuidores->pluck('user_id');

        $query = Venda::with('distribuidor.user')
            ->whereIn('distribuidor_id', $distribuidorIds);

        if ($this->request->filled('periodo')) {
            $hoje = Carbon::today();
            if ($this->request->periodo === 'semana') {
                $query->whereBetween('data', [$hoje->startOfWeek(), $hoje->endOfWeek()]);
            } elseif ($this->request->periodo === 'mes') {
                $query->whereMonth('data', $hoje->month)->whereYear('data', $hoje->year);
            }
        }

        if ($this->request->filled('inicio') && $this->request->filled('fim')) {
            $query->whereBetween('data', [$this->request->inicio, $this->request->fim]);
        }

        $vendas = $query->orderByDesc('data')->get();

        $comissoes = Commission::whereIn('user_id', $userIds)
            ->get()
            ->groupBy('user_id');

        return view('gestor.relatorios.vendas_excel', compact('vendas', 'comissoes'));
    }
}
