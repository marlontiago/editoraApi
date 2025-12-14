<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Advogado;
use App\Services\AdvogadoService;
use Illuminate\Http\Request;

class AdvogadoController extends Controller
{
    public function __construct(private AdvogadoService $service)
    {
    }

    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 10);

        $advogados = $this->service->paginateIndex($perPage);

        return response()->json([
            'ok' => true,
            'data' => $advogados->items(),
            'meta' => [
                'current_page' => $advogados->currentPage(),
                'per_page'     => $advogados->perPage(),
                'total'        => $advogados->total(),
                'last_page'    => $advogados->lastPage(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $dados = $request->validate($this->service->rules());

        $advogado = $this->service->create($dados);

        return response()->json([
            'ok' => true,
            'message' => 'Advogado criado com sucesso.',
            'data' => $advogado,
        ], 201);
    }

    public function show(Advogado $advogado)
    {
        return response()->json([
            'ok' => true,
            'data' => $advogado,
        ]);
    }

    public function update(Request $request, Advogado $advogado)
    {
        $dados = $request->validate($this->service->rules());

        $advogado = $this->service->update($advogado, $dados);

        return response()->json([
            'ok' => true,
            'message' => 'Advogado atualizado com sucesso.',
            'data' => $advogado,
        ]);
    }

    public function destroy(Advogado $advogado)
    {
        $this->service->delete($advogado);

        return response()->json([
            'ok' => true,
            'message' => 'Advogado excluído.',
        ]);
    }
}
