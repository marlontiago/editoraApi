<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotaFiscal;
use App\Models\NotaPagamento;
use App\Models\Advogado;
use App\Models\DiretorComercial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotaPagamentoController extends Controller
{
    public function create(NotaFiscal $nota)
    {
        // Carrega gestor/distribuidor para obter percentual_vendas
        $nota->load(['pedido.gestor', 'pedido.distribuidor']);
        //dd($nota->toArray());

        $percGestor       = (float) optional($nota->pedido->gestor)->percentual_vendas ?: 0.0;
        $percDistribuidor = (float) optional($nota->pedido->distribuidor)->percentual_vendas ?: 0.0;
        

        // Agora buscamos nas tabelas dos CRUDs
        $advogados = Advogado::orderBy('nome')->get(['id','nome','percentual_vendas']);
        $diretores = DiretorComercial::orderBy('nome')->get(['id','nome','percentual_vendas']);

        // Regra: só permite registrar pagamento se a nota estiver faturada
        if ($nota->status !== 'faturada') {
            return redirect()
                ->route('admin.notas.show', $nota)
                ->with('error', 'Apenas notas faturadas podem registrar pagamento.');
        }

        return view('admin.notas.pagamentos.create', compact(
            'nota', 'advogados', 'diretores', 'percGestor', 'percDistribuidor'
        ));
    }

    public function store(Request $request, NotaFiscal $nota)
    {
        if ($nota->status !== 'faturada') {
            return back()->with('error', 'Apenas notas faturadas podem registrar pagamento.');
        }

        // Regras: você continua enviando as retenções como % (0–100)
        $rules = [
            'data_pagamento'         => ['nullable','date'],
            'valor_pago'             => ['required','numeric','min:0.01'],

            // Retenções em % (0 a 100)
            'ret_irrf'               => ['nullable','numeric','min:0','max:100'],
            'ret_iss'                => ['nullable','numeric','min:0','max:100'],
            'ret_inss'               => ['nullable','numeric','min:0','max:100'],
            'ret_pis'                => ['nullable','numeric','min:0','max:100'],
            'ret_cofins'             => ['nullable','numeric','min:0','max:100'],
            'ret_csll'               => ['nullable','numeric','min:0','max:100'],
            'ret_outros'             => ['nullable','numeric','min:0','max:100'],

            'adesao_ata'             => ['nullable','boolean'],

            'advogado_id'            => ['nullable','exists:advogados,id'],
            'perc_comissao_advogado' => ['nullable','numeric','min:0','max:100'],

            'diretor_id'             => ['nullable','exists:diretor_comercials,id'],
            'perc_comissao_diretor'  => ['nullable','numeric','min:0','max:100'],

            'observacoes'            => ['nullable','string','max:2000'],
        ];

        $data = $request->validate($rules);

        // Checkbox
        $adesaoAta = (bool) ($data['adesao_ata'] ?? false);

        // Carrega percentuais atuais (apenas para sugerir/auto-preencher quando não vier do form)
        $nota->load(['pedido.gestor', 'pedido.distribuidor']);

        // Se marcar adesão, exige advogado
        if ($adesaoAta && empty($data['advogado_id'])) {
            return back()->withInput()->withErrors([
                'advogado_id' => 'Informe o advogado para adesão à ata.'
            ]);
        }

        // Preenche % do advogado se não vier do form
        if ($adesaoAta && ($data['advogado_id'] ?? null) && ($data['perc_comissao_advogado'] === null)) {
            $auto = \App\Models\Advogado::whereKey($data['advogado_id'])->value('percentual_vendas');
            if ($auto !== null) {
                $data['perc_comissao_advogado'] = (float) $auto;
            }
        }

        // Preenche % do diretor se não vier do form
        if (($data['diretor_id'] ?? null) && ($data['perc_comissao_diretor'] === null)) {
            $auto = \App\Models\DiretorComercial::whereKey($data['diretor_id'])->value('percentual_vendas');
            if ($auto !== null) {
                $data['perc_comissao_diretor'] = (float) $auto;
            }
        }

        // ==== CÁLCULOS ====
        $valorPago = (float) $data['valor_pago'];

        // Map de retenções (% -> valor)
        $getp = fn($k) => (float) ($data[$k] ?? 0); // percentual 0–100

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

        // Snapshot de % de gestor/distribuidor NO MOMENTO DO PAGAMENTO
        $percGestor       = (float) optional($nota->pedido->gestor)->percentual_vendas ?: 0.0;
        $percDistribuidor = (float) optional($nota->pedido->distribuidor)->percentual_vendas ?: 0.0;

        // Comissões calculadas SOBRE O LÍQUIDO
        $comissaoGestor       = round($valorLiquido * ($percGestor / 100), 2);
        $comissaoDistribuidor = round($valorLiquido * ($percDistribuidor / 100), 2);

        // Advogado e Diretor (sobre o líquido)
        $percAdv = (float) ($data['perc_comissao_advogado'] ?? 0);
        $percDir = (float) ($data['perc_comissao_diretor']  ?? 0);

        $comissaoAdv = $adesaoAta && $percAdv > 0 ? round($valorLiquido * ($percAdv / 100), 2) : 0.0;
        $comissaoDir = $percDir > 0 ? round($valorLiquido * ($percDir / 100), 2) : 0.0;

        DB::transaction(function () use (
            $nota, $data,
            $retPer, $retVal,
            $valorLiquido,
            $percGestor, $percDistribuidor,
            $comissaoGestor, $comissaoDistribuidor,
            $comissaoAdv, $comissaoDir
        ) {
            \App\Models\NotaPagamento::create([
                'nota_fiscal_id' => $nota->id,

                'data_pagamento' => $data['data_pagamento'] ?? null,
                'valor_pago'     => $data['valor_pago'],
                'valor_liquido'  => $valorLiquido,

                // retenções (% + valor)
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

                // advogado/diretor
                'adesao_ata'             => (bool) ($data['adesao_ata'] ?? false),

                'advogado_id'            => $data['advogado_id'] ?? null,
                'perc_comissao_advogado' => $data['perc_comissao_advogado'] ?? null,
                'comissao_advogado'      => $comissaoAdv,

                'diretor_id'             => $data['diretor_id'] ?? null,
                'perc_comissao_diretor'  => $data['perc_comissao_diretor'] ?? null,
                'comissao_diretor'       => $comissaoDir,

                // gestor/distribuidor — SNAPSHOT
                'perc_comissao_gestor'        => $percGestor,
                'comissao_gestor'             => $comissaoGestor,
                'perc_comissao_distribuidor'  => $percDistribuidor,
                'comissao_distribuidor'       => $comissaoDistribuidor,
                'comissao_snapshot_at'        => now(),

                'observacoes' => $data['observacoes'] ?? null,
            ]);

            // Atualiza status financeiro da nota conforme sua regra
            $nota->refresh();
            $nota->atualizarStatusFinanceiro();
        });

        return redirect()
            ->route('admin.notas.show', $nota)
            ->with('success', 'Pagamento registrado com sucesso!');
    }


    public function show(NotaFiscal $nota, NotaPagamento $pagamento)
{
    if ($pagamento->nota_fiscal_id !== $nota->id) {
        abort(404);
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

    // Totais do pedido (visual)
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

    // ====== TUDO ABAIXO USA SNAPSHOT DO PAGAMENTO ======
    $valorPago    = (float) $pagamento->valor_pago;
    $valorLiquido = (float) $pagamento->valor_liquido;

    // Percentuais de retenção salvos (%)
    $ret = [
        'irrf'   => (float) ($pagamento->ret_irrf_perc   ?? 0),
        'iss'    => (float) ($pagamento->ret_iss_perc    ?? 0),
        'inss'   => (float) ($pagamento->ret_inss_perc   ?? 0),
        'pis'    => (float) ($pagamento->ret_pis_perc    ?? 0),
        'cofins' => (float) ($pagamento->ret_cofins_perc ?? 0),
        'csll'   => (float) ($pagamento->ret_csll_perc   ?? 0),
        'outros' => (float) ($pagamento->ret_outros_perc ?? 0),
    ];

    // Valores de retenção salvos (R$)
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

    // Comissões snapshot (NÃO recalcular com cadastro atual!)
    $percGestor       = (float) ($pagamento->perc_comissao_gestor       ?? 0);
    $percDistribuidor = (float) ($pagamento->perc_comissao_distribuidor ?? 0);

    $comissaoGestor       = (float) ($pagamento->comissao_gestor       ?? 0);
    $comissaoDistribuidor = (float) ($pagamento->comissao_distribuidor ?? 0);

    // Advogado/Diretor snapshot
    $percAdv = (float) ($pagamento->perc_comissao_advogado ?? 0);
    $percDir = (float) ($pagamento->perc_comissao_diretor  ?? 0);

    $comissaoAdv = (float) ($pagamento->comissao_advogado ?? 0);
    $comissaoDir = (float) ($pagamento->comissao_diretor  ?? 0);

    return view('admin.notas.pagamentos.show', compact(
        'nota',
        'pagamento',
        'pedido',
        'valorBrutoPedido',
        'totalDescontosPedido',
        'valorComDescontoPedido',
        'percGestor',
        'percDistribuidor',
        'comissaoGestor',
        'comissaoDistribuidor',
        'ret',
        'retValores',
        'totalRetencoes',
        'valorLiquido',
        'percAdv',
        'percDir',
        'comissaoAdv',
        'comissaoDir',
        'valorPago'
    ));
}

}
