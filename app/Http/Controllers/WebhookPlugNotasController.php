<?php
namespace App\Http\Controllers;

use App\Models\NotaFiscal;
use App\Services\PlugNotasService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookPlugNotasController extends Controller
{
    public function handle(Request $r, PlugNotasService $pn)
    {
        // 1) Segurança: confirme um header secreto simples
        $secret = env('PLUGNOTAS_WEBHOOK_SECRET');
        if ($secret && $r->header('X-Webhook-Token') !== $secret) {
            Log::warning('Webhook PlugNotas rejeitado: token inválido');
            abort(401, 'Unauthorized webhook');
        }

        // 2) Capture o payload
        $payload = $r->all();
        Log::info('PLUG_WEBHOOK', $payload);

        // Estruturas possíveis:
        // - { id, status, chave, numero, serie, links: {pdf, xml}, ... }
        // - às vezes vem dentro de "documento" / "data", etc.
        $id         = data_get($payload, 'id') ?? data_get($payload, 'documento.id') ?? data_get($payload, 'data.id');
        $status     = data_get($payload, 'status') ?? data_get($payload, 'documento.status') ?? data_get($payload, 'data.status');
        $chave      = data_get($payload, 'chave') ?? data_get($payload, 'documento.chave') ?? data_get($payload, 'data.chave');
        $numero     = data_get($payload, 'numero') ?? data_get($payload, 'documento.numero') ?? data_get($payload, 'data.numero');
        $serie      = data_get($payload, 'serie')  ?? data_get($payload, 'documento.serie')  ?? data_get($payload, 'data.serie');
        $protocolo  = data_get($payload, 'protocolo') ?? data_get($payload, 'documento.protocolo') ?? data_get($payload, 'data.protocolo');
        $pdf        = data_get($payload, 'links.pdf') ?? data_get($payload, 'documento.links.pdf') ?? data_get($payload, 'data.links.pdf');
        $xml        = data_get($payload, 'links.xml') ?? data_get($payload, 'documento.links.xml') ?? data_get($payload, 'data.links.xml');
        $cStat      = (int) data_get($payload, 'cStat', 0);

        if (!$id) {
            Log::warning('Webhook sem ID de documento', $payload);
            return response()->json(['ok' => true]); // evita re-tentativas infinitas
        }

        // 3) Ache a nota pelo plugnotas_id
        $nota = NotaFiscal::where('plugnotas_id', $id)->first();
        if (!$nota) {
            Log::warning('Webhook: NotaFiscal não encontrada para plugnotas_id', ['id' => $id]);
            return response()->json(['ok' => true]);
        }

        // 4) Atualize os campos
        $nota->forceFill([
            'plugnotas_status' => $status ?? $nota->plugnotas_status,
            'numero'           => $numero ?? $nota->numero,
            'serie'            => $serie ?? $nota->serie,
            'chave_acesso'     => $chave ?? $nota->chave_acesso,
            'protocolo'        => $protocolo ?? $nota->protocolo,
            'pdf_url'          => $pdf ?? $nota->pdf_url,
            'xml_url'          => $xml ?? $nota->xml_url,
        ])->save();

        // 5) Se autorizado, marque emitida_em
        if (($status === 'CONCLUIDO' && $cStat === 100) || $status === 'CONCLUIDO') {
            $nota->forceFill([
                'emitida_em' => $nota->emitida_em ?? now(),
            ])->save();

            // OPCIONAL: baixar e guardar localmente:
            /*
            try {
                $pdfResp = $pn->pdfNFe($nota->plugnotas_id);
                $xmlResp = $pn->xmlNFe($nota->plugnotas_id);
                $dir = "notas/{$nota->id}";
                \Storage::disk('local')->put("{$dir}/danfe.pdf", $pdfResp->body());
                \Storage::disk('local')->put("{$dir}/nfe.xml", $xmlResp->body());
            } catch (\Throwable $e) {
                Log::warning('Webhook: falha ao salvar PDF/XML localmente', ['err' => $e->getMessage()]);
            }
            */
        }

        return response()->json(['ok' => true]);
    }
}
