<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use App\Models\Produto;
use App\Models\NotaFiscal;
use App\Models\NotaItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Support\CfopRules; // ✅ Regras centralizadas de CFOP

class NotaFiscalController extends Controller
{
    public function emitir(Request $request, Pedido $pedido)
    {
        // ✅ valida CFOP (4 dígitos, opcional)
        $dadosNota = $request->validate([
            'cfop' => ['nullable','regex:/^\d{4}$/'],
            // ... se tiver outros campos no seu form, valide aqui também
        ]);

        $jaFaturada = NotaFiscal::where('pedido_id', $pedido->id)
            ->where('status', 'faturada')
            ->exists();

        if ($jaFaturada) {
            return back()->with('error', 'Este pedido já possui uma nota faturada. Não é possível emitir outra.');
        }

        $substituir = $request->boolean('substituir');

        $pedido->load([
            'produtos' => function ($q) {
                $q->withPivot([
                    'quantidade', 'preco_unitario', 'desconto_aplicado',
                    'subtotal', 'peso_total_produto', 'caixas'
                ]);
            },
            'cliente', 'gestor', 'distribuidor'
        ]);

        $notaEmitida = NotaFiscal::where('pedido_id', $pedido->id)
            ->where('status', 'emitida')
            ->latest('id')
            ->first();

        if ($notaEmitida && !$substituir) {
            return back()->with('error', 'Já existe uma nota emitida para este pedido. Você pode faturar a atual ou emitir uma nova nota substituindo a atual.');
        }

        // Próximo número sequencial (Postgres): converte numero -> int com NULLIF
        $proximoNumero = (string) (NotaFiscal::max(DB::raw("NULLIF(numero, '')::int")) + 1);

        DB::transaction(function () use ($pedido, $proximoNumero, $notaEmitida, $substituir, $dadosNota) {
            // 👉 CFOP usado na nota: do form OU, se não vier, usa o do pedido
            $cfopUsado = $dadosNota['cfop'] ?? $pedido->cfop ?? null;

            if ($notaEmitida && $substituir) {
                $notaEmitida->update([
                    'status'              => 'cancelada',
                    'cancelada_em'        => now(),
                    'motivo_cancelamento' => 'Substituída por nova emissão em ' . now()->format('d/m/Y H:i'),
                ]);

                if ($pedido && method_exists($pedido, 'registrarLog')) {
                    $pedido->registrarLog(
                        'nota_cancelada',
                        "Nota {$notaEmitida->numero} cancelada por substituição.",
                        ['nota_id' => $notaEmitida->id]
                    );
                }
            }

            $emitente = [
                'razao_social' => config('empresa.razao_social', env('EMPRESA_RAZAO', 'Minha Empresa LTDA')),
                'cnpj'         => config('empresa.cnpj',         env('EMPRESA_CNPJ',  '00.000.000/0000-00')),
                'ie'           => config('empresa.ie',           env('EMPRESA_IE',    'ISENTO')),
                'endereco'     => config('empresa.endereco',     env('EMPRESA_ENDERECO', 'Rua Exemplo, 123')),
                'bairro'       => config('empresa.bairro',       env('EMPRESA_BAIRRO',   'Centro')),
                'municipio'    => config('empresa.municipio',    env('EMPRESA_MUNICIPIO','Curitiba')),
                'uf'           => config('empresa.uf',           env('EMPRESA_UF',       'PR')),
                'cep'          => config('empresa.cep',          env('EMPRESA_CEP',      '00000-000')),
                'telefone'     => config('empresa.telefone',     env('EMPRESA_FONE',     '(00) 0000-0000')),
                'email'        => config('empresa.email',        env('EMPRESA_EMAIL',    'contato@empresa.com')),
            ];

            $c = $pedido->cliente;
            $destinatario = [
                'razao_social'   => $c?->razao_social,
                'cnpj'           => $c?->cnpj,
                'cpf'            => $c?->cpf,
                'inscr_estadual' => $c?->inscr_estadual,
                'endereco'       => trim(($c?->endereco ?? '')
                                    . ($c?->numero ? ', '.$c->numero : '')
                                    . ($c?->complemento ? ' - '.$c->complemento : '')),
                'bairro'         => $c?->bairro,
                'municipio'      => $c?->cidade,
                'uf'             => $c?->uf,
                'cep'            => $c?->cep,
                'telefone'       => $c?->telefone,
                'email'          => $c?->email,
            ];

            $valorBruto  = (float) ($pedido->valor_bruto ?? 0);
            $valorTotal  = (float) ($pedido->valor_total ?? 0);
            $desconto    = max(0, $valorBruto - $valorTotal);
            $pesoTotal   = (float) ($pedido->peso_total ?? 0);
            $totalCaixas = (int)   ($pedido->total_caixas ?? 0);
            $tipo        = $pedido->tipo ?? '1';

            $nota = NotaFiscal::create([
                'pedido_id'             => $pedido->id,
                'numero'                => $proximoNumero,
                'serie'                 => '1',
                'cfop'                  => $cfopUsado, // ✅ grava CFOP decidido
                'status'                => 'emitida',
                'valor_bruto'           => $valorBruto,
                'desconto_total'        => $desconto,
                'valor_total'           => $valorTotal,
                'peso_total'            => $pesoTotal,
                'total_caixas'          => $totalCaixas,
                'emitente_snapshot'     => $emitente,
                'destinatario_snapshot' => $destinatario,
                'pedido_snapshot'       => [
                    'id'                 => $pedido->id,
                    'data'               => optional($pedido->data)->format('Y-m-d'),
                    'status'             => $pedido->status,
                    'natureza_operacao'  => $pedido->natureza_operacao ?? 'VENDA DE PRODUTOS',
                    'tipo'               => $tipo,
                    'gestor_id'          => $pedido->gestor_id,
                    'distribuidor_id'    => $pedido->distribuidor_id,
                    'cliente_id'         => $pedido->cliente_id,
                    'observacoes'        => $pedido->observacoes ?? null,
                ],
                'ambiente'              => 'interno',
                'emitida_em'            => now(),
            ]);

            foreach ($pedido->produtos as $produto) {
                NotaItem::create([
                    'nota_fiscal_id'     => $nota->id,
                    'produto_id'         => $produto->id,
                    'quantidade'         => (int) $produto->pivot->quantidade,
                    'preco_unitario'     => (float) $produto->pivot->preco_unitario,
                    'desconto_aplicado'  => (float) $produto->pivot->desconto_aplicado,
                    'subtotal'           => (float) $produto->pivot->subtotal,
                    'peso_total_produto' => (float) $produto->pivot->peso_total_produto,
                    'caixas'             => (int) $produto->pivot->caixas,
                    'descricao_produto'  => $produto->nome ?? $produto->titulo,
                    'isbn'               => $produto->isbn ?? null,
                    'titulo'             => $produto->titulo ?? null,
                ]);
            }

            if ($pedido && method_exists($pedido, 'registrarLog')) {
                $pedido->registrarLog('nota_emitida', "Nota {$nota->numero} emitida.", [
                    'nota_id' => $nota->id,
                    'cfop'    => $nota->cfop,
                ]);
            }
        });

        // ✅ Mensagem conforme CFOP (considera o do pedido se o form não vier)
        $cfop = $dadosNota['cfop'] ?? $pedido->cfop ?? null;
        $msg  = 'Nota emitida com sucesso.';

        if (CfopRules::isSimplesRemessa($cfop)) {
            $msg = 'Nota emitida (Simples Remessa) — baixa de estoque será ignorada no faturamento.';
        } elseif (CfopRules::isBonificacao($cfop)) {
            $msg = 'Nota emitida (Bonificação) — baixa de estoque ocorrerá no faturamento.';
        }

        return back()->with('success', $substituir
            ? "Nova nota emitida com sucesso (a anterior foi cancelada). {$msg}"
            : $msg
        );
    }

