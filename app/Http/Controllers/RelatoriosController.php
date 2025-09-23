<?php

namespace App\Http\Controllers;

use App\Models\Advogado;
use App\Models\Cliente;
use App\Models\DiretorComercial;
use App\Models\Distribuidor;
use App\Models\Gestor;
use App\Models\NotaFiscal;
use App\Models\Pedido;
use App\Models\City; // <-- seu model correto é City
use Illuminate\Http\Request;
use Carbon\Carbon;

class RelatoriosController extends Controller
{
    public function index(Request $request)
    {
        // ==========================
        // Filtros (entrada do usuário)
        // ==========================
        $statusFiltro = $request->get('status');        // 'pago' | 'aguardando_pagamento' | 'emitida' | 'faturada'
        $filtroTipo   = $request->get('tipo');          // 'cliente' | 'gestor' | 'distribuidor'
        $filtroId     = (int) $request->get('id');

        $advogadoId   = (int) $request->get('advogado_id');
        $diretorId    = (int) $request->get('diretor_id');
        $cidadeId     = (int) $request->get('cidade_id');

        // Datas para UI
        $dataInicio = $request->input('data_inicio'); // YYYY-MM-DD
        $dataFim    = $request->input('data_fim');

        // Janela temporal normalizada (dia inteiro)
        $inicio = $dataInicio ? Carbon::parse($dataInicio)->startOfDay() : null;
        $fim    = $dataFim    ? Carbon::parse($dataFim)->endOfDay()     : null;
        if ($inicio && $fim && $inicio->gt($fim)) {
            [$inicio, $fim] = [$fim, $inicio];
        }
        $periodo = $inicio && $fim;

        // ==========================
        // Selects para os filtros fixos
        // ==========================
        $clientes       = Cliente::orderBy('razao_social')->get();
        $gestores       = Gestor::orderBy('razao_social')->get();
        $distribuidores = Distribuidor::orderBy('razao_social')->get();
        $advogados      = Advogado::orderBy('nome')->get();
        $diretores      = DiretorComercial::orderBy('nome')->get();
        // OBS: o dropdown de cidade será montado depois, com base nas notas filtradas.

        // ==========================
        // Base: Notas Fiscais
        // ==========================
        $notasQuery = NotaFiscal::query()
            ->with([
                'pedido:id,cliente_id,gestor_id,distribuidor_id',
                'pedido.cliente:id,razao_social',
                'pedido.gestor:id,razao_social,percentual_vendas',
                'pedido.distribuidor:id,razao_social,percentual_vendas',
                'pedido.cidades:id,name', // ajuste o relacionamento se necessário
                'pagamentos:id,nota_fiscal_id,valor_liquido,data_pagamento,ret_irrf,ret_iss,ret_inss,ret_pis,ret_cofins,ret_csll,ret_outros,comissao_advogado,comissao_diretor,perc_comissao_advogado,perc_comissao_diretor,advogado_id,diretor_id',
                'pagamentos.advogado:id,nome',
                'pagamentos.diretor:id,nome',
            ]);

        // Status
        if (!empty($statusFiltro)) {
            if (in_array($statusFiltro, ['pago','aguardando_pagamento','faturada'])) {
                $notasQuery->where('status_financeiro', $statusFiltro);
            } elseif ($statusFiltro === 'emitida') {
                $notasQuery->whereNotNull('emitida_em');
            }
        }

        // Período (considera emitida, faturada ou pagamento no range)
        if ($periodo) {
            $notasQuery->where(function ($q) use ($inicio, $fim) {
                $q->whereBetween('emitida_em', [$inicio, $fim])
                  ->orWhereBetween('faturada_em', [$inicio, $fim])
                  ->orWhereHas('pagamentos', function ($p) use ($inicio, $fim) {
                      $p->whereBetween('data_pagamento', [$inicio, $fim]);
                  });
            });
        }

        // Entidade principal (cliente/gestor/distribuidor)
        if (in_array($filtroTipo, ['cliente','gestor','distribuidor']) && $filtroId > 0) {
            $coluna = $filtroTipo === 'cliente' ? 'cliente_id'
                    : ($filtroTipo === 'gestor' ? 'gestor_id' : 'distribuidor_id');

            $notasQuery->whereHas('pedido', function ($q) use ($coluna, $filtroId) {
                $q->where($coluna, $filtroId);
            });
        }

        // Advogado
        if ($advogadoId > 0) {
            $notasQuery->whereHas('pagamentos', function ($q) use ($advogadoId, $periodo, $inicio, $fim) {
                $q->where('advogado_id', $advogadoId);
                if ($periodo) $q->whereBetween('data_pagamento', [$inicio, $fim]);
            });
        }
        // Diretor
        if ($diretorId > 0) {
            $notasQuery->whereHas('pagamentos', function ($q) use ($diretorId, $periodo, $inicio, $fim) {
                $q->where('diretor_id', $diretorId);
                if ($periodo) $q->whereBetween('data_pagamento', [$inicio, $fim]);
            });
        }
        // Cidade
        if ($cidadeId > 0) {
            $notasQuery->whereHas('pedido.cidades', function ($q) use ($cidadeId) {
                $q->where('cities.id', $cidadeId);
            });
        }

        $notas = $notasQuery->orderByDesc('id')->get();

        // ==========================
        // Dropdown de Cidades (apenas as que aparecem no resultado atual)
        // ==========================
        $cityIds = $notas->flatMap(function ($n) {
            $pedido = $n->pedido;
            return $pedido && $pedido->cidades ? $pedido->cidades->pluck('id') : collect();
        })->unique()->values();

        $cidadesOptions = $cityIds->isEmpty()
            ? collect() // nenhum resultado → dropdown vazio (ou você pode carregar todas com nota histórica, se preferir)
            : City::whereIn('id', $cityIds)->orderBy('name')->get(['id','name']);

        // ==========================
        // Agregações / Totais
        // ==========================
        $totais = [
            'qtd_notas'              => $notas->count(),
            'total_bruto'            => (float) $notas->sum(fn($n) => (float) ($n->valor_total ?? 0)),
            'total_liquido_pago'     => 0.0,
            'total_retencoes'        => 0.0,
            'retencoes_por_tipo'     => [
                'IRRF'   => 0.0, 'ISS' => 0.0, 'INSS' => 0.0,
                'PIS'    => 0.0, 'COFINS' => 0.0, 'CSLL' => 0.0, 'OUTROS' => 0.0,
            ],
            'comissao_gestor'        => 0.0,
            'comissao_distribuidor'  => 0.0,
            'comissao_advogado'      => 0.0,
            'comissao_diretor'       => 0.0,
            'total_descontado'       => 0.0,
        ];

        $gestoresBreak   = [];
        $distsBreak      = [];
        $advogadosBreak  = [];
        $diretoresBreak  = [];

        // Detalhamento item a item para os cards
        $gestoresDetalhe  = [];
        $distsDetalhe     = [];
        $advogadosDetalhe = [];
        $diretoresDetalhe = [];

        foreach ($notas as $nota) {
            $pedido = $nota->pedido;

            // pagamentos na janela
            $pagamentos = $nota->pagamentos ?? collect();
            if ($periodo) {
                $pagamentos = $pagamentos->filter(function ($pg) use ($inicio, $fim) {
                    $d = Carbon::parse($pg->data_pagamento);
                    // betweenIncluded cobre >= inicio e <= fim
                    return $d->betweenIncluded($inicio, $fim);
                });
            }

            // líquido e retenções
            $liquido = (float) $pagamentos->sum('valor_liquido');
            $totais['total_liquido_pago'] += $liquido;

            $map = ['IRRF'=>'ret_irrf','ISS'=>'ret_iss','INSS'=>'ret_inss','PIS'=>'ret_pis','COFINS'=>'ret_cofins','CSLL'=>'ret_csll','OUTROS'=>'ret_outros'];
            foreach ($map as $label => $campo) {
                $sub = (float) $pagamentos->sum($campo);
                $totais['retencoes_por_tipo'][$label] += $sub;
                $totais['total_retencoes']            += $sub;
            }

            // Gestor/Distribuidor (base = líquido da nota no período)
            $percGestor       = (float) ($pedido->gestor->percentual_vendas       ?? 0);
            $percDistribuidor = (float) ($pedido->distribuidor->percentual_vendas ?? 0);

            $comG = round($liquido * ($percGestor / 100), 2);
            $comD = round($liquido * ($percDistribuidor / 100), 2);

            $totais['comissao_gestor']       += $comG;
            $totais['comissao_distribuidor'] += $comD;

            if ($pedido && $pedido->gestor) {
                $gid  = (int) $pedido->gestor->id;
                $gnom = (string) ($pedido->gestor->razao_social ?? 'Gestor '.$gid);
                if (!isset($gestoresBreak[$gid])) {
                    $gestoresBreak[$gid] = ['nome'=>$gnom,'perc'=>$percGestor,'qtd'=>0,'total'=>0.0];
                }
                $gestoresBreak[$gid]['qtd']   += 1;
                $gestoresBreak[$gid]['total'] += $comG;

                if (!isset($gestoresDetalhe[$gid])) $gestoresDetalhe[$gid] = [];
                $gestoresDetalhe[$gid][] = [
                    'nota'     => (int) $nota->id,
                    'base'     => round($liquido, 2),
                    'perc'     => $percGestor,
                    'comissao' => $comG,
                ];
            }

            if ($pedido && $pedido->distribuidor) {
                $did  = (int) $pedido->distribuidor->id;
                $dnom = (string) ($pedido->distribuidor->razao_social ?? 'Distribuidor '.$did);
                if (!isset($distsBreak[$did])) {
                    $distsBreak[$did] = ['nome'=>$dnom,'perc'=>$percDistribuidor,'qtd'=>0,'total'=>0.0];
                }
                $distsBreak[$did]['qtd']   += 1;
                $distsBreak[$did]['total'] += $comD;

                if (!isset($distsDetalhe[$did])) $distsDetalhe[$did] = [];
                $distsDetalhe[$did][] = [
                    'nota'     => (int) $nota->id,
                    'base'     => round($liquido, 2),
                    'perc'     => $percDistribuidor,
                    'comissao' => $comD,
                ];
            }

            // Advogado / Diretor (por pagamento)
            foreach ($pagamentos as $pg) {
                // Advogado
                if (!empty($pg->advogado_id)) {
                    $aid   = (int) $pg->advogado_id;
                    $anome = (string) optional($pg->advogado)->nome ?? ("Advogado ".$aid);

                    $base   = (float) $pg->valor_liquido;
                    $percA  = (float) ($pg->perc_comissao_advogado ?? 0);
                    $valorA = (float) ($pg->comissao_advogado ?? 0);
                    if ($valorA <= 0 && $percA > 0) {
                        $valorA = round($base * ($percA / 100), 2);
                    }
                    // % efetivo
                    $percEfetivoA = $percA > 0 ? $percA : ($base > 0 ? round(($valorA / $base) * 100, 4) : 0.0);

                    $totais['comissao_advogado'] += $valorA;

                    if (!isset($advogadosBreak[$aid])) {
                        $advogadosBreak[$aid] = ['nome'=>$anome,'qtd'=>0,'total'=>0.0,'perc'=>$percA ?: null];
                    }
                    $advogadosBreak[$aid]['qtd']   += 1;
                    $advogadosBreak[$aid]['total'] += $valorA;

                    if (!isset($advogadosDetalhe[$aid])) $advogadosDetalhe[$aid] = [];
                    $advogadosDetalhe[$aid][] = [
                        'nota'     => (int) $nota->id,
                        'base'     => round($base, 2),
                        'perc'     => $percEfetivoA,
                        'comissao' => round($valorA, 2),
                    ];
                }

                // Diretor
                if (!empty($pg->diretor_id)) {
                    $did2  = (int) $pg->diretor_id;
                    $dnome = (string) optional($pg->diretor)->nome ?? ("Diretor ".$did2);

                    $base   = (float) $pg->valor_liquido;
                    $percD  = (float) ($pg->perc_comissao_diretor ?? 0);
                    $valorD = (float) ($pg->comissao_diretor ?? 0);
                    if ($valorD <= 0 && $percD > 0) {
                        $valorD = round($base * ($percD / 100), 2);
                    }
                    $percEfetivoD = $percD > 0 ? $percD : ($base > 0 ? round(($valorD / $base) * 100, 4) : 0.0);

                    $totais['comissao_diretor'] += $valorD;

                    if (!isset($diretoresBreak[$did2])) {
                        $diretoresBreak[$did2] = ['nome'=>$dnome,'qtd'=>0,'total'=>0.0,'perc'=>$percD ?: null];
                    }
                    $diretoresBreak[$did2]['qtd']   += 1;
                    $diretoresBreak[$did2]['total'] += $valorD;

                    if (!isset($diretoresDetalhe[$did2])) $diretoresDetalhe[$did2] = [];
                    $diretoresDetalhe[$did2][] = [
                        'nota'     => (int) $nota->id,
                        'base'     => round($base, 2),
                        'perc'     => $percEfetivoD,
                        'comissao' => round($valorD, 2),
                    ];
                }
            }
        }

        // Total descontado = Bruto - (Retenções + Comissões)
        $somaComissoes = $totais['comissao_gestor']
                       + $totais['comissao_distribuidor']
                       + $totais['comissao_advogado']
                       + $totais['comissao_diretor'];

        $totais['total_descontado'] = round(
            $totais['total_bruto'] - ($totais['total_retencoes'] + $somaComissoes),
            2
        );

        // Ordena breakdowns por valor desc
        $byTotalDesc = fn($a,$b) => $b['total'] <=> $a['total'];
        uasort($gestoresBreak, $byTotalDesc);
        uasort($distsBreak, $byTotalDesc);
        uasort($advogadosBreak, $byTotalDesc);
        uasort($diretoresBreak, $byTotalDesc);

        // ==========================
        // Cards do topo (contagens) – usa a mesma base de filtros
        // ==========================
        $base = clone $notasQuery;
        $notasPagas    = (clone $base)->where('status_financeiro', 'pago')->get();
        $notasAPagar   = (clone $base)->where('status_financeiro', 'aguardando_pagamento')->get();
        $notasEmitidas = (clone $base)->get();

        // ==========================
        // Export PDF
        // ==========================
        if ($request->get('export') === 'pdf') {
            if ($notas->isEmpty()) {
                return back()->with('error', 'Nenhum dado para exportar com os filtros atuais.');
            }

            $filename = 'relatorio_' . now()->format('Ymd_His') . '.pdf';
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.relatorios.pdf', [
                'notas'            => $notas,
                'totais'           => $totais,
                'gestoresBreak'    => $gestoresBreak,
                'distsBreak'       => $distsBreak,
                'advogadosBreak'   => $advogadosBreak,
                'diretoresBreak'   => $diretoresBreak,
                'gestoresDetalhe'  => $gestoresDetalhe,
                'distsDetalhe'     => $distsDetalhe,
                'advogadosDetalhe' => $advogadosDetalhe,
                'diretoresDetalhe' => $diretoresDetalhe,

                'statusFiltro'     => $statusFiltro,
                'filtroTipo'       => $filtroTipo,
                'filtroId'         => $filtroId,
                'advogadoId'       => $advogadoId,
                'diretorId'        => $diretorId,
                'cidadeId'         => $cidadeId,
                'dataInicio'       => $dataInicio,
                'dataFim'          => $dataFim,
            ])->setPaper('a4', 'landscape');

            return $pdf->download($filename);
        }

        // ==========================
        // View
        // ==========================
        return view('admin.relatorios.index', compact(
            'clientes','gestores','distribuidores','advogados','diretores',
            'statusFiltro','filtroTipo','filtroId','advogadoId','diretorId','cidadeId',
            'dataInicio','dataFim',
            'notas','totais',
            'gestoresBreak','distsBreak','advogadosBreak','diretoresBreak',
            'gestoresDetalhe','distsDetalhe','advogadosDetalhe','diretoresDetalhe',
            'notasPagas','notasAPagar','notasEmitidas',
            'cidadesOptions' // <-- dropdown de cidade com apenas cidades das notas do resultado
        ));
    }
}
