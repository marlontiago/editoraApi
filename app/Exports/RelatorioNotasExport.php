<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class RelatorioNotasExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(
        protected Collection $notas,
        protected ?string $dataInicio,
        protected ?string $dataFim
    ) {}

    public function headings(): array
    {
        return [
            'Pedido','Nota','Cliente','Gestor','Distribuidor','Cidades','Emitida em','Faturada em','Status financeiro',
            'Valor nota','Pago (líquido)',

            'IRRF','ISS','PIS','COFINS','OUTROS','Retenções (total)',

            'Comissão Gestor','Comissão Distribuidor','Comissão Advogado','Comissão Diretor','Comissões (total)',
        ];
    }

    public function collection(): Collection
    {
        return $this->notas;
    }

    public function map($n): array
    {
        $pedido = $n->pedido;

        $pgts = $n->pagamentos ?? collect();
        if (!empty($this->dataInicio) && !empty($this->dataFim)) {
            $pgts = $pgts->filter(function ($pg) {
                $d = \Carbon\Carbon::parse($pg->data_pagamento)->toDateString();
                return $d >= $this->dataInicio && $d <= $this->dataFim;
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

        $ret = 0.0;
        foreach ([
            'ret_irrf_valor','ret_iss_valor',
            'ret_pis_valor','ret_cofins_valor','ret_outros_valor'
        ] as $campo) {
            $ret += (float) $pgts->sum($campo);
        }

        $cidades = $pedido && $pedido->cidades
            ? $pedido->cidades->pluck('name')->join(', ')
            : '—';

        return [
            $pedido->id ?? '',
            $n->id,
            $pedido->cliente->razao_social ?? '',
            $pedido->gestor->razao_social ?? '',
            $pedido->distribuidor->razao_social ?? '',
            $cidades,
            $n->emitida_em ? \Carbon\Carbon::parse($n->emitida_em)->format('d/m/Y') : '',
            $n->faturada_em ? \Carbon\Carbon::parse($n->faturada_em)->format('d/m/Y') : '',
            (string) ($n->status_financeiro ?? ''),
            (float) ($n->valor_total ?? 0),
            (float) $liquido,

            $retIRRF, $retISS, $retPIS, $retCOFINS, $retOUTROS, $retTotal,

            $comG, $comD, $comAdv, $comDir, $comTotal,
        ];

    }
}

