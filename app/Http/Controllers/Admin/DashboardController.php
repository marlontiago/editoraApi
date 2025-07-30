<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Gestor;
use App\Models\Produto;
use App\Models\Venda;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $totalProdutos = Produto::count();
        $totalGestores = Gestor::count();
        $vendas = Venda::with('distribuidor.user', 'distribuidor.gestor.user')->get();

        return view('admin.dashboard', [
            'vendas' => $vendas,
            'totalGestores' => $totalGestores,
            'totalProdutos' => $totalProdutos,
        ]);
    }
}
