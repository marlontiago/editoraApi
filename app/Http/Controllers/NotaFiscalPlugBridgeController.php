<?php

namespace App\Http\Controllers;

use App\Models\NotaFiscal;
use App\Services\PlugNotasService;

class NotaFiscalPlugBridgeController extends Controller
{
    public function emitir(NotaFiscal $nota, PlugNotasService $pn, NotaFiscalPlugNotasController $api)
    {
        if ($nota->plugnotas_id) {
            return back()->with('error', 'Esta NF já foi enviada à PlugNotas.');
        }

        $resp = $api->emitir($nota, $pn);

        if ($resp->status() >= 400) {
            $data = $resp->getData(true) ?? [];
            $msg  = data_get($data, 'message') ?: data_get($data, 'error.message') ?: 'Falha ao emitir.';
            return back()->with('error', $msg);
        }

        return back()->with('success', 'Nota enviada para processamento na SEFAZ. Clique em "Consultar status" em alguns segundos.');
    }

    public function consultar(NotaFiscal $nota, PlugNotasService $pn, NotaFiscalPlugNotasController $api)
    {
        if (!$nota->plugnotas_id) {
            return back()->with('error', 'Esta NF ainda não foi enviada à PlugNotas.');
        }

        $resp = $api->consultar($nota, $pn);

        if ($resp->status() >= 400) {
            return back()->with('error', 'Falha ao consultar status.');
        }

        $arr = $resp->getData(true);
        $doc = is_array($arr) ? ($arr[0] ?? []) : $arr;
        $status = data_get($doc, 'status');

        return back()->with('success', 'Status: '.($status ?: '—'));
    }

    public function pdf(NotaFiscal $nota, PlugNotasService $pn, NotaFiscalPlugNotasController $api)
    {
        if (!$nota->plugnotas_id) {
            return back()->with('error', 'NF sem plugnotas_id.');
        }
        return $api->pdf($nota, $pn);
    }

    public function xml(NotaFiscal $nota, PlugNotasService $pn, NotaFiscalPlugNotasController $api)
    {
        if (!$nota->plugnotas_id) {
            return back()->with('error', 'NF sem plugnotas_id.');
        }
        return $api->xml($nota, $pn);
    }
}
