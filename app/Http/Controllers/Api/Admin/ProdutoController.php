<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Produto;
use App\Services\ProdutoService;
use Illuminate\Http\Request;

class ProdutoController extends Controller
{
    public function index(Request $request, ProdutoService $service)
    {
        $perPage = (int) $request->get('per_page', 15);
        $perPage = ($perPage > 0 && $perPage <= 100) ? $perPage : 15;

        $query = $service->indexQuery($request);

        return response()->json([
            'ok' => true,
            'data' => $query->paginate($perPage)->withQueryString(),
        ]);
    }

    public function store(Request $request, ProdutoService $service)
    {
        $produto = $service->store($request);

        return response()->json([
            'ok' => true,
            'message' => 'Produto criado com sucesso.',
            'data' => $produto,
        ], 201);
    }

    public function update(Request $request, Produto $produto, ProdutoService $service)
    {
        $produto = $service->update($request, $produto);

        return response()->json([
            'ok' => true,
            'message' => 'Produto atualizado com sucesso.',
            'data' => $produto,
        ]);
    }

    public function destroy(Produto $produto, ProdutoService $service)
    {
        $service->destroy($produto);

        return response()->json([
            'ok' => true,
            'message' => 'Produto removido com sucesso.',
        ]);
    }

    public function import(Request $request, ProdutoService $service)
    {
        $result = $service->import($request);

        return response()->json(
            $result,
            $result['ok'] ? 200 : 422
        );
    }
}
