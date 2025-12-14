<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotaFiscal;
use App\Models\NotaPagamento;
use Illuminate\Http\Request;
use App\Services\NotaPagamentoService;

class NotaPagamentoController extends Controller
{
    public function create(NotaFiscal $nota, NotaPagamentoService $service)
    {
        try {
            $payload = $service->getCreatePayload($nota);

            return view('admin.notas.pagamentos.create', $payload);
        } catch (\Throwable $e) {
            return redirect()
                ->route('admin.notas.show', $nota)
                ->with('error', $e->getMessage());
        }
    }

    public function store(Request $request, NotaFiscal $nota, NotaPagamentoService $service)
    {
        try {
            $data = $request->validate($service->rules());

            $service->store($nota, $data);

            return redirect()
                ->route('admin.notas.show', $nota)
                ->with('success', 'Pagamento registrado com sucesso!');
        } catch (\Throwable $e) {
            // mantém o mesmo "comportamento" de voltar com input
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function show(NotaFiscal $nota, NotaPagamento $pagamento, NotaPagamentoService $service)
    {
        try {
            $payload = $service->getShowPayload($nota, $pagamento);

            return view('admin.notas.pagamentos.show', $payload);
        } catch (\Throwable $e) {
            abort(404);
        }
    }


}
