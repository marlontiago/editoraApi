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
        'adesao_ata' => 'boolean',
        'data_pagamento' => 'date',
    ];

    public function nota()
    {
        return $this->belongsTo(NotaFiscal::class, 'nota_fiscal_id');
    }

    public function advogado()
    {
        return $this->belongsTo(User::class, 'advogado_id');
    }

    public function diretor()
    {
        return $this->belongsTo(User::class, 'diretor_id');
    }

    // Helpers
    public function getTotalRetencoesAttribute(): float
    {
        return (float) (
            ($this->ret_irrf ?? 0) + ($this->ret_iss ?? 0) + ($this->ret_inss ?? 0) +
            ($this->ret_pis ?? 0) + ($this->ret_cofins ?? 0) + ($this->ret_csll ?? 0) + ($this->ret_outros ?? 0)
        );
    }
}

