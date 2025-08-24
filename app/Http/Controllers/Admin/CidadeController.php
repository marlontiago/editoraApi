<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Distribuidor;
use App\Models\Gestor;
use App\Models\City;

class CidadeController extends Controller
{
    public function cidadesPorGestor(Gestor $gestor)
    {
        // Usa o campo correto da tabela gestores
        $ufGestor = $gestor->estado_uf;

        if ($ufGestor) {
            $cidades = City::query()
                ->select('id', 'name')
                ->whereRaw('UPPER(state) = ?', [strtoupper($ufGestor)])
                ->orderBy('name')
                ->get();

            if ($cidades->isNotEmpty()) {
                return response()->json($cidades);
            }
        }

        // Fallback via pivot (se quiser manter vínculo manual de cidades x gestor)
        $cidades = City::query()
            ->select('cities.id', 'cities.name')
            ->join('city_gestor', 'city_gestor.city_id', '=', 'cities.id')
            ->where('city_gestor.gestor_id', $gestor->id)
            ->orderBy('cities.name')
            ->get();

        return response()->json($cidades);
    }

    public function cidadesPorDistribuidor($id)
    {
        $distribuidor = Distribuidor::with('cities')->findOrFail($id);

        return response()->json(
            $distribuidor->cities
                ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])
                ->values()
        );
    }

    public function porUf(string $uf)
    {
        
        $uf = strtoupper(substr($uf, 0, 2));
        return City::where('state', $uf)->orderBy('name')->get(['id','name']);
    }

}
