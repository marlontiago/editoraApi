<?php

namespace App\Http\Controllers;

use App\Models\NotaFiscal;
use App\Services\PlugNotasService;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;

class NotaFiscalPlugNotasController extends Controller
{
    private function onlyDigits($v): string
    {
        return preg_replace('/\D+/', '', (string) $v);
    }

    private function isSandbox(): bool
    {
        return str_contains(config('services.plugnotas.base_url', env('PLUGNOTAS_BASE_URL', '')), 'sandbox');
    }

    /**
     * Tenta criar a empresa no sandbox — ignora se já existir.
     */
    private function ensureEmpresa(PlugNotasService $pn, string $emitenteCpfCnpj, int $serie = 1): void
    {
        if (!$this->isSandbox() || !$emitenteCpfCnpj) return;

        $crt = (int) env('EMPRESA_CRT', 3);

        $empresa = [
            'cpfCnpj'           => $emitenteCpfCnpj,
            'razaoSocial'       => env('EMPRESA_RAZAO', 'Emitente Exemplo'),
            'nomeFantasia'      => env('EMPRESA_RAZAO', 'Emitente Exemplo'),
            'email'             => env('EMPRESA_EMAIL', 'fiscal@exemplo.com.br'),
            'telefone'          => $this->onlyDigits(env('EMPRESA_TELEFONE', '44999999999')),
            'simplesNacional'   => ($crt === 1 || $crt === 2),
            'regimeTributario'  => $crt,
            'inscricaoEstadual' => $this->onlyDigits(env('EMPRESA_IE', '')),
            'endereco'          => [
                'logradouro'   => env('EMPRESA_LOGRADOURO', 'Rua Um'),
                'numero'       => env('EMPRESA_NUMERO', '123'),
                'bairro'       => env('EMPRESA_BAIRRO', 'Centro'),
                'cep'          => $this->onlyDigits(env('EMPRESA_CEP', '87000000')),
                'cidade'       => env('EMPRESA_MUNICIPIO', 'Maringa'),
                'estado'       => env('EMPRESA_UF', 'PR'),
                'codigoCidade' => (int) (env('EMPRESA_CODIGO_CIDADE', 4106902)),
            ],
            'nfe' => ['habilitado' => true, 'config' => ['serie' => $serie]],
        ];

        $res = $pn->createCompany($empresa);

        // Trata retorno: se já existe, não é erro crítico
        $body = $res->json();

        if ($res->successful()) {
            Log::info('ensureEmpresa: criado/retorno', $body);
            return;
        }

        // plugnotas costuma retornar error.message = "Já existe..." — ignoramos esse caso
        $msg = data_get($body, 'error.message') ?? data_get($body, 'message') ?? '';
        if (stripos($msg, 'já existe') !== false || stripos($msg, 'Já existe') !== false) {
            Log::info('ensureEmpresa: empresa já existe (ignorado)', $body);
            return;
        }

        // Caso contrário, apenas logamos como warning e seguimos
        Log::warning('ensureEmpresa: falha ao criar empresa (seguindo mesmo assim)', [
            'status' => $res->status(),
            'body'   => $body,
        ]);
    }

    /** ====== API JSON ====== */

