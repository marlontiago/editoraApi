<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Venda extends Model
{
    use HasFactory;

    protected $fillable = [
        'distribuidor_id',
        'gestor_id',
        'produto_id',
        'quantidade',
        'valor_total',
        'commission_percentage_snapshot',
        'commission_value_snapshot',
    ];

    public function distribuidor()
    {
        return $this->belongsTo(Distribuidor::class);
    }

    public function gestor()
    {
        return $this->belongsTo(Gestor::class);
    }

    public function produto()
    {
        return $this->belongsTo(Produto::class);
    }
}
