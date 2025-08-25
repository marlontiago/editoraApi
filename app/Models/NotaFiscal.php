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
        'valor_bruto', 'desconto_total', 'valor_total',
        'peso_total', 'total_caixas',
        'emitente_snapshot', 'destinatario_snapshot', 'pedido_snapshot',
        'chave_acesso', 'protocolo', 'ambiente',
        'emitida_em', 'faturada_em',
    ];

    protected $casts = [
        'emitente_snapshot'     => 'array',
        'destinatario_snapshot' => 'array',
        'pedido_snapshot'       => 'array',
        'emitida_em'            => 'datetime',
        'faturada_em'           => 'datetime',
    ];

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

    public function scopeEmitidas($q)  { return $q->where('status','emitida'); }
    public function scopeFaturadas($q) { return $q->where('status','faturada'); }
}
