<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use App\Models\NotaFiscal;
use App\Services\NotaFiscalService;
use Illuminate\Http\Request;

class NotaFiscalController extends Controller
{
    public function emitir(Pedido $pedido, NotaFiscalService $service)
    {
        try {
            $nota = $service->emitir($pedido);

            return response()->json([
                'ok' => true,
                'message' => 'Pré-visualização emitida com sucesso.',
                'data' => [
                    'nota_id' => $nota->id,
                    'numero'  => $nota->numero,
                    'status'  => $nota->status,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function faturar(NotaFiscal $nota, Request $request, NotaFiscalService $service)
    {
        $modo = $request->string('modo_faturamento')->lower()->toString();

        try {
            $nota = $service->faturar($nota, $modo);

            return response()->json([
                'ok' => true,
                'message' => 'Nota faturada com sucesso.',
                'data' => [
                    'nota_id'           => $nota->id,
                    'numero'            => $nota->numero,
                    'status'            => $nota->status,
                    'status_financeiro' => $nota->status_financeiro,
                    'faturada_em'       => $nota->faturada_em,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function pdf(\App\Models\NotaFiscal $nota)
    {
        $nota->load(['itens.produto', 'pedido.cliente']);

        $cli = $nota->pedido?->cliente;
        $cliEndereco = trim(($cli->endereco ?? '')
            . ($cli->numero ? ', '.$cli->numero : '')
            . ($cli->complemento ? ' - '.$cli->complemento : ''));

        $cliMunUf = trim(($cli->cidade ?? '').'/'.($cli->uf ?? ''));

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.notas.pdf', [
            'nota'        => $nota,
            'cliEndereco' => $cliEndereco,
            'cliMunUf'    => $cliMunUf,
        ])->setPaper('a4');

        // Para API, geralmente é melhor "download" do que "stream"
        return $pdf->download("Nota-{$nota->numero}.pdf");
    }

    public function show(NotaFiscal $nota)
    {
        $nota->load(['itens.produto', 'pedido.cliente', 'pagamentos']);

        return response()->json([
            'ok' => true,
            'data' => $nota,
        ]);
    }

    // PDF via API: opcional.
    // Se quiser, você pode retornar uma URL assinada, ou um download do PDF.
}
