<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Services\ClienteService;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
    public function __construct(private ClienteService $service)
    {
    }

    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 10);

        $clientes = $this->service->paginateIndex($perPage);

        return response()->json([
            'ok' => true,
            'data' => $clientes->items(),
            'meta' => [
                'current_page' => $clientes->currentPage(),
                'per_page' => $clientes->perPage(),
                'total' => $clientes->total(),
                'last_page' => $clientes->lastPage(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $cliente = $this->service->create($request);

        return response()->json([
            'ok' => true,
            'message' => 'Cliente cadastrado com sucesso!',
            'data' => $cliente,
        ], 201);
    }

    public function show(Cliente $cliente)
    {
        return response()->json([
            'ok' => true,
            'data' => $cliente,
        ]);
    }

    public function update(Request $request, Cliente $cliente)
    {
        $cliente = $this->service->update($request, $cliente);

        return response()->json([
            'ok' => true,
            'message' => 'Cliente atualizado com sucesso!',
            'data' => $cliente,
        ]);
    }

    public function destroy(Cliente $cliente)
    {
        $this->service->delete($cliente);

        return response()->json([
            'ok' => true,
            'message' => 'Cliente excluído com sucesso!',
        ]);
    }
}
