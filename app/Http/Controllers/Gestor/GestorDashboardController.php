<?php

namespace App\Http\Controllers\Gestor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class GestorDashboardController extends Controller
{
     public function index()
    {
        $gestor = auth()->user()->gestor; // precisa da relação user->gestor
        $qtdDistribuidores = $gestor?->distribuidores()->count() ?? 0;

        return view('gestor.dashboard', compact('qtdDistribuidores'));
    }

    public function comissoes()
    {
        $gestor = auth()->user()->gestor;

        // Busca os IDs dos distribuidores do gestor
        $distribuidorIds = $gestor->distribuidores()->pluck('id');

        // Busca percentual de comissão do gestor (ou 0 se não configurado)
        $percentualComissao = optional(
            \App\Models\Comissao::where('tipo', 'gestor')->first()
        )->percentual ?? 0;

        // Soma das comissões acumuladas sobre as vendas dos distribuidores
        $totalVendas = \App\Models\Venda::whereIn('distribuidor_id', $distribuidorIds)->sum('valor_total');
        $totalComissao = $totalVendas * ($percentualComissao / 100);

        return view('gestor.comissoes.index', compact('totalVendas', 'totalComissao', 'percentualComissao'));
    }

}
