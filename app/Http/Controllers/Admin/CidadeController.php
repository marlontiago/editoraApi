<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Distribuidor;
use App\Models\Gestor;
use App\Models\City;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

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

    public function porUf(string $uf, Request $request)
    {
        $withOccupancy = $request->boolean('with_occupancy');

        $cities = City::query()
            ->where('state', strtoupper($uf)) // ajuste conforme teu schema (coluna da UF na tabela cities)
            ->orderBy('name')
            ->get(['id','name']);

        // Se pediu ocupação, marcamos se a cidade já tem distribuidor vinculado
        if ($withOccupancy) {
            // city_distribuidor: city_id x distribuidor_id
            // Traz ocupações agregadas por city_id
            $ocupacoes = DB::table('city_distribuidor')
                ->join('distribuidores','distribuidores.id','=','city_distribuidor.distribuidor_id')
                ->select('city_distribuidor.city_id', DB::raw('MIN(distribuidores.razao_social) as distribuidor_name'))
                ->groupBy('city_distribuidor.city_id')
                ->pluck('distribuidor_name', 'city_distribuidor.city_id');

            $data = $cities->map(function ($c) use ($ocupacoes) {
                $name = $c->name;
                return [
                    'id'                => $c->id,
                    'name'              => $name,
                    'occupied'          => $ocupacoes->has($c->id),
                    'distribuidor_name' => $ocupacoes->get($c->id),
                ];
            })->values();
        } else {
            $data = $cities->map(fn($c) => ['id'=>$c->id, 'name'=>$c->name])->values();
        }

        return response()->json($data);
    }

}