    public function emitir(NotaFiscal $nota, PlugNotasService $pn): JsonResponse
    {
        $money2 = fn($v) => number_format((float)$v, 2, '.', '');
        $qty4   = fn($v) => number_format((float)$v, 4, '.', '');

        $emitente = $nota->emitente_snapshot ?? [];
        $dest     = $nota->destinatario_snapshot ?? [];
        $pedido   = $nota->pedido_snapshot ?? [];

        // Emitente
        $emitenteCpfCnpj = $this->onlyDigits($emitente['cpfCnpj'] ?? $emitente['cnpj'] ?? $emitente['cpf'] ?? env('EMPRESA_CNPJ', ''));
        $ieFromSnapshot  = $this->onlyDigits($emitente['inscricaoEstadual'] ?? $emitente['ie'] ?? '');
        $emitenteIE      = $ieFromSnapshot !== '' ? $ieFromSnapshot : $this->onlyDigits(env('EMPRESA_IE', ''));

        // Destinatário / Endereço
        $destCpfCnpj = $this->onlyDigits($dest['cpfCnpj'] ?? $dest['cnpj'] ?? $dest['cpf'] ?? '');
        $destRazao   = $dest['razaoSocial'] ?? $dest['razao_social'] ?? $dest['nome'] ?? 'Cliente';
        $dLog = data_get($dest, 'endereco.logradouro') ?? ($dest['endereco'] ?? 'Rua');
        $dNum = data_get($dest, 'endereco.numero')     ?? ($dest['numero'] ?? '0');
        $dBai = data_get($dest, 'endereco.bairro')     ?? ($dest['bairro'] ?? 'Centro');
        $dCep = $this->onlyDigits(data_get($dest, 'endereco.cep') ?? ($dest['cep'] ?? '01000000'));
        $dCid = data_get($dest, 'endereco.cidade')     ?? ($dest['municipio'] ?? 'Sao Paulo');
        $dUF  = strtoupper(data_get($dest, 'endereco.uf') ?? ($dest['uf'] ?? 'SP'));
        $dCodCid = (int) (data_get($dest, 'endereco.codigoCidade') ?? ($dest['codigoCidade'] ?? 0));
        if ($dCodCid <= 0) $dCodCid = 3550308;

        // Itens (snapshot ou relacionamento)
        $snapshotItens = (array) data_get($pedido, 'itens', []);
        if (empty($snapshotItens) && method_exists($nota, 'itens')) {
            $snapshotItens = $nota->itens->map(fn($ni) => [
                'produto_id'     => $ni->produto_id,
                'codigo'         => $ni->codigo ?? ('PROD-'.$ni->produto_id),
                'titulo'         => $ni->titulo ?? $ni->descricao_produto ?? 'Item',
                'ncm'            => $ni->ncm ?? '49019900',
                'unidade'        => $ni->unidade ?? 'UN',
                'quantidade'     => (float) $ni->quantidade,
                'valor_unitario' => (float) $ni->preco_unitario,
                'desconto'       => (float) ($ni->desconto ?? 0),
            ])->all();
        }

        // CFOP automático: PR é origem
        $estadoOrigem = 'PR';
        $itens = collect($snapshotItens)->map(function ($i) use ($money2, $qty4, $estadoOrigem, $dest) {
            $quantidade = (float)($i['quantidade'] ?? 1);
            $valorUnitario = (float)($i['valor_unitario'] ?? 0);

            $tributos = [
                'icms'   => data_get($i, 'tributos.icms')   ?? data_get($i, 'impostos.icms')   ?? ['origem' => 0, 'cst' => '40'],
                'pis'    => data_get($i, 'tributos.pis')    ?? data_get($i, 'impostos.pis')    ?? ['cst' => '06'],
                'cofins' => data_get($i, 'tributos.cofins') ?? data_get($i, 'impostos.cofins') ?? ['cst' => '06'],
            ];

            $estadoDestino = strtoupper(data_get($dest, 'endereco.uf') ?? ($dest['uf'] ?? ''));
            $cfop = ($estadoDestino === $estadoOrigem) ? '5101' : '6101';

            $item = [
                'codigo'        => $i['codigo'] ?? ('PROD-'.($i['produto_id'] ?? 'ITEM')),
                'descricao'     => $i['descricao'] ?? ($i['titulo'] ?? 'Item'),
                'ncm'           => $i['ncm'] ?? '49019900',
                'cfop'          => $cfop,
                'unidade'       => $i['unidade'] ?? 'UN',
                'quantidade'    => (float)$qty4($quantidade),
                'valorUnitario' => $money2($valorUnitario),
                'tributos'      => $tributos,
            ];

            if (isset($i['desconto'])) {
                $item['desconto'] = $money2($i['desconto']);
            }

            return $item;
        })->values()->all();

        // totaliza (cuidado: valorUnitario é string formatada com 2 casas)
        $totalProdutos = 0.0;
        $totalDesc = 0.0;
        foreach ($itens as $it) {
            $totalProdutos += ((float)$it['quantidade']) * ((float)$it['valorUnitario']);
            $totalDesc += (float) (($it['desconto'] ?? 0));
        }
        $valorTotal = $money2($totalProdutos - $totalDesc);

        // montando payload no padrão que a PlugNotas espera (ajuste conforme sua integração)
        $payload = [
            'idIntegracao'   => PlugNotasService::makeIdIntegracao('EDITORA'),
            'emitente'       => ['cpfCnpj' => $emitenteCpfCnpj, 'inscricaoEstadual' => $emitenteIE],
            'destinatario'   => [
                'cpfCnpj'     => $destCpfCnpj ?: null,
                'razaoSocial' => $destRazao,
                'endereco'    => [
                    'logradouro'   => $dLog,
                    'numero'       => $dNum,
                    'bairro'       => $dBai,
                    'cep'          => $dCep,
                    'cidade'       => $dCid,
                    'estado'       => $dUF,
                    'codigoCidade' => $dCodCid,
                ],
                'email' => $dest['email'] ?? null,
            ],
            'natureza'        => $pedido['natureza'] ?? $pedido['natureza_operacao'] ?? 'Venda de mercadoria',
            'consumidorFinal' => $pedido['consumidorFinal'] ?? true,
            'presencial'      => false,
            'saida'           => true,
            'serie'           => (int)($nota->serie ?? 1),
            'itens'           => $itens,
            'pagamentos'      => [[ 'meio' => '90', 'valor' => '0.00' ]],
            'valorTotal'      => $valorTotal,
        ];

        // sandbox: garante cadastro do emitente (ignora se já existir)
        $this->ensureEmpresa($pn, $emitenteCpfCnpj, (int)($nota->serie ?? 1));

        // envia
        $res = $pn->sendNFe($payload);
        $body = $res->json();

        // logs úteis
        Log::info('PLUG_PAYLOAD', ['payload' => $payload]);
        Log::info('PLUG_RESPONSE', ['status' => $res->status(), 'body' => $body]);

        // extrai docId de forma robusta
        $doc   = $body[0] ?? (data_get($body, 'data.0') ?? data_get($body, 'documents.0') ?? data_get($body, 'document') ?? null);
        $docId = data_get($doc, 'id') ?? data_get($doc, '_id') ?? data_get($body, 'id');

        if (!$docId) {
            Log::error('PLUG_DOC_ID_MISSING', ['res' => $body]);
            // devolve o corpo da API como JSON com status da API (ou 500)
            $status = $res->status() >= 200 ? $res->status() : 500;
            return response()->json($body, $status);
        }

        // salva status inicial na nota
        $nota->forceFill([
            'plugnotas_id'     => $docId,
            'protocolo'        => data_get($body, 'protocol') ?? null,
            'plugnotas_status' => data_get($doc, 'status') ?? data_get($body, 'message') ?? null,
            'ambiente'         => $this->isSandbox() ? 'sandbox' : 'producao',
            'status'           => 'emitida',
        ])->save();

        return response()->json($body, $res->status());
    }

