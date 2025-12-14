<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Anexo;
use App\Models\City;
use App\Models\Distribuidor;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;
use App\Services\DistribuidorAnexoService;


class DistribuidorAnexoController extends Controller
{
    public function show(Distribuidor $distribuidor, Anexo $anexo, DistribuidorAnexoService $service)
{
    $service->assertPertenceAoDistribuidor($distribuidor, $anexo);

    $cidades = $service->getCidadesDoDistribuidor($distribuidor);

    return response()->json([
        'ok' => true,
        'data' => [
            'distribuidor' => [
                'id' => $distribuidor->id,
                'nome' => $distribuidor->nome ?? $distribuidor->razao_social ?? null,
            ],
            'anexo' => $anexo->load('cidade'),
            'cidades' => $cidades,
        ],
    ]);
}

   public function update(Request $request, Distribuidor $distribuidor, Anexo $anexo, DistribuidorAnexoService $service)
{
    $out = $service->updateFromRequest($request, $distribuidor, $anexo);

    return response()->json([
        'ok' => true,
        'message' => 'Anexo atualizado com sucesso!',
        'data' => $out,
    ]);
}

    protected function assertPertenceAoDistribuidor(Distribuidor $distribuidor, Anexo $anexo): void
    {
        if ($anexo->anexavel_type !== Distribuidor::class || (int)$anexo->anexavel_id !== (int)$distribuidor->id) {
            abort(404);
        }
    }
}
