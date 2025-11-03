<?php

namespace App\Http\Controllers;

use App\Models\Advogado;
use App\Models\Cliente;
use App\Models\DiretorComercial;
use App\Models\Distribuidor;
use App\Models\Gestor;
use App\Models\NotaFiscal;
use App\Models\City;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf; // <- export PDF

class RelatoriosController extends Controller
{
    public function index(Request $request)
    {
        // ==========================
        // Filtros (entrada do usuário)
        // ==========================
        $statusFiltro = $request->get('status'); // 'pago' | 'pago_parcial' | 'aguardando_pagamento' | 'emitida' | 'faturada'

        // NOVO: tipo de relatório
        $tipoRelatorio = $request->get('tipo_relatorio', 'financeiro');
        $tipoRelatorio = in_array($tipoRelatorio, ['financeiro','geral']) ? $tipoRelatorio : 'financeiro';

        // NOVO: ler diretamente os selects e resolver exclusividade
        $clienteSel = (int) $request->get('cliente_select');
        $gestorSel  = (int) $request->get('gestor_select');
        $distSel    = (int) $request->get('distribuidor_select');

        // Prioridade: Cliente > Gestor > Distribuidor (ajuste se quiser outra)
        if ($clienteSel) {
            $filtroTipo = 'cliente';
            $filtroId   = $clienteSel;
        } elseif ($gestorSel) {
            $filtroTipo = 'gestor';
            $filtroId   = $gestorSel;
        } elseif ($distSel) {
            $filtroTipo = 'distribuidor';
            $filtroId   = $distSel;
        } else {
            // retrocompatibilidade com ?tipo=&id=
            $filtroTipo = $request->get('tipo');          // 'cliente' | 'gestor' | 'distribuidor'
            $filtroId   = (int) $request->get('id');
        }

        $advogadoId   = (int) $request->get('advogado_id');
        $diretorId    = (int) $request->get('diretor_id');
        $cidadeId     = (int) $request->get('cidade_id');

        // Datas para UI
        $dataInicio = $request->input('data_inicio'); // YYYY-MM-DD
        $dataFim    = $request->input('data_fim');

        // NOVO: UF selecionada
        $ufSelecionada = $request->get('uf');

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

        // NOVO: opções de UF a partir das cidades
        $ufsOptions = City::query()
            ->select('state')           // coluna é 'state'
            ->distinct()
            ->orderBy('state')
            ->pluck('state');

        // ==========================
        // Base: Notas Fiscais
        // ==========================
        $notasQuery = NotaFiscal::query()
            ->with([
                'pedido:id,cliente_id,gestor_id,distribuidor_id',
                'pedido.cliente:id,razao_social',
                'pedido.gestor:id,razao_social,percentual_vendas',
                'pedido.distribuidor:id,razao_social,percentual_vendas',
                'pedido.cidades:id,name,state',
                'pagamentos:id,nota_fiscal_id,valor_pago,valor_liquido,data_pagamento,' .
                    'ret_irrf_valor,ret_iss_valor,ret_inss_valor,ret_pis_valor,ret_cofins_valor,ret_csll_valor,ret_outros_valor,' .
                    'comissao_gestor,perc_comissao_gestor,comissao_distribuidor,perc_comissao_distribuidor,' .
                    'comissao_advogado,perc_comissao_advogado,advogado_id,' .
                    'comissao_diretor,perc_comissao_diretor,diretor_id',
                'pagamentos.advogado:id,nome',
                'pagamentos.diretor:id,nome',
            ]);

        // Status (apenas quando usuário clica nos cards ou coloca manualmente)
        if (!empty($statusFiltro)) {
            if ($statusFiltro === 'aguardando_pagamento') {
                // Inclui também notas com status_financeiro 'pago_parcial'
                $notasQuery->where(function ($q) {
                    $q->where('status_financeiro', 'aguardando_pagamento')
                      ->orWhere('status_financeiro', 'pago_parcial');
                });
            } elseif (in_array($statusFiltro, ['pago', 'pago_parcial'])) {
                // Status financeiros
                $notasQuery->where('status_financeiro', $statusFiltro);
            } elseif ($statusFiltro === 'faturada') {
                // Status de nota (não financeiro)
                $notasQuery->where('status', 'faturada');
            } elseif ($statusFiltro === 'emitida') {
                $notasQuery->where('status', 'emitida');
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
        if (in_array($filtroTipo, ['cliente', 'gestor', 'distribuidor']) && $filtroId > 0) {
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

        // NOVO: UF (state)
        if (!empty($ufSelecionada)) {
            $notasQuery->whereHas('pedido.cidades', function ($q) use ($ufSelecionada) {
                $q->where('state', $ufSelecionada);
            });
        }

        // Recorte por tipo de relatório
        if ($tipoRelatorio === 'financeiro') {
            // Exclui CFOPs de Remessa e Bonificação/Brinde
            $notasQuery->paraRelatorioFinanceiro();
        }

        // >>> NOVO: recorte de status para o modo FINANCEIRO
        if ($tipoRelatorio === 'financeiro') {
            $notasQuery->where(function ($q) {
                $q->where('status_financeiro', 'pago')
                  ->orWhere(function ($q2) {
                      $q2->where('status', 'faturada')
                         ->whereIn('status_financeiro', ['aguardando_pagamento', 'pago_parcial']);
                  });
            });
        }

        // >>> Recorte final: "apenas última nota por pedido" + "ignorar canceladas"
        $tabelaNotas = (new NotaFiscal)->getTable();
        $notasQuery
            ->where('status', '!=', 'cancelada')
            ->whereIn('id', function ($sub) use ($tabelaNotas) {
                $sub->selectRaw('MAX(id)')
                    ->from($tabelaNotas)
                    ->groupBy('pedido_id');
            });

        $notas = $notasQuery->orderByDesc('id')->get();

        // ==========================
        // Dropdown de Cidades
        // ==========================
        if (!empty($ufSelecionada)) {
            // Todas as cidades da UF selecionada
            $cidadesOptions = City::where('state', $ufSelecionada)
                ->orderBy('name')
                ->get(['id', 'name']);
        } else {
            // Cidades presentes no resultado atual
            $cityIds = $notas->flatMap(function ($n) {
                $pedido = $n->pedido;
                return $pedido && $pedido->cidades ? $pedido->cidades->pluck('id') : collect();
            })->unique()->values();

            $cidadesOptions = $cityIds->isEmpty()
                ? collect()
                : City::whereIn('id', $cityIds)->orderBy('name')->get(['id', 'name']);
        }

        // ==========================
        // Agregações / Totais (usando snapshots!)
        // ==========================
        $totais = [
            'qtd_notas'              => $notas->count(),
            'total_bruto'            => (float) $notas->sum(fn($n) => (float) ($n->valor_total ?? 0)),
            'total_liquido_pago'     => 0.0,
            'total_retencoes'        => 0.0,
            'retencoes_por_tipo'     => [
                'IRRF'   => 0.0, 'ISS' => 0.0, 'INSS' => 0.0,
                'PIS'    => 0.0, 'COFINS' => 0.0, 'CSLL'  => 0.0, 'OUTROS' => 0.0,
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
                    return $d->betweenIncluded($inicio, $fim);
                });
            }

            // líquido (snapshot)
            $liquido = (float) $pagamentos->sum('valor_liquido');
            $totais['total_liquido_pago'] += $liquido;

            // retenções por tipo
            $map = [
                'IRRF'   => 'ret_irrf_valor',
                'ISS'    => 'ret_iss_valor',
                'INSS'   => 'ret_inss_valor',
                'PIS'    => 'ret_pis_valor',
                'COFINS' => 'ret_cofins_valor',
                'CSLL'   => 'ret_csll_valor',
                'OUTROS' => 'ret_outros_valor',
            ];
            foreach ($map as $label => $campo) {
                $sub = (float) $pagamentos->sum($campo);
                $totais['retencoes_por_tipo'][$label] += $sub;
                $totais['total_retencoes']            += $sub;
            }

            // Gestor/Distribuidor — snapshots de cada pagamento
            $comG = (float) $pagamentos->sum('comissao_gestor');
            $comD = (float) $pagamentos->sum('comissao_distribuidor');

            // percentuais (média no período)
            $percGestorSnap  = (float) $pagamentos->avg('perc_comissao_gestor');
            $percDistribSnap = (float) $pagamentos->avg('perc_comissao_distribuidor');

            $totais['comissao_gestor']       += $comG;
            $totais['comissao_distribuidor'] += $comD;

            if ($pedido && $pedido->gestor) {
                $gid  = (int) $pedido->gestor->id;
                $gnom = (string) ($pedido->gestor->razao_social ?? 'Gestor '.$gid);

                if (!isset($gestoresBreak[$gid])) {
                    $gestoresBreak[$gid] = ['nome'=>$gnom,'perc'=>$percGestorSnap,'qtd'=>0,'total'=>0.0];
                }
                $gestoresBreak[$gid]['qtd']   += 1;
                $gestoresBreak[$gid]['total'] += $comG;

                if (!isset($gestoresDetalhe[$gid])) $gestoresDetalhe[$gid] = [];
                $gestoresDetalhe[$gid][] = [
                    'nota'     => (int) $nota->id,
                    'base'     => round($liquido, 2),
                    'perc'     => $percGestorSnap,
                    'comissao' => round($comG, 2),
                ];
            }

            if ($pedido && $pedido->distribuidor) {
                $did  = (int) $pedido->distribuidor->id;
                $dnom = (string) ($pedido->distribuidor->razao_social ?? 'Distribuidor '.$did);

                if (!isset($distsBreak[$did])) {
                    $distsBreak[$did] = ['nome'=>$dnom,'perc'=>$percDistribSnap,'qtd'=>0,'total'=>0.0];
                }
                $distsBreak[$did]['qtd']   += 1;
                $distsBreak[$did]['total'] += $comD;

                if (!isset($distsDetalhe[$did])) $distsDetalhe[$did] = [];
                $distsDetalhe[$did][] = [
                    'nota'     => (int) $nota->id,
                    'base'     => round($liquido, 2),
                    'perc'     => $percDistribSnap,
                    'comissao' => round($comD, 2),
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
                        'perc'     => $percA,
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
                        'perc'     => $percD,
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
        $byTotalDesc = fn($a, $b) => $b['total'] <=> $a['total'];
        uasort($gestoresBreak,  $byTotalDesc);
        uasort($distsBreak,     $byTotalDesc);
        uasort($advogadosBreak, $byTotalDesc);
        uasort($diretoresBreak, $byTotalDesc);

        // ==========================
        // Cards do topo (respeitam o recorte 'financeiro' x 'geral')
        // ==========================
        $base = NotaFiscal::query();

        // Reaplica filtros essenciais nos cards:
        $base->with([
            'pedido:id,cliente_id,gestor_id,distribuidor_id',
            'pagamentos:id,nota_fiscal_id,valor_pago,valor_liquido,data_pagamento',
        ]);

        // Período
        if ($periodo) {
            $base->where(function ($q) use ($inicio, $fim) {
                $q->whereBetween('emitida_em', [$inicio, $fim])
                  ->orWhereBetween('faturada_em', [$inicio, $fim])
                  ->orWhereHas('pagamentos', function ($p) use ($inicio, $fim) {
                      $p->whereBetween('data_pagamento', [$inicio, $fim]);
                  });
            });
        }

        // Entidade principal
        if (in_array($filtroTipo, ['cliente','gestor','distribuidor']) && $filtroId > 0) {
            $coluna = $filtroTipo === 'cliente' ? 'cliente_id' : ($filtroTipo === 'gestor' ? 'gestor_id' : 'distribuidor_id');
            $base->whereHas('pedido', fn($q) => $q->where($coluna, $filtroId));
        }

        // Advogado
        if ($advogadoId > 0) {
            $base->whereHas('pagamentos', function ($q) use ($advogadoId, $periodo, $inicio, $fim) {
                $q->where('advogado_id', $advogadoId);
                if ($periodo) $q->whereBetween('data_pagamento', [$inicio, $fim]);
            });
        }

        // Diretor
        if ($diretorId > 0) {
            $base->whereHas('pagamentos', function ($q) use ($diretorId, $periodo, $inicio, $fim) {
                $q->where('diretor_id', $diretorId);
                if ($periodo) $q->whereBetween('data_pagamento', [$inicio, $fim]);
            });
        }

        // Cidade / UF
        if ($cidadeId > 0) {
            $base->whereHas('pedido.cidades', fn($q) => $q->where('cities.id', $cidadeId));
        }
        if (!empty($ufSelecionada)) {
            $base->whereHas('pedido.cidades', fn($q) => $q->where('state', $ufSelecionada));
        }

        // Recorte
        if ($tipoRelatorio === 'financeiro') {
            $base->paraRelatorioFinanceiro();
        }

        // >>> NOVO: recorte de status para o modo FINANCEIRO (cards)
        if ($tipoRelatorio === 'financeiro') {
            $base->where(function ($q) {
                $q->where('status_financeiro', 'pago')
                  ->orWhere(function ($q2) {
                      $q2->where('status', 'faturada')
                         ->whereIn('status_financeiro', ['aguardando_pagamento', 'pago_parcial']);
                  });
            });
        }

        // >>> aplicar o mesmo recorte dos relatórios (última por pedido + ignora canceladas)
        $base->where('status', '!=', 'cancelada')
             ->whereIn('id', function ($sub) use ($tabelaNotas) {
                 $sub->selectRaw('MAX(id)')
                     ->from($tabelaNotas)
                     ->groupBy('pedido_id');
             });

        $notasPagas    = (clone $base)->where('status_financeiro', 'pago')->get();
        $notasAPagar   = (clone $base)->where(function($q){
                                $q->where('status_financeiro','aguardando_pagamento')
                                  ->orWhere('status_financeiro','pago_parcial');
                           })->get();
        $notasEmitidas = (clone $base)->where('status','emitida')->get();

        // ==========================
        // EXPORT PDF (detecta ?export=pdf)
        // ==========================
        if ($request->get('export') === 'pdf') {
            $pdf = Pdf::loadView('admin.relatorios.pdf', [
                // mesmos dados da view HTML
                'clientes'          => $clientes,
                'gestores'          => $gestores,
                'distribuidores'    => $distribuidores,
                'advogados'         => $advogados,
                'diretores'         => $diretores,

                'statusFiltro'      => $statusFiltro,
                'filtroTipo'        => $filtroTipo,
                'filtroId'          => $filtroId,
                'advogadoId'        => $advogadoId,
                'diretorId'         => $diretorId,
                'cidadeId'          => $cidadeId,
                'dataInicio'        => $dataInicio,
                'dataFim'           => $dataFim,
                'ufSelecionada'     => $ufSelecionada,

                'notas'             => $notas,
                'totais'            => $totais,
                'gestoresBreak'     => $gestoresBreak,
                'distsBreak'        => $distsBreak,
                'advogadosBreak'    => $advogadosBreak,
                'diretoresBreak'    => $diretoresBreak,
                'gestoresDetalhe'   => $gestoresDetalhe,
                'distsDetalhe'      => $distsDetalhe,
                'advogadosDetalhe'  => $advogadosDetalhe,
                'diretoresDetalhe'  => $diretoresDetalhe,

                'ufsOptions'        => $ufsOptions,
                'cidadesOptions'    => $cidadesOptions,

                'tipoRelatorio'     => $tipoRelatorio,
            ])->setPaper('a4', 'landscape');

            return $pdf->download('relatorio-financeiro-'.now()->format('Ymd-His').'.pdf');
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
            'cidadesOptions',
            'ufsOptions','ufSelecionada',
            'tipoRelatorio'
        ));
    }
}
