<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Distribuidor;
use App\Models\Gestor;
use Illuminate\Http\Request;

class CidadeController extends Controller
{
    public function cidadesPorGestor($id)
    {
        $gestor = Gestor::with('cities')->findOrFail($id);
        return response()->json($gestor->cities);
    }

    public function cidadesPorDistribuidor($id)
    {
        $distribuidor = Distribuidor::with('cities')->findOrFail($id);
        return response()->json($distribuidor->cities);
    }
}
