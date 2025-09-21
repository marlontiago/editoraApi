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
     * Se a cidade estiver “ocupada” por algum distribuidor, retorna ocupado=true
     * e os dados do(s) distribuidor(es). Para fins de UI, agregamos em um único nome.
     */
    public function porUf(string $uf, Request $request)
    {
        $uf = strtoupper(trim($uf));

        $rows = DB::table('cities as c')
            ->leftJoin('city_distribuidor as cd', 'cd.city_id', '=', 'c.id')
            ->leftJoin('distribuidores as d', 'd.id', '=', 'cd.distribuidor_id')
            ->whereRaw('UPPER(c.state) = ?', [$uf])
            ->groupBy('c.id', 'c.name')
            ->orderBy('c.name')
            ->get([
                'c.id',
                'c.name',
                DB::raw('MAX(cd.distribuidor_id) as distribuidor_id'),
                DB::raw('MIN(d.razao_social) as distribuidor_nome'),
            ]);

        $data = $rows->map(function ($r) {
            $ocupado = !is_null($r->distribuidor_id);
            return [
                'id'                 => $r->id,
                'name'               => $r->name,
                // CHAVES NO PADRÃO QUE O FRONT ESPERA:
                'occupied'           => $ocupado,
                'distribuidor_name'  => $ocupado ? $r->distribuidor_nome : null,

                // (Opcional) mantém compatibilidade com quem já usa em PT-BR:
                'ocupado'            => $ocupado,
                'distribuidor_nome'  => $ocupado ? $r->distribuidor_nome : null,
            ];
        })->values();

        return response()->json($data);
    }
}
