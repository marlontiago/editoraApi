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

        $rules = [
            'data_pagamento'           => ['nullable','date'],
            'valor_pago'               => ['required','numeric','min:0.01'],

            // Retenções em % (0 a 100)
            'ret_irrf'                 => ['nullable','numeric','min:0','max:100'],
            'ret_iss'                  => ['nullable','numeric','min:0','max:100'],
            'ret_inss'                 => ['nullable','numeric','min:0','max:100'],
            'ret_pis'                  => ['nullable','numeric','min:0','max:100'],
            'ret_cofins'               => ['nullable','numeric','min:0','max:100'],
            'ret_csll'                 => ['nullable','numeric','min:0','max:100'],
            'ret_outros'               => ['nullable','numeric','min:0','max:100'],

            'adesao_ata'               => ['nullable','boolean'],

            // Agora validamos contra as tabelas dos CRUDs
            'advogado_id'              => ['nullable','exists:advogados,id'],
            'perc_comissao_advogado'   => ['nullable','numeric','min:0','max:100'],

            'diretor_id'               => ['nullable','exists:diretor_comercials,id'],
            'perc_comissao_diretor'    => ['nullable','numeric','min:0','max:100'],

            'observacoes'              => ['nullable','string','max:2000'],
        ];

        $data = $request->validate($rules);

        // Checkbox
        $adesaoAta = (bool) ($data['adesao_ata'] ?? false);

        // Se marcar adesão, exige advogado e %
        if ($adesaoAta && (empty($data['advogado_id']))) {
            return back()->withInput()->withErrors([
                'advogado_id' => 'Informe o advogado para adesão à ata.'
            ]);
        }

        // Se marcou adesão e não informou o % do advogado, pega do cadastro
        if ($adesaoAta && ($data['advogado_id'] ?? null) && ($data['perc_comissao_advogado'] === null)) {
            $auto = Advogado::whereKey($data['advogado_id'])->value('percentual_vendas');
            dump($auto);
            if ($auto !== null) {
                $data['perc_comissao_advogado'] = (float) $auto;
            }
        }

        // Se escolheu diretor e não informou %, pega do cadastro
        if (($data['diretor_id'] ?? null) && ($data['perc_comissao_diretor'] === null)) {
            $auto = DiretorComercial::whereKey($data['diretor_id'])->value('percentual_vendas');
            if ($auto !== null) {
                $data['perc_comissao_diretor'] = (float) $auto;
            }
        }

        // Base para cálculo
        $valorPago = (float) $data['valor_pago'];

        // Converte percentuais de retenção (% -> R$) e soma total
        $p = fn($k) => (float) ($data[$k] ?? 0);
        $vIRRF   = $valorPago * ($p('ret_irrf')   / 100);
        $vISS    = $valorPago * ($p('ret_iss')    / 100);
        $vINSS   = $valorPago * ($p('ret_inss')   / 100);
        $vPIS    = $valorPago * ($p('ret_pis')    / 100);
        $vCOFINS = $valorPago * ($p('ret_cofins') / 100);
        $vCSLL   = $valorPago * ($p('ret_csll')   / 100);
        $vOUTROS = $valorPago * ($p('ret_outros') / 100);

        $totalRet     = $vIRRF + $vISS + $vINSS + $vPIS + $vCOFINS + $vCSLL + $vOUTROS;
        $valorLiquido = max(0, $valorPago - $totalRet);

        // Comissões (SEMPRE sobre o líquido)
        $comissaoAdv = 0.0;
        $comissaoDir = 0.0;

        if ($adesaoAta && $data['perc_comissao_advogado'] !== null) {
            $comissaoAdv = round($valorLiquido * ((float)$data['perc_comissao_advogado'] / 100), 2);
        }
        if ($data['perc_comissao_diretor'] !== null) {
            $comissaoDir = round($valorLiquido * ((float)$data['perc_comissao_diretor'] / 100), 2);
        }

        DB::transaction(function () use ($nota, $data, $valorLiquido, $comissaoAdv, $comissaoDir) {
            NotaPagamento::create([
                'nota_fiscal_id'          => $nota->id,
                'data_pagamento'          => $data['data_pagamento'] ?? null,
                'valor_pago'              => $data['valor_pago'],

                'ret_irrf'                => $data['ret_irrf']   ?? null,
                'ret_iss'                 => $data['ret_iss']    ?? null,
                'ret_inss'                => $data['ret_inss']   ?? null,
                'ret_pis'                 => $data['ret_pis']    ?? null,
                'ret_cofins'              => $data['ret_cofins'] ?? null,
                'ret_csll'                => $data['ret_csll']   ?? null,
                'ret_outros'              => $data['ret_outros'] ?? null,

                'adesao_ata'              => (bool) ($data['adesao_ata'] ?? false),

                // IDs agora são das tabelas dos CRUDs
                'advogado_id'             => $data['advogado_id'] ?? null,
                'perc_comissao_advogado'  => $data['perc_comissao_advogado'] ?? null,

                'diretor_id'              => $data['diretor_id'] ?? null,
                'perc_comissao_diretor'   => $data['perc_comissao_diretor'] ?? null,

                'valor_liquido'           => $valorLiquido,
                'comissao_advogado'       => $comissaoAdv,
                'comissao_diretor'        => $comissaoDir,

                'observacoes'             => $data['observacoes'] ?? null,
            ]);

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

        $percGestor       = (float) optional($pedido->gestor)->percentual_vendas ?: 0.0;
        $percDistribuidor = (float) optional($pedido->distribuidor)->percentual_vendas ?: 0.0;

        $valorPago = (float) $pagamento->valor_pago;
        $ret = [
            'irrf'   => (float) ($pagamento->ret_irrf   ?? 0),
            'iss'    => (float) ($pagamento->ret_iss    ?? 0),
            'inss'   => (float) ($pagamento->ret_inss   ?? 0),
            'pis'    => (float) ($pagamento->ret_pis    ?? 0),
            'cofins' => (float) ($pagamento->ret_cofins ?? 0),
            'csll'   => (float) ($pagamento->ret_csll   ?? 0),
            'outros' => (float) ($pagamento->ret_outros ?? 0),
        ];
        $retValores = [
            'irrf'   => $valorPago * ($ret['irrf']   / 100),
            'iss'    => $valorPago * ($ret['iss']    / 100),
            'inss'   => $valorPago * ($ret['inss']   / 100),
            'pis'    => $valorPago * ($ret['pis']    / 100),
            'cofins' => $valorPago * ($ret['cofins'] / 100),
            'csll'   => $valorPago * ($ret['csll']   / 100),
            'outros' => $valorPago * ($ret['outros'] / 100),
        ];
        $totalRetencoes = array_sum($retValores);

        $valorLiquido = (float) ($pagamento->valor_liquido ?? max(0, $valorPago - $totalRetencoes));

        $comissaoGestor       = round($valorLiquido * ($percGestor / 100), 2);
        $comissaoDistribuidor = round($valorLiquido * ($percDistribuidor / 100), 2);

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
