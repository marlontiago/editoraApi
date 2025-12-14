<?php

namespace App\Services;

use App\Models\Pedido;
use App\Models\NotaFiscal;
use App\Models\NotaItem;
use Illuminate\Support\Facades\DB;

class NotaFiscalService
{
    /**
     * Emite (pré-visualização) a nota de um pedido.
     * Retorna a NotaFiscal criada.
     */
    public function emitir(Pedido $pedido): NotaFiscal
    {
        // bloqueia se pedido cancelado/finalizado
        if (in_array($pedido->status, ['cancelado', 'finalizado'], true)) {
            throw new \RuntimeException('Não é possível emitir pré-visualização para pedidos cancelados/finalizados.');
        }

        // bloqueia se já houver faturada
        $jaFaturada = NotaFiscal::where('pedido_id', $pedido->id)
            ->where('status', 'faturada')
            ->exists();

        if ($jaFaturada) {
            throw new \RuntimeException('Este pedido já possui uma nota faturada. Não é possível emitir outra.');
        }

        // carrega o pedido com pivots
        $pedido->load([
            'produtos' => function ($q) {
                $q->withPivot([
                    'quantidade', 'preco_unitario', 'desconto_aplicado',
                    'subtotal', 'peso_total_produto', 'caixas'
                ]);
            },
            'cliente', 'gestor', 'distribuidor'
        ]);

        // Próximo número (PostgreSQL-friendly) - mantém sua lógica
        $proximoNumero = (string) (NotaFiscal::max(DB::raw("NULLIF(numero, '')::int")) + 1);

        $nota = null;

        DB::transaction(function () use ($pedido, $proximoNumero, &$nota) {

            // se ainda estiver em_andamento => pre_aprovado
            if ($pedido->status === 'em_andamento') {
                $pedido->update([
                    'status'            => 'pre_aprovado',
                    'status_financeiro' => 'pre_aprovado',
                ]);

                if (method_exists($pedido, 'registrarLog')) {
                    $pedido->registrarLog(
                        'status_alterado',
                        'Status alterado para Pré-aprovado (pré-visualização de nota).',
                        []
                    );
                }
            }

            // cancela emitidas anteriores
            $notasEmitidas = NotaFiscal::where('pedido_id', $pedido->id)
                ->where('status', 'emitida')
                ->get();

            foreach ($notasEmitidas as $old) {
                $old->update([
                    'status'              => 'cancelada',
                    'cancelada_em'        => now(),
                    'motivo_cancelamento' => 'Substituída automaticamente por nova emissão em ' . now()->format('d/m/Y H:i'),
                ]);

                if ($pedido && method_exists($pedido, 'registrarLog')) {
                    $pedido->registrarLog(
                        'nota_cancelada',
                        "Nota {$old->numero} cancelada por substituição automática.",
                        ['nota_id' => $old->id]
                    );
                }
            }

            // snapshots
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
                'status'                => 'emitida',
                'status_financeiro'     => 'pre_aprovado',
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

            if (method_exists($pedido, 'registrarLog')) {
                $pedido->registrarLog('nota_emitida', "Nota {$nota->numero} emitida (pré-visualização).", ['nota_id' => $nota->id]);
            }
            
        });
        if (!$nota instanceof NotaFiscal) {
            throw new \RuntimeException('Falha ao emitir a nota fiscal.');
        }

        return $nota;
    }

    /**
     * Fatura uma nota. Retorna a NotaFiscal atualizada.
     */
    public function faturar(NotaFiscal $nota, string $modo = 'normal'): NotaFiscal
    {
        if ($nota->status !== 'emitida') {
            throw new \RuntimeException('A nota não está no status correto para faturamento.');
        }

        $modo = strtolower($modo);
        if (!in_array($modo, ['normal','simples_remessa','brinde'], true)) {
            $modo = 'normal';
        }

        $nota->load('itens.produto', 'pedido');

        DB::transaction(function () use ($nota, $modo) {

            foreach ($nota->itens as $item) {
                if (!$item->produto) {
                    throw new \RuntimeException("Produto {$item->produto_id} não encontrado.");
                }
            }

            $deveBaixarEstoque = in_array($modo, ['normal','brinde'], true);
            $statusFinanceiro  = match ($modo) {
                'simples_remessa' => 'simples_remessa',
                'brinde'          => 'brinde',
                default           => 'aguardando_pagamento',
            };

            if ($deveBaixarEstoque) {
                foreach ($nota->itens as $item) {
                    $afetados = \App\Models\Produto::whereKey($item->produto_id)
                        ->where('quantidade_estoque', '>=', (int) $item->quantidade)
                        ->update([
                            'quantidade_estoque' => DB::raw('quantidade_estoque - ' . (int) $item->quantidade),
                        ]);

                    if ($afetados === 0) {
                        $nome = $item->produto?->nome ?? $item->produto?->titulo ?? ('ID ' . $item->produto_id);
                        throw new \RuntimeException("Estoque insuficiente para {$nome}.");
                    }
                }
            }

            $nota->update([
                'status'            => 'faturada',
                'faturada_em'       => now(),
                'status_financeiro' => $statusFinanceiro,
            ]);

            if ($nota->pedido) {
                $nota->pedido->update(['status' => 'finalizado']);
            }

            if ($nota->pedido && method_exists($nota->pedido, 'registrarLog')) {
                $msg = match ($modo) {
                    'simples_remessa' => "Nota {$nota->numero} faturada como SIMPLES REMESSA (sem baixa de estoque).",
                    'brinde'          => "Nota {$nota->numero} faturada como BRINDE (estoque baixado).",
                    default           => "Nota {$nota->numero} faturada (estoque baixado).",
                };

                $nota->pedido->registrarLog('nota_faturada', $msg, ['nota_id' => $nota->id, 'modo' => $modo]);
            }
        });

        return $nota->refresh();
    }
}
