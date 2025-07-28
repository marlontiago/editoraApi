<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Illuminate\Http\Request;
use App\Models\Venda;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DistribuidorVendasExport implements FromView
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function view(): View
    {
        $distribuidor = Auth::user()->distribuidor;

        $query = Venda::with('produtos')->where('distribuidor_id', $distribuidor->id);

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

        return view('distribuidor.vendas.excel', compact('vendas'));
    }
}