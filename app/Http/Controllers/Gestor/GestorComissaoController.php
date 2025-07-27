<?php

namespace App\Http\Controllers\Gestor;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class GestorComissaoController extends Controller
{
    public function index()
    {
        $gestor = Auth::user()->gestor;

        $distribuidores = $gestor->distribuidores()->with(['vendas.produto'])->get();

        $totalComissao = $distribuidores->flatMap->vendas->sum('comissao');

        return view('gestor.comissoes.index', compact('distribuidores', 'totalComissao'));
    }
}
