<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PedidoLog extends Model
{
    protected $table = 'pedido_logs';
    protected $fillable = ['pedido_id','user_id','acao','detalhes','changes'];

    protected $casts = [
        'changes' => 'array',
    ];

    public function pedido()
    { 
        return $this->belongsTo(Pedido::class); 
    }
    public function user()
    { 
        return $this->belongsTo(User::class);
    }
}
