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
    /**
     * (Opcional) Mantido por compatibilidade.
     * Agora o gestor NÃO trava mais a UF/cidade. Use apenas se quiser listar
     * as cidades da UF do gestor como atalho.
     */
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

        // Fallback via pivot (se você mantiver esse vínculo manual)
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
    public function cidadesPorDistribuidor($id)
    {
        $distribuidor = Distribuidor::with('cities')->findOrFail($id);

        return response()->json(
            $distribuidor->cities
                ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])
                ->values()
        );
    }

    /**
     * Lista cidades por UF SEM travar UF por gestor e informando “ocupação”.
     * Mantido por compatibilidade com a tela antiga (select por UF).
     */
    public function porUf(string $uf, Request $request)
    {
        $uf = strtoupper(trim($uf));

        $rows = DB::table('cities as c')
            ->leftJoin('city_distribuidor as cd', 'cd.city_id', '=', 'c.id')
            ->leftJoin('distribuidores as d', 'd.id', '=', 'cd.distribuidor_id')
            ->whereRaw('UPPER(c.state) = ?', [$uf])
            ->groupBy('c.id', 'c.name', 'c.state')
            ->orderBy('c.name')
            ->get([
                'c.id',
                'c.name',
                'c.state',
                DB::raw('MAX(cd.distribuidor_id) as distribuidor_id'),
                DB::raw('MIN(d.razao_social) as distribuidor_nome'),
            ]);

        $data = $rows->map(function ($r) {
            $ocupado = !is_null($r->distribuidor_id);
            return [
                'id'                 => $r->id,
                'name'               => $r->name,
                'uf'                 => $r->state,
                // padrão do front novo:
                'occupied'           => $ocupado,
                'distribuidor_id'    => $ocupado ? (int)$r->distribuidor_id : null,
                'distribuidor_name'  => $ocupado ? $r->distribuidor_nome : null,
                // compat antigo (PT-BR):
                'ocupado'            => $ocupado,
                'distribuidor_nome'  => $ocupado ? $r->distribuidor_nome : null,
            ];
        })->values();

        return response()->json($data);
    }

    /**
     * NOVO: Busca global por cidades (qualquer UF), com flag de ocupação.
     * GET /admin/cidades/busca?q=curi&uf=PR&with_occupancy=1&limit=20
     * Retorna [{id,name,uf,occupied,distribuidor_id,distribuidor_name}]
     */
    public function search(Request $request)
    {
        $q  = trim((string) $request->query('q', ''));
        $uf = strtoupper(trim((string) $request->query('uf', '')));
        $withOcc = (bool) $request->boolean('with_occupancy', false);
        $limit = min((int)$request->integer('limit', 50), 100);

        // se não tem UF e o termo tem < 2 chars, não busque nada (evita seq scan gigante)
        if ($uf === '' && mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $query = DB::table('cities')->select('id','name','state');

        if ($uf !== '')  $query->where('state', $uf);
        if ($q !== '')   $query->where('name', 'ILIKE', "%{$q}%");

        $rows = $query->orderBy('name')->limit($limit)->get();

        // Ocupação (opcional)
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
            'uf' => $r->state,             // compatibilidade com o que o front espera
            'occupied' => isset($occupiedMap[$r->id]),
            'distribuidor_name' => $occupiedMap[$r->id] ?? null,
        ]);

        return response()->json($out);
    }


}
