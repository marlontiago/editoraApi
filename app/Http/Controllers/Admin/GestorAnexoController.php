<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Gestor;
use App\Models\Anexo;

use App\Services\GestorAnexoService;

class GestorAnexoController extends Controller
{
    public function edit(Gestor $gestor, Anexo $anexo, GestorAnexoService $service)
    {
        $payload = $service->getEditPayload($gestor, $anexo);
        return view('admin.gestores.anexos.edit', $payload);
    }

    public function update(Request $request, Gestor $gestor, Anexo $anexo, GestorAnexoService $service)
    {
        $service->updateFromRequest($request, $gestor, $anexo);

        return redirect()
            ->route('admin.gestores.show', $gestor)
            ->with('success', 'Anexo atualizado com sucesso!');
    }

    protected function assertPertenceAoGestor(Gestor $gestor, Anexo $anexo): void
    {
        if ($anexo->anexavel_type !== Gestor::class || (int)$anexo->anexavel_id !== (int)$gestor->id) {
            abort(404);
        }
    }
}
