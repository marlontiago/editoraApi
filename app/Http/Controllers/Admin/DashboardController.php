<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Gestor;
use App\Models\Produto;
use App\Models\Venda;
use App\Models\Commission;
use App\Models\User;


class DashboardController extends Controller
{
    public function index()
{
    $totalProdutos = Produto::count();
    $totalGestores = Gestor::count();
    $totalUsuarios = User::count();

    $vendas = Venda::with('distribuidor.user', 'distribuidor.gestor.user')->get();

    $vendas->each(function ($venda) {
        $distribuidorUserId = optional($venda->distribuidor->user)->id;
        $gestorUserId = optional($venda->distribuidor->gestor->user)->id;

        $comissaoDistribuidor = Commission::where('user_id', $distribuidorUserId)
            ->where('tipo_usuario', 'distribuidor')
            ->value('percentage') ?? 0;

        $comissaoGestor = Commission::where('user_id', $gestorUserId)
            ->where('tipo_usuario', 'gestor')
            ->value('percentage') ?? 0;

        $venda->comissao_distribuidor = $comissaoDistribuidor;
        $venda->valor_comissao_distribuidor = ($comissaoDistribuidor / 100) * $venda->valor_total;

        $venda->comissao_gestor = $comissaoGestor;
        $venda->valor_comissao_gestor = ($comissaoGestor / 100) * $venda->valor_total;
    });

    return view('admin.dashboard', [
        'vendas' => $vendas,
        'totalGestores' => $totalGestores,
        'totalProdutos' => $totalProdutos,
        'totalUsuarios' => $totalUsuarios,
    ]);
}
}
