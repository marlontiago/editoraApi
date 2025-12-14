<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use App\Services\PedidoService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PedidoController extends Controller
{
    public function index(Request $request, PedidoService $service)
    {
        $perPage = (int) $request->get('per_page', 10);
        $perPage = ($perPage > 0 && $perPage <= 100) ? $perPage : 10;

        $pedidos = $service->indexQuery()->paginate($perPage)->withQueryString();

        return response()->json(['ok' => true, 'data' => $pedidos]);
    }

    public function create(PedidoService $service)
    {
        return response()->json(['ok' => true, 'data' => $service->createPayload()]);
    }

    public function store(Request $request, PedidoService $service)
    {
        try {
            $pedido = $service->store($request);
            return response()->json([
                'ok' => true,
                'message' => 'Pedido criado com sucesso!',
                'data' => $pedido,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json(['ok' => false, 'errors' => $e->errors()], 422);
        }
    }

    public function show(Pedido $pedido, PedidoService $service)
    {
        return response()->json(['ok' => true, 'data' => $service->showPayload($pedido)]);
    }

    public function edit(Pedido $pedido, PedidoService $service)
    {
        // payload igual ao create + dados do pedido
        $pedido->load(['cidades','produtos','cliente','gestor','distribuidor.user']);
        return response()->json([
            'ok' => true,
            'data' => [
                'pedido' => $pedido,
                'payload' => $service->createPayload(),
            ],
        ]);
    }

    public function update(Pedido $pedido, Request $request, PedidoService $service)
    {
        try {
            $pedidoAtualizado = $service->update($pedido, $request);

            return response()->json([
                'ok' => true,
                'message' => 'Pedido atualizado com sucesso!',
                'data' => $pedidoAtualizado,
            ]);
        } catch (ValidationException $e) {
            return response()->json(['ok' => false, 'errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
