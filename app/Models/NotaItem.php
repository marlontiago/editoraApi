<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class NotaItem extends Model
{
    use HasFactory;

    protected $table = 'nota_itens';

    protected $fillable = [
        'nota_fiscal_id', 'produto_id',
        'quantidade', 'preco_unitario', 'desconto_aplicado',
        'subtotal', 'peso_total_produto', 'caixas',
        'descricao_produto', 'isbn', 'titulo',
    ];

    public function nota()
    {
        return $this->belongsTo(NotaFiscal::class, 'nota_fiscal_id');
    }

    public function produto()
    {
        return $this->belongsTo(Produto::class);
    }
}