    public function consultar(NotaFiscal $nota, PlugNotasService $pn): JsonResponse
    {
        if (!$nota->plugnotas_id) return response()->json(['error' => 'Nota sem plugnotas_id.'], 400);

        $res = $pn->resumoNFe($nota->plugnotas_id);
        $body = $res->json();

        Log::info('PLUG_RESUMO_DEBUG', [
            'id'     => $nota->plugnotas_id,
            'status' => data_get($body, 'status') ?? null,
            'numero' => data_get($body, 'numero') ?? null,
        ]);

        // salva campos úteis
        $nota->forceFill([
            'plugnotas_status' => data_get($body, 'status') ?? $nota->plugnotas_status,
            'chave_acesso'     => data_get($body, 'chave') ?? $nota->chave_acesso,
            'numero'           => data_get($body, 'numero') ?? $nota->numero,
            'serie'            => data_get($body, 'serie') ?? $nota->serie,
            'protocolo'        => data_get($body, 'protocolo') ?? $nota->protocolo,
            'pdf_url'          => data_get($body, 'pdf') ?: data_get($body, 'links.pdf') ?: $nota->pdf_url,
            'xml_url'          => data_get($body, 'xml') ?: data_get($body, 'links.xml') ?: $nota->xml_url,
        ])->save();

        if (strtoupper(data_get($body, 'status', '')) === 'CONCLUIDO') {
            $nota->forceFill(['emitida_em' => $nota->emitida_em ?? now()])->save();
        }

        return response()->json($body, $res->status());
    }

