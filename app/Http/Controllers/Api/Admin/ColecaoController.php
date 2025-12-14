<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Colecao;
use App\Services\ColecaoService;
use Illuminate\Http\Request;

class ColecaoController extends Controller
{
    public function __construct(private ColecaoService $service)
    {
    }

    // POST /api/admin/colecoes/quick-create
    public function quickCreate(Request $request)
    {
        $data = $request->validate($this->service->rulesQuickCreate());

        $colecao = $this->service->quickCreate($data);

        return response()->json([
            'ok' => true,
            'message' => 'Coleção criada e produtos vinculados com sucesso!',
            'data' => $colecao,
        ], 201);
    }

    // DELETE /api/admin/colecoes/{colecao}
    public function destroy(Colecao $colecao)
    {
        $this->service->delete($colecao);

        return response()->json([
            'ok' => true,
            'message' => 'Coleção excluída. Produtos vinculados foram mantidos sem coleção.',
        ]);
    }
}
