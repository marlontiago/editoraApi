<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotaFiscal;
use App\Models\NotaPagamento;
use App\Services\NotaPagamentoService;
use Illuminate\Http\Request;

class NotaPagamentoController extends Controller
{
    public function create(NotaFiscal $nota, NotaPagamentoService $service)
    {
        try {
            $payload = $service->getCreatePayload($nota);

            // OBS: payload tem models/collections; ok para JSON
            return response()->json([
                'ok' => true,
                'data' => [
                    'nota'             => $payload['nota'],
                    'advogados'        => $payload['advogados'],
                    'diretores'        => $payload['diretores'],
                    'percGestor'       => $payload['percGestor'],
                    'percDistribuidor' => $payload['percDistribuidor'],
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function store(Request $request, NotaFiscal $nota, NotaPagamentoService $service)
    {
        try {
            $data = $request->validate($service->rules());

            $pagamento = $service->store($nota, $data);

            return response()->json([
                'ok' => true,
                'message' => 'Pagamento registrado com sucesso!',
                'data' => [
                    'pagamento' => $pagamento,
                ],
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function show(NotaFiscal $nota, NotaPagamento $pagamento, NotaPagamentoService $service)
    {
        try {
            $payload = $service->getShowPayload($nota, $pagamento);

            return response()->json([
                'ok' => true,
                'data' => $payload,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'message' => 'Not found',
            ], 404);
        }
    }
}