    public function pdf(NotaFiscal $nota, PlugNotasService $pn)
    {
        if (!$nota->plugnotas_id) return response()->json(['error' => 'Nota sem plugnotas_id.'], 400);

        // Se temos URL salva, redireciona
        if (!empty($nota->pdf_url)) {
            Log::info('PLUG_PDF_URL(CACHED)', ['id' => $nota->plugnotas_id, 'url' => $nota->pdf_url]);
            return redirect()->away($nota->pdf_url);
        }

        // tenta buscar resumo pra pegar link
        $resumo = $pn->resumoNFe($nota->plugnotas_id)->json();
        $pdf = data_get($resumo, 'pdf') ?: data_get($resumo, 'links.pdf');

        if ($pdf) {
            $nota->forceFill(['pdf_url' => $pdf])->save();
            return redirect()->away($pdf);
        }

        $resp = $pn->pdfNFe($nota->plugnotas_id);
        return response($resp->body(), 200)->header('Content-Type', 'application/pdf');
    }

    public function xml(NotaFiscal $nota, PlugNotasService $pn)
    {
        if (!$nota->plugnotas_id) return response()->json(['error' => 'Nota sem plugnotas_id.'], 400);

        if (!empty($nota->xml_url)) {
            return redirect()->away($nota->xml_url);
        }

        $resumo = $pn->resumoNFe($nota->plugnotas_id)->json();
        $xml = data_get($resumo, 'xml') ?: data_get($resumo, 'links.xml');

        if ($xml) {
            $nota->forceFill(['xml_url' => $xml])->save();
            return redirect()->away($xml);
        }

        $resp = $pn->xmlNFe($nota->plugnotas_id);
        return response($resp->body(), 200)->header('Content-Type', 'application/xml');
    }

    /** ====== Web (wrappers que retornam redirect+flash) ====== */

    public function emitirWeb(NotaFiscal $nota, PlugNotasService $pn)
    {
        $resp = $this->emitir($nota, $pn);
        if ($resp->status() >= 400) {
            $msg = data_get($resp->getData(true), 'message') ?? data_get($resp->getData(true), 'error.message') ?? 'Falha ao emitir';
            return back()->with('error', $msg);
        }
        return back()->with('success', 'Nota enviada para processamento.');
    }

    public function consultarWeb(NotaFiscal $nota, PlugNotasService $pn)
    {
        $resp = $this->consultar($nota, $pn);
        if ($resp->status() >= 400) return back()->with('error', 'Falha ao consultar status');
        $status = data_get($resp->getData(true)[0] ?? [], 'status') ?? data_get($resp->getData(true), 'status') ?? '—';
        return back()->with('success', 'Status: '.$status);
    }

    public function pdfWeb(NotaFiscal $nota, PlugNotasService $pn)
    {
        return $this->pdf($nota, $pn);
    }

    public function xmlWeb(NotaFiscal $nota, PlugNotasService $pn)
    {
        return $this->xml($nota, $pn);
    }
}
