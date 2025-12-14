<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use App\Models\NotaFiscal;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\NotaFiscalService;

class NotaFiscalController extends Controller
{
    public function emitir(Request $request, Pedido $pedido, NotaFiscalService $service)
    {
        try {
            $service->emitir($pedido);
            return back()->with('success', 'Pré-visualização emitida com sucesso. Status do pedido alterado para Pré-aprovado.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }



    public function faturar(NotaFiscal $nota, Request $request, NotaFiscalService $service)
{
    $modo = $request->string('modo_faturamento')->lower()->toString();

    try {
        $service->faturar($nota, $modo);
        return back()->with('success', 'Nota faturada com sucesso.');
    } catch (\Throwable $e) {
        return back()->with('error', $e->getMessage());
    }
}


    public function show(NotaFiscal $nota)
    {
        $nota->load([
            'itens.produto',
            'pedido.cliente',
            'pagamentos',
        ]);

        $pagamentoAtual = $nota->pagamentos->sortByDesc('id')->first();

        return view('admin.notas.show', compact('nota', 'pagamentoAtual'));
    }

    public function pdf(NotaFiscal $nota)
    {
        $nota->load(['itens.produto', 'pedido.cliente']);

        $cli = $nota->pedido?->cliente;
        $cliEndereco = trim(($cli->endereco ?? '')
            . ($cli->numero ? ', '.$cli->numero : '')
            . ($cli->complemento ? ' - '.$cli->complemento : ''));
        $cliMunUf = trim(($cli->cidade ?? '').'/'.($cli->uf ?? ''));

        $pdf = Pdf::loadView('admin.notas.pdf', [
            'nota'           => $nota,
            'cliEndereco'    => $cliEndereco,
            'cliMunUf'       => $cliMunUf,
        ])->setPaper('a4');

        return $pdf->stream("Nota-{$nota->numero}.pdf");
    }
}
