<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Gestor;
use App\Models\Anexo;
use App\Services\GestorAnexoService;

class GestorAnexoController extends Controller
{
    public function show(Gestor $gestor, Anexo $anexo, GestorAnexoService $service)
    {
        $payload = $service->getEditPayload($gestor, $anexo);

        return response()->json([
            'ok' => true,
            'data' => [
                'gestor' => $payload['gestor']->only(['id','razao_social']),
                'anexo' => $payload['anexo'],
                'cidades' => $payload['cidades'],
            ],
        ]);
    }

    public function update(Request $request, Gestor $gestor, Anexo $anexo, GestorAnexoService $service)
    {
        $anexoAtualizado = $service->updateFromRequest($request, $gestor, $anexo);

        return response()->json([
            'ok' => true,
            'message' => 'Anexo atualizado com sucesso!',
            'data' => $anexoAtualizado,
        ]);
    }

    protected function assertPertenceAoGestor(Gestor $gestor, Anexo $anexo): void
    {
            if ($anexo->anexavel_type !== Gestor::class || (int)$anexo->anexavel_id !== (int)$gestor->id) {
                abort(404);
            }
        }
    }
