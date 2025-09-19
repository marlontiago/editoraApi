<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotaPagamento extends Model
{
    protected $table = 'nota_pagamentos';

    protected $fillable = [
        'nota_fiscal_id', 'data_pagamento', 'valor_pago',
        'ret_irrf','ret_iss','ret_inss','ret_pis','ret_cofins','ret_csll','ret_outros',
        'adesao_ata','advogado_id','perc_comissao_advogado',
        'diretor_id','perc_comissao_diretor',
        'valor_liquido','comissao_advogado','comissao_diretor',
        'observacoes',
    ];

    protected $casts = [
        'adesao_ata'     => 'boolean',
        'data_pagamento' => 'date',
        // se quiser garantir precisão ao ler:
        // 'valor_pago'     => 'decimal:2',
        // 'valor_liquido'  => 'decimal:2',
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

    // Pelo seu controller você usa as tabelas Advogado e DiretorComercial
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
    | Helpers / Accessors
    |--------------------------------------------------------------------------
    */

    /**
     * Retorna array com valores das retenções (em R$) calculados sobre valor_pago.
     * Ex.: ['irrf' => 12.34, 'iss' => 0.00, ...]
     */
    public function getRetencoesValoresAttribute(): array
    {
        $valor = (float) ($this->valor_pago ?? 0);
        $p = fn(string $k) => (float) ($this->{$k} ?? 0); // percentuais

        return [
            'irrf'   => $valor * ($p('ret_irrf')   / 100),
            'iss'    => $valor * ($p('ret_iss')    / 100),
            'inss'   => $valor * ($p('ret_inss')   / 100),
            'pis'    => $valor * ($p('ret_pis')    / 100),
            'cofins' => $valor * ($p('ret_cofins') / 100),
            'csll'   => $valor * ($p('ret_csll')   / 100),
            'outros' => $valor * ($p('ret_outros') / 100),
        ];
    }

    /**
     * Soma percentual total (em %) — útil se quiser exibir “x% retido”.
     */
    public function getRetencoesPercentuaisAttribute(): float
    {
        return (float) (
            ($this->ret_irrf   ?? 0) +
            ($this->ret_iss    ?? 0) +
            ($this->ret_inss   ?? 0) +
            ($this->ret_pis    ?? 0) +
            ($this->ret_cofins ?? 0) +
            ($this->ret_csll   ?? 0) +
            ($this->ret_outros ?? 0)
        );
    }

    /**
     * TOTAL de retenções em R$ (agora sim, dinheiro — não percentuais).
     */
    public function getTotalRetencoesAttribute(): float
    {
        return round(array_sum($this->retencoes_valores), 2);
    }

    /**
     * Valor líquido calculado (fallback): se não estiver preenchido no BD,
     * calcula como valor_pago - total_retencoes.
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
