<?php

namespace App\Http\Controllers\Gestor;

use App\Http\Controllers\Controller;
use App\Models\Distribuidor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $gestor = Auth::user()->gestor;
        $qtdDistribuidores = $gestor->distribuidores()->count();

        return view('gestor.dashboard', compact('qtdDistribuidores'));
    }
}
