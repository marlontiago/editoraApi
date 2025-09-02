<?php

namespace App\Http\Controllers;

use App\Models\Advogado;
use App\Models\Cliente;
use App\Models\DiretorComercial;
use App\Models\Distribuidor;
use App\Models\Gestor;
use App\Models\NotaFiscal;
use App\Models\Pedido;
use Illuminate\Http\Request;

class RelatoriosController extends Controller
{
    public function index(Request $request)
    {
        // --- Filtros da requisição ---
        $statusFiltro = $request->get('status');        // 'pago' | 'aguardando_pagamento' | 'emitida'
        $filtroTipo   = $request->get('tipo');          // 'cliente' | 'gestor' | 'distribuidor'
        $filtroId     = (int) $request->get('id');      // id do usuário selecionado

        $dataInicio   = $request->input('data_inicio'); // YYYY-MM-DD
        $dataFim      = $request->input('data_fim');    // YYYY-MM-DD
        $periodo      = $dataInicio && $dataFim;        // só aplica quando tem as duas datas

        // --- Métricas topo (sem período; se quiser com período me avise) ---
        $notasPagas    = NotaFiscal::where('status_financeiro', 'pago')->get();
        $notasAPagar   = NotaFiscal::where('status_financeiro', 'aguardando_pagamento')->get();
        $notasEmitidas = NotaFiscal::all();

        // --- Selects para os filtros ---
        $clientes       = Cliente::orderBy('razao_social')->get();
        $gestores       = Gestor::orderBy('razao_social')->get();
        $distribuidores = Distribuidor::orderBy('razao_social')->get();
        $advogados      = Advogado::orderBy('nome')->get();
        $diretores      = DiretorComercial::orderBy('nome')->get();

        // --- Variáveis de saída ---
        $pedidos                 = collect(); // lista por cliente/gestor/distribuidor
        $totalComissoesDoFiltro  = 0.0;
        $pedidoStatus            = collect(); // lista quando clica no card de status OU só datas

        // =====================================================================
        // 1) LISTA PELOS CARDS (STATUS)  -> $pedidoStatus
        // =====================================================================
        if ($statusFiltro) {
            $pedidoStatus = Pedido::with([
                'cliente:id,razao_social',
                'gestor:id,razao_social',
                'distribuidor:id,razao_social',
                'notaFiscal:id,pedido_id,status_financeiro,valor_total,emitida_em',
                'notaFiscal.pagamentos' => function ($q) use ($periodo, $dataInicio, $dataFim) {
                    if ($periodo) {
                        // compara só a DATA, ignorando hora
                        $q->whereDate('data_pagamento', '>=', $dataInicio)
                          ->whereDate('data_pagamento', '<=', $dataFim);
                    }
                    $q->select(['id','nota_fiscal_id','valor_liquido','data_pagamento']);
                },
            ])
            ->whereHas('notaFiscal', function ($q) use ($statusFiltro, $periodo, $dataInicio, $dataFim) {
                if ($statusFiltro === 'emitida') {
                    $q->whereNotNull('emitida_em');
                    if ($periodo) {
                        $q->whereDate('emitida_em', '>=', $dataInicio)
                          ->whereDate('emitida_em', '<=', $dataFim);
                    }
                } else {
                    $q->where('status_financeiro', $statusFiltro);
                    if ($periodo) {
                        $q->whereHas('pagamentos', function ($p) use ($dataInicio, $dataFim) {
                            $p->whereDate('data_pagamento', '>=', $dataInicio)
                              ->whereDate('data_pagamento', '<=', $dataFim);
                        });
                    }
                }
            })
            ->orderByDesc('id')
            ->get();
        }

        // =====================================================================
        // 1.1) FALLBACK: SÓ PERÍODO (sem status/usuário) -> $pedidoStatus
        // =====================================================================
        if ($periodo && !$statusFiltro && !$filtroTipo) {
            $pedidoStatus = Pedido::with([
                'cliente:id,razao_social',
                'gestor:id,razao_social',
                'distribuidor:id,razao_social',
                'notaFiscal:id,pedido_id,status_financeiro,valor_total,emitida_em',
                'notaFiscal.pagamentos' => function ($q) use ($dataInicio, $dataFim) {
                    $q->whereDate('data_pagamento', '>=', $dataInicio)
                      ->whereDate('data_pagamento', '<=', $dataFim)
                      ->select(['id','nota_fiscal_id','valor_liquido','data_pagamento']);
                },
            ])
            ->whereHas('notaFiscal.pagamentos', function ($p) use ($dataInicio, $dataFim) {
                $p->whereDate('data_pagamento', '>=', $dataInicio)
                  ->whereDate('data_pagamento', '<=', $dataFim);
            })
            ->orderByDesc('id')
            ->get();
        }

        // =====================================================================
        // 2) LISTA POR CLIENTE / GESTOR / DISTRIBUIDOR  -> $pedidos
        //    (comissão sobre SOMA do valor_liquido dos pagamentos no período)
        // =====================================================================
        if (in_array($filtroTipo, ['cliente','gestor','distribuidor']) && $filtroId > 0) {
            $coluna = $filtroTipo === 'cliente' ? 'cliente_id'
                    : ($filtroTipo === 'gestor' ? 'gestor_id' : 'distribuidor_id');

            $query = Pedido::where($coluna, $filtroId)
                ->with([
                    'cliente:id,razao_social',
                    'distribuidor:id,razao_social,percentual_vendas',
                    'gestor:id,razao_social,percentual_vendas',
                    'notaFiscal:id,pedido_id,status_financeiro',
                    'notaFiscal.pagamentos' => function ($q) use ($periodo, $dataInicio, $dataFim) {
                        if ($periodo) {
                            $q->whereDate('data_pagamento', '>=', $dataInicio)
                            ->whereDate('data_pagamento', '<=', $dataFim);
                        }
                        $q->select(['id','nota_fiscal_id','valor_liquido','data_pagamento']);
                    },
                ]);

            // >>> AQUI: restringe a lista aos pedidos com pagamento no período
            if ($periodo) {
                $query->whereHas('notaFiscal.pagamentos', function ($p) use ($dataInicio, $dataFim) {
                    $p->whereDate('data_pagamento', '>=', $dataInicio)
                    ->whereDate('data_pagamento', '<=', $dataFim);
                });
            }

            $pedidos = $query->orderByDesc('id')->get();

            // Percentual do selecionado (cliente não tem comissão)
            $percentual = 0.0;
            if ($filtroTipo === 'gestor') {
                $percentual = (float) optional(Gestor::find($filtroId))->percentual_vendas ?: 0.0;
            } elseif ($filtroTipo === 'distribuidor') {
                $percentual = (float) optional(Distribuidor::find($filtroId))->percentual_vendas ?: 0.0;
            }

            $totalComissoesDoFiltro = 0.0;

            foreach ($pedidos as $p) {
                $valorLiquidoPago = 0.0;

                if ($p->notaFiscal && $p->notaFiscal->pagamentos) {
                    // pagamentos já vêm filtrados por data quando aplicável
                    $valorLiquidoPago = (float) $p->notaFiscal->pagamentos->sum('valor_liquido');
                }

                // campos "calculados" para a view
                $p->valor_liquido_pago_total = round($valorLiquidoPago, 2);
                $p->comissao_do_filtro       = round($valorLiquidoPago * ($percentual / 100), 2);

                $totalComissoesDoFiltro += $p->comissao_do_filtro;
            }

            $totalComissoesDoFiltro = round($totalComissoesDoFiltro, 2);
        }
        // ==========================
        // RESUMOS (para UI e PDF)
        // ==========================
        $resumoUsuario = [
            'qtd'             => 0,
            'valor_liquido'   => 0.0,
            'total_comissoes' => 0.0,
        ];

        if ($pedidos && $pedidos->count() > 0) {
            $resumoUsuario['qtd']             = $pedidos->count();
            $resumoUsuario['valor_liquido']   = (float) $pedidos->sum(fn($p) => (float) ($p->valor_liquido_pago_total ?? 0));
            $resumoUsuario['total_comissoes'] = (float) $pedidos->sum(fn($p) => (float) ($p->comissao_do_filtro ?? 0));
        }

        $resumoStatus = [
            'qtd'           => 0,
            'valor_total'   => 0.0,
        ];

        if ($pedidoStatus && $pedidoStatus->count() > 0) {
            $resumoStatus['qtd']         = $pedidoStatus->count();
            $resumoStatus['valor_total'] = (float) $pedidoStatus->sum(fn($p) => (float) optional($p->notaFiscal)->valor_total);
        }

        // ================================================================
        // EXPORTAÇÃO PDF
        // ================================================================
        if ($request->get('export') === 'pdf') {
            $exportPedidosUsuario = $pedidos && $pedidos->count() > 0;
            $exportPedidosStatus  = !$exportPedidosUsuario && $pedidoStatus && $pedidoStatus->count() > 0;

            if (! $exportPedidosUsuario && ! $exportPedidosStatus) {
                return back()->with('error', 'Nenhum dado para exportar com os filtros atuais.');
            }

            $filename = 'relatorio_' . now()->format('Ymd_His') . '.pdf';

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.relatorios.pdf', [
                'pedidos'        => $pedidos,
                'pedidoStatus'   => $pedidoStatus,
                'exportUsuario'  => $exportPedidosUsuario,
                'exportStatus'   => $exportPedidosStatus,
                'filtroTipo'     => $filtroTipo,
                'filtroId'       => $filtroId,
                'statusFiltro'   => $statusFiltro,
                'dataInicio'     => $dataInicio,
                'dataFim'        => $dataFim,
            ])->setPaper('a4', 'landscape');

            return $pdf->download($filename);
        }
        // --- Render ---
        return view('admin.relatorios.index', compact(
            'notasPagas',
            'notasAPagar',
            'notasEmitidas',
            'clientes',
            'gestores',
            'distribuidores',
            'advogados',
            'diretores',
            'pedidos',
            'filtroTipo',
            'filtroId',
            'totalComissoesDoFiltro',
            'pedidoStatus',
            'statusFiltro',
            'dataInicio',
            'dataFim',
            'resumoUsuario',
            'resumoStatus',
        ));
    }
}
