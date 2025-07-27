<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Venda extends Model
{
    use HasFactory;

    protected $fillable = ['distribuidor_id', 'produto_id', 'quantidade', 'valor_total', 'comissao'];

    public function distribuidor(){

        return $this->belongsTo(Distribuidor::class);

    }

    public function produto(){

        return $this->belongsTo(Produto::class);
        
    }
}
