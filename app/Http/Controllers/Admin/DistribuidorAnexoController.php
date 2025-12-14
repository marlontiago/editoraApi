<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use App\Models\Distribuidor;
use App\Models\Anexo;
use App\Models\City;
use Carbon\Carbon;
use App\Services\DistribuidorAnexoService;

class DistribuidorAnexoController extends Controller
{
  public function edit(Distribuidor $distribuidor, Anexo $anexo, DistribuidorAnexoService $service)
{
    $service->assertPertenceAoDistribuidor($distribuidor, $anexo);

    $cidades = $service->getCidadesDoDistribuidor($distribuidor);

    return view('admin.distribuidores.anexos.edit', compact('distribuidor', 'anexo', 'cidades'));
}

    public function update(Request $request, Distribuidor $distribuidor, Anexo $anexo, DistribuidorAnexoService $service)
{
    $service->updateFromRequest($request, $distribuidor, $anexo);

    return redirect()
        ->route('admin.distribuidores.show', $distribuidor)
        ->with('success', 'Anexo atualizado com sucesso!');
}


    protected function assertPertenceAoDistribuidor(Distribuidor $distribuidor, Anexo $anexo): void
    {
        if ($anexo->anexavel_type !== Distribuidor::class || (int)$anexo->anexavel_id !== (int)$distribuidor->id) {
            abort(404);
        }
    }
}
