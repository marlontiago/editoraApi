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
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\RelatorioNotasExport;

class RelatoriosController extends Controller
{
    public function index(\Illuminate\Http\Request $request)
{
    // ==========================
    // 0) Entradas & Filtros
    // ==========================
    $tipoRelatorio = $request->get('tipo_relatorio', 'geral'); // 'geral' | 'financeiro'
    $statusFiltro  = $request->get('status'); // opcional (mantido)

    // Exclusividade de selects (cliente > gestor > distribuidor)
    $clienteSel = (int) $request->get('cliente_select');
    $gestorSel  = (int) $request->get('gestor_select');
    $distSel    = (int) $request->get('distribuidor_select');

    if ($clienteSel) {
        $filtroTipo = 'cliente'; $filtroId = $clienteSel;
    } elseif ($gestorSel) {
        $filtroTipo = 'gestor';  $filtroId = $gestorSel;
    } elseif ($distSel) {
        $filtroTipo = 'distribuidor'; $filtroId = $distSel;
    } else {
        // retrocompat com ?tipo=&id=
        $filtroTipo = $request->get('tipo');
        $filtroId   = (int) $request->get('id');
    }

    $advogadoId = (int) $request->get('advogado_id');
    $diretorId  = (int) $request->get('diretor_id');
    $cidadeId   = (int) $request->get('cidade_id');

    // Datas (UI)
    $dataInicio = $request->input('data_inicio'); // YYYY-MM-DD
    $dataFim    = $request->input('data_fim');

    // UF
    $ufSelecionada = $request->get('uf');

    // Janela temporal normalizada
    $inicio = $dataInicio ? \Carbon\Carbon::parse($dataInicio)->startOfDay() : null;
    $fim    = $dataFim    ? \Carbon\Carbon::parse($dataFim)->endOfDay()     : null;
    if ($inicio && $fim && $inicio->gt($fim)) {
        [$inicio, $fim] = [$fim, $inicio];
    }
    $periodo = $inicio && $fim;

    // ==========================
    // 1) Selects fixos
    // ==========================
    $clientes       = \App\Models\Cliente::orderBy('razao_social')->get();
    $gestores       = \App\Models\Gestor::orderBy('razao_social')->get();
    $distribuidores = \App\Models\Distribuidor::orderBy('razao_social')->get();
    $advogados      = \App\Models\Advogado::orderBy('nome')->get();
    $diretores      = \App\Models\DiretorComercial::orderBy('nome')->get();

    // UFs disponíveis (a partir das cidades)
    $ufsOptions = \App\Models\City::query()
        ->select('state')
        ->distinct()
        ->orderBy('state')
        ->pluck('state');

    // ==========================
    // 2) Base: Notas (SEM canceladas) + filtros
    // ==========================
    $notasBase = \App\Models\NotaFiscal::query()
        ->where('status', '!=', 'cancelada')
        // IMPORTANTE: não usar ->with() aqui; o DEDUP usará essa base em subquery
        ;

    // Tipo de relatório
    if ($tipoRelatorio === 'financeiro') {
        // Somente notas pagas ou pago parcial
        $notasBase->whereIn('status_financeiro', ['pago', 'pago_parcial']);
    } else {
        // "geral": tudo (exceto canceladas), mas respeita statusFiltro se vier
        if (!empty($statusFiltro)) {
            if ($statusFiltro === 'aguardando_pagamento') {
                $notasBase->where(function ($q) {
                    $q->where('status_financeiro', 'aguardando_pagamento')
                      ->orWhere('status_financeiro', 'pago_parcial');
                });
            } elseif (in_array($statusFiltro, ['pago', 'faturada'])) {
                $notasBase->where('status_financeiro', $statusFiltro);
            } elseif ($statusFiltro === 'emitida') {
                $notasBase->whereNotNull('emitida_em');
            }
        }
    }

    // Período (emitida, faturada ou pagamentos no range)
    if ($periodo) {
        $notasBase->where(function ($q) use ($inicio, $fim) {
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

        $notasBase->whereHas('pedido', function ($q) use ($coluna, $filtroId) {
            $q->where($coluna, $filtroId);
        });
    }

    // Advogado
    if ($advogadoId > 0) {
        $notasBase->whereHas('pagamentos', function ($q) use ($advogadoId, $periodo, $inicio, $fim) {
            $q->where('advogado_id', $advogadoId);
            if ($periodo) $q->whereBetween('data_pagamento', [$inicio, $fim]);
        });
    }

    // Diretor
    if ($diretorId > 0) {
        $notasBase->whereHas('pagamentos', function ($q) use ($diretorId, $periodo, $inicio, $fim) {
            $q->where('diretor_id', $diretorId);
            if ($periodo) $q->whereBetween('data_pagamento', [$inicio, $fim]);
        });
    }

    // Cidade
    if ($cidadeId > 0) {
        $notasBase->whereHas('pedido.cidades', function ($q) use ($cidadeId) {
            $q->where('cities.id', $cidadeId);
        });
    }

    // UF
    if (!empty($ufSelecionada)) {
        $notasBase->whereHas('pedido.cidades', function ($q) use ($ufSelecionada) {
            $q->where('state', $ufSelecionada);
        });
    }

    // ==========================
    // 3) DEDUP por pedido (pega só a nota mais recente por pedido)
    //     -> Retorna só os IDs; depois recarregamos via Eloquent com with(...)
    // ==========================
    $tableNota = (new \App\Models\NotaFiscal)->getTable(); // normalmente "notas_fiscais"

    $dedup = function ($builder) use ($tableNota) {
        // Constrói uma subquery aplicando os MESMOS filtros do $builder
        $subBase = DB::query()
            ->fromSub((clone $builder)->select("{$tableNota}.*"), $tableNota)
            ->select("{$tableNota}.*")
            ->selectRaw('row_number() over (partition by pedido_id order by id desc) as rn');

        // Fica apenas com rn = 1 (nota mais recente de cada pedido)
        return DB::query()->fromSub($subBase, 'nf')->where('rn', 1);
    };

    // IDs finais (deduplicados)
    $notaIds = $dedup($notasBase)->orderByDesc('id')->pluck('id');

    // Agora carregamos as notas como MODELS com relações
    $notas = \App\Models\NotaFiscal::with([
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
        ])
        ->whereIn('id', $notaIds)
        ->orderByDesc('id')
        ->get();

    // ==========================
    // 4) Cidades do dropdown
    // ==========================
    if (!empty($ufSelecionada)) {
        $cidadesOptions = \App\Models\City::where('state', $ufSelecionada)
            ->orderBy('name')
            ->get(['id', 'name']);
    } else {
        $cityIds = $notas->flatMap(function ($n) {
            $pedido = $n->pedido;
            return $pedido && $pedido->cidades ? $pedido->cidades->pluck('id') : collect();
        })->unique()->values();

        $cidadesOptions = $cityIds->isEmpty()
            ? collect()
            : \App\Models\City::whereIn('id', $cityIds)->orderBy('name')->get(['id', 'name']);
    }

    // ==========================
    // 5) Totais e Breakdowns
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

    $gestoresDetalhe  = [];
    $distsDetalhe     = [];
    $advogadosDetalhe = [];
    $diretoresDetalhe = [];

    foreach ($notas as $nota) {
        $pedido = $nota->pedido;

        // pagamentos no período (quando houver)
        $pagamentos = $nota->pagamentos ?? collect();
        if ($periodo) {
            $pagamentos = $pagamentos->filter(function ($pg) use ($inicio, $fim) {
                $d = \Carbon\Carbon::parse($pg->data_pagamento);
                return $d->betweenIncluded($inicio, $fim);
            });
        }

        // líquido
        $liquido = (float) $pagamentos->sum('valor_liquido');
        $totais['total_liquido_pago'] += $liquido;

        // retenções (R$)
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

        // comissões gestor/distribuidor (snapshots)
        $comG = (float) $pagamentos->sum('comissao_gestor');
        $comD = (float) $pagamentos->sum('comissao_distribuidor');

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

    // Ordena breakdowns por valor
    $byTotalDesc = fn($a, $b) => $b['total'] <=> $a['total'];
    uasort($gestoresBreak,  $byTotalDesc);
    uasort($distsBreak,     $byTotalDesc);
    uasort($advogadosBreak, $byTotalDesc);
    uasort($diretoresBreak, $byTotalDesc);

    // ==========================
    // 6) Cards do topo (dedup por IDs)
    // ==========================
    $cardsDedup = function ($builder) use ($dedup) {
        // retorna apenas IDs; na view usamos ->count()
        return $dedup($builder)->pluck('id');
    };

    $notasPagas = $cardsDedup((clone $notasBase)->where('status_financeiro', 'pago'));
    $notasAPagar = $cardsDedup((clone $notasBase)->whereIn('status_financeiro', ['aguardando_pagamento', 'pago_parcial']));
    $notasEmitidas = $cardsDedup((clone $notasBase));

    // ==========================
    // 7) Export PDF
    // ==========================
    if ($request->get('export') === 'pdf') {
        if ($notas->isEmpty()) {
            return back()->with('error', 'Nenhum dado para exportar com os filtros atuais.');
        }

        $filename = ($tipoRelatorio === 'financeiro' ? 'relatorio_financeiro_' : 'relatorio_geral_')
            . now()->format('Ymd_His') . '.pdf';

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
            'tipoRelatorio'    => $tipoRelatorio, // título dinâmico no PDF (já tratado na sua view)
        ])->setPaper('a4', 'landscape');

        return $pdf->download($filename);
    }

    // ==========================
    // 7.1) Export CSV
    // ==========================
    if ($request->get('export') === 'csv') {
        if ($notas->isEmpty()) {
            return back()->with('error', 'Nenhum dado para exportar com os filtros atuais.');
        }

        $filename = ($tipoRelatorio === 'financeiro' ? 'relatorio_financeiro_' : 'relatorio_geral_')
            . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
            'Cache-Control'       => 'no-store, no-cache, must-revalidate, max-age=0',
        ];

        $callback = function () use ($notas, $dataInicio, $dataFim) {
            $out = fopen('php://output', 'w');

            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));

            fwrite($out, "sep=;\n");

            fputcsv($out, [
                'Pedido',
                'Nota',
                'Cliente',
                'Gestor',
                'Distribuidor',
                'Cidades',
                'Emitida em',
                'Faturada em',
                'Status financeiro',
                'Valor nota',
                'Pago (líquido)',

                'IRRF', 'ISS', 'PIS', 'COFINS', 'OUTROS', 'Retenções (total)',

                'Comissão Gestor',
                'Comissão Distribuidor',
                'Comissão Advogado',
                'Comissão Diretor',
                'Comissões (total)',
            ], ';');

            foreach ($notas as $n) {
                $pedido = $n->pedido;

                $pgts = collect($n->pagamentos);

                if (!empty($dataInicio) && !empty($dataFim)) {
                    $pgts = $pgts->filter(function ($pg) use ($dataInicio, $dataFim) {
                        if (empty($pg->data_pagamento)) return false;
                        $d = \Carbon\Carbon::parse($pg->data_pagamento)->toDateString();
                        return $d >= $dataInicio && $d <= $dataFim;
                    });
                }

                $liquido = (float) $pgts->sum('valor_liquido');

                $retIRRF   = (float) $pgts->sum('ret_irrf_valor');
                $retISS    = (float) $pgts->sum('ret_iss_valor');
                $retPIS    = (float) $pgts->sum('ret_pis_valor');
                $retCOFINS = (float) $pgts->sum('ret_cofins_valor');
                $retOUTROS = (float) $pgts->sum('ret_outros_valor');

                $retTotal = $retIRRF + $retISS + $retPIS + $retCOFINS + $retOUTROS;

                $comG   = (float) $pgts->sum('comissao_gestor');
                $comD   = (float) $pgts->sum('comissao_distribuidor');
                $comAdv = (float) $pgts->sum('comissao_advogado');
                $comDir = (float) $pgts->sum('comissao_diretor');
                $comTotal = $comG + $comD + $comAdv + $comDir;

                $cidades = ($pedido && $pedido->cidades)
                    ? $pedido->cidades->pluck('name')->join(', ')
                    : '—';

                fputcsv($out, [
                    $pedido->id ?? '',
                    $n->id,
                    $pedido->cliente->razao_social ?? '',
                    $pedido->gestor->razao_social ?? '',
                    $pedido->distribuidor->razao_social ?? '',
                    $cidades,
                    $n->emitida_em ? \Carbon\Carbon::parse($n->emitida_em)->format('d/m/Y') : '',
                    $n->faturada_em ? \Carbon\Carbon::parse($n->faturada_em)->format('d/m/Y') : '',
                    (string) ($n->status_financeiro ?? ''),
                    number_format((float)($n->valor_total ?? 0), 2, ',', '.'),
                    number_format($liquido, 2, ',', '.'),

                    // Retenções
                    number_format($retIRRF, 2, ',', '.'),
                    number_format($retISS, 2, ',', '.'),
                    number_format($retPIS, 2, ',', '.'),
                    number_format($retCOFINS, 2, ',', '.'),
                    number_format($retOUTROS, 2, ',', '.'),
                    number_format($retTotal, 2, ',', '.'),

                    // Comissões
                    number_format($comG, 2, ',', '.'),
                    number_format($comD, 2, ',', '.'),
                    number_format($comAdv, 2, ',', '.'),
                    number_format($comDir, 2, ',', '.'),
                    number_format($comTotal, 2, ',', '.'),
                ], ';');
            }

            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }


    // ==========================
    // 7.2) Export Excel (XLSX)
    // ==========================
    if ($request->get('export') === 'xlsx') {
        if ($notas->isEmpty()) {
            return back()->with('error', 'Nenhum dado para exportar com os filtros atuais.');
        }

        $filename = ($tipoRelatorio === 'financeiro' ? 'relatorio_financeiro_' : 'relatorio_geral_')
            . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(
            new RelatorioNotasExport($notas, $dataInicio, $dataFim),
            $filename
        );
    }


    // ==========================
    // 8) View
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
