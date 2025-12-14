<?php

namespace App\Services;

use App\Models\NotaFiscal;
use App\Models\NotaPagamento;
use App\Models\Advogado;
use App\Models\DiretorComercial;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class NotaPagamentoService
{
    /**
     * Retorna o payload que a tela "create" precisa.
     * (cidades + anexos + percentuais + lists advogados/diretores)
     */
    public function getCreatePayload(NotaFiscal $nota): array
    {
        $nota->load([
            'pedido.gestor.anexos',
            'pedido.distribuidor.anexos',
            'pedido.cidades',
        ]);

        if ($nota->status !== 'faturada') {
            throw new \RuntimeException('Apenas notas faturadas podem registrar pagamento.');
        }

        $cidadeIds = $nota->pedido->cidades->pluck('id');
        $refDate   = Carbon::now();

        $percGestor       = $this->resolvePercentualByCidade(optional($nota->pedido)->gestor, $cidadeIds, $refDate);
        $percDistribuidor = $this->resolvePercentualByCidade(optional($nota->pedido)->distribuidor, $cidadeIds, $refDate);

        $advogados = Advogado::orderBy('nome')->get(['id','nome','percentual_vendas']);
        $diretores = DiretorComercial::orderBy('nome')->get(['id','nome','percentual_vendas']);

        return compact('nota', 'advogados', 'diretores', 'percGestor', 'percDistribuidor');
    }

    /**
     * Regras de validação reaproveitáveis (web e api).
     */
    public function rules(): array
    {
        return [
            'data_pagamento'         => ['nullable','date'],
            'valor_pago'             => ['required','numeric','min:0.01'],

            'ret_irrf'   => ['nullable','numeric','min:0','max:100'],
            'ret_iss'    => ['nullable','numeric','min:0','max:100'],
            'ret_inss'   => ['nullable','numeric','min:0','max:100'],
            'ret_pis'    => ['nullable','numeric','min:0','max:100'],
            'ret_cofins' => ['nullable','numeric','min:0','max:100'],
            'ret_csll'   => ['nullable','numeric','min:0','max:100'],
            'ret_outros' => ['nullable','numeric','min:0','max:100'],

            'adesao_ata'             => ['nullable','boolean'],

            'advogado_id'            => ['nullable','exists:advogados,id'],
            'perc_comissao_advogado' => ['nullable','numeric','min:0','max:100'],

            'diretor_id'             => ['nullable','exists:diretor_comercials,id'],
            'perc_comissao_diretor'  => ['nullable','numeric','min:0','max:100'],

            'observacoes'            => ['nullable','string','max:2000'],
        ];
    }

    /**
     * Cria o pagamento e atualiza status financeiro da nota.
     * Retorna o pagamento criado e a nota "refresh" (opcional).
     */
    public function store(NotaFiscal $nota, array $data): NotaPagamento
    {
        if ($nota->status !== 'faturada') {
            throw new \RuntimeException('Apenas notas faturadas podem registrar pagamento.');
        }

        $adesaoAta = (bool) ($data['adesao_ata'] ?? false);

        // precisa cidades + anexos pra regra de percentual por cidade
        $nota->load([
            'pedido.gestor.anexos',
            'pedido.distribuidor.anexos',
            'pedido.cidades',
        ]);

        if ($adesaoAta && empty($data['advogado_id'])) {
            throw new \RuntimeException('Informe o advogado para adesão à ata.');
        }

        // Auto-preencher percentuais se não vierem
        if ($adesaoAta && ($data['advogado_id'] ?? null) && ($data['perc_comissao_advogado'] ?? null) === null) {
            $auto = Advogado::whereKey($data['advogado_id'])->value('percentual_vendas');
            if ($auto !== null) $data['perc_comissao_advogado'] = (float) $auto;
        }

        if (
            ($data['diretor_id'] ?? null) &&
            (($data['perc_comissao_diretor'] ?? null) === null)
        ) {
            $auto = DiretorComercial::whereKey($data['diretor_id'])->value('percentual_vendas');

            if ($auto !== null) {
                $data['perc_comissao_diretor'] = (float) $auto;
            }
        }

        // ==== Cálculos base
        $valorPago = (float) $data['valor_pago'];
        $getp = fn($k) => (float) ($data[$k] ?? 0);

        $retPer = [
            'irrf'   => $getp('ret_irrf'),
            'iss'    => $getp('ret_iss'),
            'inss'   => $getp('ret_inss'),
            'pis'    => $getp('ret_pis'),
            'cofins' => $getp('ret_cofins'),
            'csll'   => $getp('ret_csll'),
            'outros' => $getp('ret_outros'),
        ];

        $retVal = [
            'irrf'   => round($valorPago * ($retPer['irrf']   / 100), 2),
            'iss'    => round($valorPago * ($retPer['iss']    / 100), 2),
            'inss'   => round($valorPago * ($retPer['inss']   / 100), 2),
            'pis'    => round($valorPago * ($retPer['pis']    / 100), 2),
            'cofins' => round($valorPago * ($retPer['cofins'] / 100), 2),
            'csll'   => round($valorPago * ($retPer['csll']   / 100), 2),
            'outros' => round($valorPago * ($retPer['outros'] / 100), 2),
        ];

        $totalRet = array_sum($retVal);
        $valorLiquido = max(0, round($valorPago - $totalRet, 2));

        // ===== Regra percentuais por cidade
        $cidadeIds = $nota->pedido->cidades->pluck('id');
        $refDate   = isset($data['data_pagamento']) ? Carbon::parse($data['data_pagamento']) : Carbon::now();

        $percGestor       = $this->resolvePercentualByCidade(optional($nota->pedido)->gestor, $cidadeIds, $refDate);
        $percDistribuidor = $this->resolvePercentualByCidade(optional($nota->pedido)->distribuidor, $cidadeIds, $refDate);

        // Comissões sobre líquido
        $comissaoGestor       = round($valorLiquido * ($percGestor / 100), 2);
        $comissaoDistribuidor = round($valorLiquido * ($percDistribuidor / 100), 2);

        $percAdv = (float) ($data['perc_comissao_advogado'] ?? 0);
        $percDir = (float) ($data['perc_comissao_diretor']  ?? 0);

        $comissaoAdv = $adesaoAta && $percAdv > 0 ? round($valorLiquido * ($percAdv / 100), 2) : 0.0;
        $comissaoDir = $percDir > 0 ? round($valorLiquido * ($percDir / 100), 2) : 0.0;

        $pagamento = null;

        DB::transaction(function () use (
            $nota, $data,
            $retPer, $retVal,
            $valorLiquido,
            $percGestor, $percDistribuidor,
            $comissaoGestor, $comissaoDistribuidor,
            $comissaoAdv, $comissaoDir,
            &$pagamento
        ) {
            $pagamento = NotaPagamento::create([
                'nota_fiscal_id' => $nota->id,

                'data_pagamento' => $data['data_pagamento'] ?? null,
                'valor_pago'     => $data['valor_pago'],
                'valor_liquido'  => $valorLiquido,

                'ret_irrf_perc'    => $retPer['irrf']   ?: null,
                'ret_irrf_valor'   => $retVal['irrf']   ?: null,
                'ret_iss_perc'     => $retPer['iss']    ?: null,
                'ret_iss_valor'    => $retVal['iss']    ?: null,
                'ret_inss_perc'    => $retPer['inss']   ?: null,
                'ret_inss_valor'   => $retVal['inss']   ?: null,
                'ret_pis_perc'     => $retPer['pis']    ?: null,
                'ret_pis_valor'    => $retVal['pis']    ?: null,
                'ret_cofins_perc'  => $retPer['cofins'] ?: null,
                'ret_cofins_valor' => $retVal['cofins'] ?: null,
                'ret_csll_perc'    => $retPer['csll']   ?: null,
                'ret_csll_valor'   => $retVal['csll']   ?: null,
                'ret_outros_perc'  => $retPer['outros'] ?: null,
                'ret_outros_valor' => $retVal['outros'] ?: null,

                'adesao_ata'             => (bool) ($data['adesao_ata'] ?? false),

                'advogado_id'            => $data['advogado_id'] ?? null,
                'perc_comissao_advogado' => $data['perc_comissao_advogado'] ?? null,
                'comissao_advogado'      => $comissaoAdv,

                'diretor_id'             => $data['diretor_id'] ?? null,
                'perc_comissao_diretor'  => $data['perc_comissao_diretor'] ?? null,
                'comissao_diretor'       => $comissaoDir,

                'perc_comissao_gestor'        => $percGestor,
                'comissao_gestor'             => $comissaoGestor,
                'perc_comissao_distribuidor'  => $percDistribuidor,
                'comissao_distribuidor'       => $comissaoDistribuidor,
                'comissao_snapshot_at'        => now(),

                'observacoes' => $data['observacoes'] ?? null,
            ]);

            $nota->refresh();
            $nota->atualizarStatusFinanceiro();
        });

        if (!$pagamento instanceof NotaPagamento) {
            throw new \RuntimeException('Falha ao registrar pagamento.');
        }

        return $pagamento->refresh();
    }

    /**
     * Retorna o mesmo "pacote de dados" que a view show precisava (sem faltar nada).
     */
    public function getShowPayload(NotaFiscal $nota, NotaPagamento $pagamento): array
    {
        if ($pagamento->nota_fiscal_id !== $nota->id) {
            throw new \RuntimeException('Pagamento não pertence a esta nota.');
        }

        $nota->load([
            'pedido.produtos' => function ($q) {
                $q->withPivot(['quantidade','preco_unitario','desconto_aplicado','subtotal','peso_total_produto','caixas']);
            },
            'pedido.cliente',
            'pedido.gestor',
            'pedido.distribuidor',
            'pedido.cidades',
        ]);

        $pedido = $nota->pedido;

        $valorBrutoPedido = 0.0;
        $totalDescontosPedido = 0.0;
        $valorComDescontoPedido = 0.0;

        foreach ($pedido->produtos as $p) {
            $qtd       = (int) ($p->pivot->quantidade ?? 0);
            $unit      = (float) ($p->pivot->preco_unitario ?? 0);
            $brutoItem = $qtd * $unit;
            $subtotal  = (float) ($p->pivot->subtotal ?? 0);

            $valorBrutoPedido      += $brutoItem;
            $totalDescontosPedido  += max(0, $brutoItem - $subtotal);
            $valorComDescontoPedido+= $subtotal;
        }

        $valorPago    = (float) $pagamento->valor_pago;
        $valorLiquido = (float) $pagamento->valor_liquido;

        $ret = [
            'irrf'   => (float) ($pagamento->ret_irrf_perc   ?? 0),
            'iss'    => (float) ($pagamento->ret_iss_perc    ?? 0),
            'inss'   => (float) ($pagamento->ret_inss_perc   ?? 0),
            'pis'    => (float) ($pagamento->ret_pis_perc    ?? 0),
            'cofins' => (float) ($pagamento->ret_cofins_perc ?? 0),
            'csll'   => (float) ($pagamento->ret_csll_perc   ?? 0),
            'outros' => (float) ($pagamento->ret_outros_perc ?? 0),
        ];
        $retValores = [
            'irrf'   => (float) ($pagamento->ret_irrf_valor   ?? 0),
            'iss'    => (float) ($pagamento->ret_iss_valor    ?? 0),
            'inss'   => (float) ($pagamento->ret_inss_valor   ?? 0),
            'pis'    => (float) ($pagamento->ret_pis_valor    ?? 0),
            'cofins' => (float) ($pagamento->ret_cofins_valor ?? 0),
            'csll'   => (float) ($pagamento->ret_csll_valor   ?? 0),
            'outros' => (float) ($pagamento->ret_outros_valor ?? 0),
        ];
        $totalRetencoes = array_sum($retValores);

        $percGestor       = (float) ($pagamento->perc_comissao_gestor       ?? 0);
        $percDistribuidor = (float) ($pagamento->perc_comissao_distribuidor ?? 0);

        $comissaoGestor       = (float) ($pagamento->comissao_gestor       ?? 0);
        $comissaoDistribuidor = (float) ($pagamento->comissao_distribuidor ?? 0);

        $percAdv = (float) ($pagamento->perc_comissao_advogado ?? 0);
        $percDir = (float) ($pagamento->perc_comissao_diretor  ?? 0);

        $comissaoAdv = (float) ($pagamento->comissao_advogado ?? 0);
        $comissaoDir = (float) ($pagamento->comissao_diretor  ?? 0);

        return compact(
            'nota','pagamento','pedido',
            'valorBrutoPedido','totalDescontosPedido','valorComDescontoPedido',
            'percGestor','percDistribuidor',
            'comissaoGestor','comissaoDistribuidor',
            'ret','retValores','totalRetencoes',
            'valorLiquido','percAdv','percDir','comissaoAdv','comissaoDir','valorPago'
        );
    }

    /**
     * PRIORIDADE:
     *  (1) contrato por CIDADE vigente (mesmo com ativo=false) →
     *  (2) GLOBAL ATIVO vigente →
     *  (3) GLOBAL vigente (mais recente) →
     *  (4) cadastro
     */
    private function resolvePercentualByCidade($owner, $cidadeIds, Carbon $refDate): float
    {
        if (!$owner) return 0.0;

        $cidadeIds = collect($cidadeIds)->filter(fn($v) => !is_null($v) && $v !== '')->map(fn($v)=>(int)$v)->values();

        if ($cidadeIds->isNotEmpty()) {
            $cityAnexos = $owner->anexos()
                ->where('tipo', 'contrato_cidade')
                ->whereIn('cidade_id', $cidadeIds)
                ->orderByDesc('data_assinatura')
                ->orderByDesc('created_at')
                ->get();

            $cityVigentes = $cityAnexos->filter(fn($ax) =>
                $this->anexoVigente($ax, $refDate) && $ax->percentual_vendas !== null
            );

            if ($cityVigentes->isNotEmpty()) {
                return (float) $cityVigentes->first()->percentual_vendas;
            }
        }

        $globalAnexos = $owner->anexos()
            ->whereNull('cidade_id')
            ->whereIn('tipo', ['contrato','aditivo','outro'])
            ->orderByDesc('data_assinatura')
            ->orderByDesc('created_at')
            ->get();

        $globalVigentes = $globalAnexos->filter(fn($ax) =>
            $this->anexoVigente($ax, $refDate) && $ax->percentual_vendas !== null
        );

        $globalActive = $globalVigentes->first(fn($ax) => (bool) $ax->ativo === true);
        if ($globalActive) return (float) $globalActive->percentual_vendas;

        if ($globalVigentes->isNotEmpty()) return (float) $globalVigentes->first()->percentual_vendas;

        return (float) ($owner->percentual_vendas ?? 0.0);
    }

    private function anexoVigente($ax, Carbon $refDate): bool
    {
        $inicio = null;
        if (!empty($ax->data_assinatura)) {
            try { $inicio = Carbon::parse($ax->data_assinatura)->startOfDay(); } catch (\Throwable $e) {}
        }
        if (!$inicio && !empty($ax->created_at)) {
            try { $inicio = Carbon::parse($ax->created_at)->startOfDay(); } catch (\Throwable $e) {}
        }

        $fim = null;
        if (!empty($ax->data_vencimento)) {
            try { $fim = Carbon::parse($ax->data_vencimento)->endOfDay(); } catch (\Throwable $e) {}
        }

        if (!$inicio && !$fim) {
            return (bool) $ax->ativo === true;
        }

        if (!$fim) {
            if (!(bool)$ax->ativo) return false;
            if ($inicio) return $refDate->greaterThanOrEqualTo($inicio);
            return true;
        }

        if (!$inicio) {
            return (bool)$ax->ativo === true && $refDate->lessThanOrEqualTo($fim);
        }

        return $refDate->betweenIncluded($inicio, $fim);
    }
}
