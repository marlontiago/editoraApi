<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Distribuidor;
use App\Models\Gestor;
use App\Models\City;

class CidadeController extends Controller
{
   
    public function cidadesPorGestor(Gestor $gestor)
    {
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

        $cidades = City::query()
            ->select('cities.id', 'cities.name')
            ->join('city_gestor', 'city_gestor.city_id', '=', 'cities.id')
            ->where('city_gestor.gestor_id', $gestor->id)
            ->orderBy('cities.name')
            ->get();

        return response()->json($cidades);
    }

    /**
     * Prioridade máxima: quando há um distribuidor escolhido,
     * listar SOMENTE as cidades onde ele atua.
     */
    public function porDistribuidor(Distribuidor $distribuidor)
{
    $cidades = $distribuidor->cities()
        ->select('cities.id','cities.name','cities.state')
        ->get()
        ->map(fn($c) => [
            'id'   => $c->id,
            'name' => $c->name,
            'state'=> $c->state,     // <— AQUI
            // para manter compatibilidade com seu front:
            'ocupado'           => false,
            'distribuidor_nome' => null,
        ]);

    return response()->json($cidades);
}

    /**
     * Lista cidades por UF SEM travar UF por gestor e informando “ocupação”.
     */
    public function porUf(string $uf, Request $request)
{
    $withOcc = $request->boolean('with_occupancy');

    $base = \App\Models\City::where('state', $uf)
        ->select('id','name','state');

    $cidades = $base->get()->map(function($c) use ($withOcc) {
        $ocupado = false;
        $distNome = null;

        if ($withOcc) {
            $row = DB::table('city_distribuidor')
                ->join('distribuidores','distribuidores.id','=','city_distribuidor.distribuidor_id')
                ->where('city_distribuidor.city_id', $c->id)
                ->select('distribuidores.razao_social as dist_nome')
                ->first();

            $ocupado  = (bool) $row;
            $distNome = $row->dist_nome ?? null;
        }

        return [
            'id'                => $c->id,
            'name'              => $c->name,
            'state'             => $c->state, // <— AQUI
            'ocupado'           => $ocupado,
            'distribuidor_nome' => $distNome,
        ];
    });

    return response()->json($cidades);
}

    public function search(Request $request)
    {
        $q  = trim((string) $request->query('q', ''));
        $uf = strtoupper(trim((string) $request->query('uf', '')));
        $withOcc = (bool) $request->boolean('with_occupancy', false);
        $limit = min((int)$request->integer('limit', 50), 100);

        if ($uf === '' && mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $query = DB::table('cities')->select('id','name','state');

        if ($uf !== '')  $query->where('state', $uf);
        if ($q !== '')   $query->where('name', 'ILIKE', "%{$q}%");

        $rows = $query->orderBy('name')->limit($limit)->get();

        $occupiedMap = [];
        if ($withOcc && $rows->isNotEmpty()) {
            $ids = $rows->pluck('id');
            $occ = DB::table('city_distribuidor')
                ->join('distribuidores','distribuidores.id','=','city_distribuidor.distribuidor_id')
                ->whereIn('city_distribuidor.city_id', $ids)
                ->select('city_distribuidor.city_id','distribuidores.razao_social')
                ->get();
            foreach ($occ as $o) $occupiedMap[$o->city_id] = $o->razao_social;
        }

        $out = $rows->map(fn($r) => [
            'id' => $r->id,
            'name' => $r->name,
            'state' => $r->state,
            'uf' => $r->state,            
            'occupied' => isset($occupiedMap[$r->id]),
            'distribuidor_name' => $occupiedMap[$r->id] ?? null,
        ]);

        return response()->json($out);
    }


}
