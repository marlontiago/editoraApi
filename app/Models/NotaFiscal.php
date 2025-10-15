<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;

class NotaFiscal extends Model
{
    use HasFactory;

    protected $table = 'notas_fiscais';

    protected $fillable = [
        'pedido_id',
        'numero', 'serie', 'status',
        'status_financeiro', 'pago_em',
        'valor_bruto', 'desconto_total', 'valor_total',
        'peso_total', 'total_caixas',
        'emitente_snapshot', 'destinatario_snapshot', 'pedido_snapshot',
        'chave_acesso', 'protocolo', 'ambiente',
        'emitida_em', 'faturada_em',
        'cancelada_em', 'motivo_cancelamento','plugnotas_id','plugnotas_status','pdf_url','xml_url',
    ];

    protected $casts = [
        'emitente_snapshot'     => 'array',
        'destinatario_snapshot' => 'array',
        'pedido_snapshot'       => 'array',
        'emitida_em'            => 'datetime',
        'faturada_em'           => 'datetime',
        'cancelada_em'          => 'datetime',
        'pago_em'               => 'datetime',
        'valor_bruto'           => 'decimal:2',
        'desconto_total'        => 'decimal:2',
        'valor_total'           => 'decimal:2',
        'peso_total'            => 'decimal:3',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relações
    |--------------------------------------------------------------------------
    */
    public function pedido()
    {
        return $this->belongsTo(Pedido::class, 'pedido_id');
    }

    public function itens()
    {
        return $this->hasMany(NotaItem::class);
    }

    public function pagamentos()
    {
        return $this->hasMany(NotaPagamento::class, 'nota_fiscal_id');
    }

    public function cliente()
    {
        return $this->hasOne(Cliente::class, 'id', 'pedido.cliente_id');
    }

    public function distribuidor()
    {
        return $this->HasOne(Distribuidor::class, 'id', 'pedido.distribuidor_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Escopos
    |--------------------------------------------------------------------------
    */
    public function scopeEmitidas($q)  { return $q->where('status', 'emitida'); }
    public function scopeFaturadas($q) { return $q->where('status', 'faturada'); }

    public function scopeAguardandoPagamento($q)
    {
        return $q->where('status', 'faturada')
                 ->where('status_financeiro', 'aguardando_pagamento');
    }

    public function scopePagas($q)
    {
        return $q->where('status_financeiro', 'pago');
    }

    /*
    |--------------------------------------------------------------------------
    | Acessors (Totais Financeiros)
    |--------------------------------------------------------------------------
    */
    // Total pago bruto (cliente efetivamente paga)
    public function getTotalPagoBrutoAttribute(): float
    {
        return (float) $this->pagamentos()->sum('valor_pago');
    }

    // Total pago líquido (interno, depois de retenções)
    public function getTotalPagoLiquidoAttribute(): float
    {
        return (float) $this->pagamentos()->sum('valor_liquido');
    }

    // Saldo pendente (deve bater com status_financeiro = pago)
    public function getSaldoPendenteAttribute(): float
    {
        $toCents = fn($v) => (int) round(((float) $v) * 100);

        $totalCents = $toCents($this->valor_total);
        $pagoCents  = $toCents($this->total_pago_bruto);

        $saldoCents = $totalCents - $pagoCents;
        return $saldoCents > 0 ? $saldoCents / 100 : 0.0;
    }

    /*
    |--------------------------------------------------------------------------
    | Regras de Status Financeiro
    |--------------------------------------------------------------------------
    */
    public function atualizarStatusFinanceiro(): void
    {
        // Se ainda não está faturada, padroniza como aguardando_pagamento
        if ($this->status !== 'faturada') {
            $this->forceFill([
                'status_financeiro' => 'aguardando_pagamento',
                'pago_em'           => null,
            ])->save();
            return;
        }

        // Soma BRUTA (o que o cliente pagou)
        $somaBruto = (float) $this->pagamentos()->sum('valor_pago');

        // Centavos para evitar problemas de float
        $toCents   = fn ($v) => (int) round(((float) $v) * 100);
        $totalCents= $toCents($this->valor_total);
        $somaCents = $toCents($somaBruto);

        $update = [];

        if ($somaCents <= 0) {
            // Nada pago
            if ($this->status_financeiro !== 'aguardando_pagamento') {
                $update['status_financeiro'] = 'aguardando_pagamento';
                $update['pago_em'] = null;
            }
        } elseif ($somaCents < $totalCents) {
            // Pago parcial
            if ($this->status_financeiro !== 'pago_parcial') {
                $update['status_financeiro'] = 'pago_parcial';
                $update['pago_em'] = null;
            }
        } else {
            // Quitado
            if ($this->status_financeiro !== 'pago') {
                $update['status_financeiro'] = 'pago';
                $update['pago_em'] = $this->pago_em ?: now();
            }
        }

        if ($update) {
            $this->forceFill($update)->save();
        }
    }

}
