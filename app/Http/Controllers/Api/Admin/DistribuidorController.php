<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Distribuidor;
use App\Models\Gestor;
use App\Models\User;
use App\Models\Anexo;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use App\Services\DistribuidorService;


class DistribuidorController extends Controller
{
   public function index(Request $request, DistribuidorService $service)
    {
        $distribuidores = $service->index($request);

        return response()->json([
            'ok' => true,
            'data' => $distribuidores,
        ]);
    }

    public function store(Request $request, DistribuidorService $service)
    {
        $distribuidor = $service->createFromRequest($request);

        return response()->json([
            'ok' => true,
            'message' => 'Distribuidor criado com sucesso!',
            'data' => $distribuidor,
        ], 201);
    }

    public function show(Distribuidor $distribuidor, DistribuidorService $service)
    {
        return response()->json([
            'ok' => true,
            'data' => $service->show($distribuidor),
        ]);
    }

    public function update(Request $request, Distribuidor $distribuidor, DistribuidorService $service)
    {
        $distribuidor = $service->updateFromRequest($request, $distribuidor);

        return response()->json([
            'ok' => true,
            'message' => 'Distribuidor atualizado com sucesso!',
            'data' => $distribuidor,
        ]);
    }

    public function destroy(Distribuidor $distribuidor, DistribuidorService $service)
    {
        $service->delete($distribuidor);

        return response()->json([
            'ok' => true,
            'message' => 'Distribuidor removido com sucesso!',
        ]);
    }

    public function destroyAnexo(Distribuidor $distribuidor, Anexo $anexo, DistribuidorService $service)
    {
        $service->deleteAnexo($distribuidor, $anexo);

        return response()->json([
            'ok' => true,
            'message' => 'Anexo excluído com sucesso.',
        ]);
    }

    public function ativarAnexo(Distribuidor $distribuidor, Anexo $anexo, DistribuidorService $service)
    {
        $distribuidor = $service->ativarAnexo($distribuidor, $anexo);

        return response()->json([
            'ok' => true,
            'message' => 'Contrato/aditivo ativado e percentual/vencimento aplicados.',
            'data' => $distribuidor,
        ]);
    }

    public function cidadesPorUfs(Request $request, DistribuidorService $service)
    {
        return response()->json($service->cidadesPorUfs($request));
    }

    public function cidadesPorGestor(Request $request, DistribuidorService $service)
    {
        return response()->json($service->cidadesPorGestor($request));
    }

    public function porGestor(Gestor $gestor, DistribuidorService $service)
    {
        return response()->json([
            'ok' => true,
            'data' => $service->porGestor($gestor),
        ]);
    }

    /**
     * Descobre a coluna de UF na tabela cities (uf, state, estado, etc).
     */
    private function cityUfColumn(): ?string
    {
        foreach (['uf','state','estado','state_code','uf_code','sigla_uf','uf_sigla'] as $col) {
            if (Schema::hasColumn('cities', $col)) return $col;
        }
        return null;
    }
}
