<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotaPagamento extends Model
{
    protected $table = 'nota_pagamentos';

    // Libera atribuição em massa para todos os campos validados no controller
    protected $guarded = [];

    protected $casts = [
        'data_pagamento'        => 'date',
        'adesao_ata'            => 'boolean',
        'comissao_snapshot_at'  => 'datetime',

        // Se quiser que o Eloquent já formate decimais na leitura, descomente:
        // 'valor_pago'             => 'decimal:2',
        // 'valor_liquido'          => 'decimal:2',
        // 'ret_irrf_valor'         => 'decimal:2',
        // 'ret_iss_valor'          => 'decimal:2',
        // 'ret_inss_valor'         => 'decimal:2',
        // 'ret_pis_valor'          => 'decimal:2',
        // 'ret_cofins_valor'       => 'decimal:2',
        // 'ret_csll_valor'         => 'decimal:2',
        // 'ret_outros_valor'       => 'decimal:2',
        // 'comissao_gestor'        => 'decimal:2',
        // 'comissao_distribuidor'  => 'decimal:2',
        // 'comissao_advogado'      => 'decimal:2',
        // 'comissao_diretor'       => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relações
    |--------------------------------------------------------------------------
    */
    public function nota()
    {
        return $this->belongsTo(NotaFiscal::class, 'nota_fiscal_id');
    }

    public function advogado()
    {
        return $this->belongsTo(Advogado::class, 'advogado_id');
    }

    public function diretor()
    {
        return $this->belongsTo(DiretorComercial::class, 'diretor_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors / Helpers (usando SNAPSHOTS salvos)
    |--------------------------------------------------------------------------
    */

    /**
     * Retenções em R$ (snapshots gravados no momento do pagamento).
     */
    public function getRetencoesValoresAttribute(): array
    {
        return [
            'irrf'   => (float) ($this->ret_irrf_valor   ?? 0),
            'iss'    => (float) ($this->ret_iss_valor    ?? 0),
            'inss'   => (float) ($this->ret_inss_valor   ?? 0),
            'pis'    => (float) ($this->ret_pis_valor    ?? 0),
            'cofins' => (float) ($this->ret_cofins_valor ?? 0),
            'csll'   => (float) ($this->ret_csll_valor   ?? 0),
            'outros' => (float) ($this->ret_outros_valor ?? 0),
        ];
    }

    /**
     * Soma de percentuais de retenção (%). Útil para exibir.
     */
    public function getRetencoesPercentuaisAttribute(): float
    {
        return (float) (
            ($this->ret_irrf_perc   ?? 0) +
            ($this->ret_iss_perc    ?? 0) +
            ($this->ret_inss_perc   ?? 0) +
            ($this->ret_pis_perc    ?? 0) +
            ($this->ret_cofins_perc ?? 0) +
            ($this->ret_csll_perc   ?? 0) +
            ($this->ret_outros_perc ?? 0)
        );
    }

    /**
     * Total de retenções em R$ (a partir dos snapshots).
     */
    public function getTotalRetencoesAttribute(): float
    {
        return round(array_sum($this->retencoes_valores), 2);
    }

    /**
     * Valor líquido preferindo o snapshot salvo; se estiver null, calcula fallback.
     */
    public function getValorLiquidoAttribute($value): float
    {
        if ($value !== null) {
            return (float) $value;
        }
        $valor = (float) ($this->valor_pago ?? 0);
        return round(max(0, $valor - $this->total_retencoes), 2);
    }
}