    public function faturar(NotaFiscal $nota)
    {
        if ($nota->status !== 'emitida') {
            return back()->with('error', 'A nota não está no status correto para faturamento.');
        }

        $nota->load('itens.produto', 'pedido');

        try {
            DB::transaction(function () use ($nota) {
                // valida existência dos produtos
                foreach ($nota->itens as $item) {
                    if (!$item->produto) {
                        throw new \RuntimeException("Produto {$item->produto_id} não encontrado.");
                    }
                }

                // 👉 Normaliza CFOP e registra decisão no log
                $cfop = $nota->cfop !== null ? trim((string) $nota->cfop) : null;
                Log::debug('Faturar Nota - CFOP e decisão', [
                    'nota_id'         => $nota->id,
                    'cfop'            => $cfop,
                    'simples_remessa' => CfopRules::isSimplesRemessa($cfop),
                    'altera_estoque'  => CfopRules::alteraEstoque($cfop),
                ]);

                // ✅ Regra de estoque por CFOP:
                // - Simples remessa: NÃO baixa estoque (alteraEstoque=false)
                // - Bonificação: baixa estoque
                // - Outros: baixa estoque
                if (CfopRules::alteraEstoque($cfop)) {
                    foreach ($nota->itens as $item) {
                        $afetados = Produto::whereKey($item->produto_id)
                            ->where('quantidade_estoque', '>=', (int) $item->quantidade)
                            ->update([
                                'quantidade_estoque' => DB::raw('quantidade_estoque - ' . (int) $item->quantidade),
                            ]);

                        if ($afetados === 0) {
                            $nome = $item->produto?->titulo ?? $item->produto?->nome ?? ('ID ' . $item->produto_id);
                            throw new \RuntimeException("Estoque insuficiente para {$nome}.");
                        }
                    }
                }

                $nota->update([
                    'status'            => 'faturada',
                    'faturada_em'       => now(),
                    // Relatórios financeiros usarão scopeParaRelatorioFinanceiro() no Model (exclui CFOPs de remessa/bonificação)
                    'status_financeiro' => 'aguardando_pagamento',
                ]);

                // Mantém a finalização do pedido após faturar (ajuste se quiser lógica diferente p/ remessa)
                if ($nota->pedido && in_array($nota->pedido->status, ['em_andamento','emitido','aprovado','pre_aprovado'])) {
                    $nota->pedido->update(['status' => 'finalizado']);
                }

                if ($nota->pedido && method_exists($nota->pedido, 'registrarLog')) {
                    $msg = match (true) {
                        CfopRules::isSimplesRemessa($cfop) =>
                            "Nota {$nota->numero} faturada (sem baixa de estoque - simples remessa).",
                        CfopRules::isBonificacao($cfop) =>
                            "Nota {$nota->numero} faturada (estoque baixado - bonificação).",
                        default =>
                            "Nota {$nota->numero} faturada (estoque baixado).",
                    };

                    $nota->pedido->registrarLog('nota_faturada', $msg, [
                        'nota_id' => $nota->id,
                        'cfop'    => $cfop,
                    ]);
                }
            });
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        // Mensagem de retorno coerente com CFOP
        $msg = match (true) {
            CfopRules::isSimplesRemessa($nota->cfop) =>
                'Nota faturada sem baixa de estoque (simples remessa).',
            CfopRules::isBonificacao($nota->cfop) =>
                'Nota faturada e estoque atualizado (bonificação).',
            default =>
                'Nota faturada e estoque atualizado com sucesso.',
        };

        return back()->with('success', $msg);
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
