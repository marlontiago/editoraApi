<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
        'cancelada_em', 'motivo_cancelamento',
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
        return $this->belongsTo(Pedido::class);
    }

    public function itens()
    {
        return $this->hasMany(NotaItem::class);
    }

    public function pagamentos()
    {
        return $this->hasMany(NotaPagamento::class, 'nota_fiscal_id');
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
        // Soma BRUTA (o que o cliente paga)
        $somaBruto = (float) $this->pagamentos()->sum('valor_pago');

        // compara por centavos para evitar erro de float
        $toCents = fn($v) => (int) round(((float) $v) * 100);
        $totalCents = $toCents($this->valor_total);
        $somaCents  = $toCents($somaBruto);

        $quitado = $somaCents >= $totalCents;

        $update = [];
        if ($quitado) {
            if ($this->status_financeiro !== 'pago') {
                $update['status_financeiro'] = 'pago';
                $update['pago_em'] = now();
            }
        } else {
            if ($this->status === 'faturada' && $this->status_financeiro !== 'aguardando_pagamento') {
                $update['status_financeiro'] = 'aguardando_pagamento';
                $update['pago_em'] = null;
            }
        }

        if (!empty($update)) {
            $this->fill($update)->save();
        }
    }
}
