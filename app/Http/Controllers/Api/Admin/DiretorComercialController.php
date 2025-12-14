<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\DiretorComercial;
use App\Services\DiretorComercialService;
use Illuminate\Http\Request;

class DiretorComercialController extends Controller
{
    public function __construct(private DiretorComercialService $service)
    {
    }

    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 10);

        $diretores = $this->service->paginateIndex($perPage);

        return response()->json([
            'ok' => true,
            'data' => $diretores->items(),
            'meta' => [
                'current_page' => $diretores->currentPage(),
                'per_page'     => $diretores->perPage(),
                'total'        => $diretores->total(),
                'last_page'    => $diretores->lastPage(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $dados = $request->validate($this->service->rules());

        $diretor = $this->service->create($dados);

        return response()->json([
            'ok' => true,
            'message' => 'Diretor Comercial criado com sucesso.',
            'data' => $diretor,
        ], 201);
    }

    public function show(DiretorComercial $diretor_comercial)
    {
        return response()->json([
            'ok' => true,
            'data' => $diretor_comercial,
        ]);
    }

    public function update(Request $request, DiretorComercial $diretor_comercial)
    {
        $dados = $request->validate($this->service->rules());

        $diretor = $this->service->update($diretor_comercial, $dados);

        return response()->json([
            'ok' => true,
            'message' => 'Diretor Comercial atualizado com sucesso.',
            'data' => $diretor,
        ]);
    }

    public function destroy(DiretorComercial $diretor_comercial)
    {
        $this->service->delete($diretor_comercial);

        return response()->json([
            'ok' => true,
            'message' => 'Diretor Comercial excluído.',
        ]);
    }
}
