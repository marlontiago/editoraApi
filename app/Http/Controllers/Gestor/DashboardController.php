<?php

namespace App\Http\Controllers\Gestor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Venda;
use App\Models\Commission;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $gestor = $user->gestor;
        $distribuidores = $gestor ? $gestor->distribuidores()->with('user')->get() : collect();
        $distribuidorIds = $distribuidores->pluck('id');

        // Vendas feitas pelos distribuidores do gestor
        $vendas = Venda::with(['distribuidor.user'])
            ->whereIn('distribuidor_id', $distribuidorIds)
            ->orderByDesc('data')
            ->get();

        // Comissões dos distribuidores (último percentual ativo por user_id)
        

        // Totalizadores
        $totalVendas = $vendas->count();
        $totalComissao = 0;

        foreach ($vendas as $venda) {
            $userId = $venda->distribuidor->user_id;
            $percentual = optional(optional($comissoes[$userId] ?? null)->last())->percentage ?? 0;
            $totalComissao += ($percentual / 100) * $venda->valor_total;
        }

        return view('gestor.dashboard', compact('distribuidores', 'vendas', 'totalVendas', 'totalComissao'));
    }
}