<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{

    protected $table = 'clientes';
    protected $fillable = [
        'user_id',
        'razao_social',
        'email',
        'cnpj',
        'cpf',
        'rg',
        'telefone',
        'endereco_completo',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Adicione outros relacionamentos ou métodos conforme necessário
    public function pedidos()
    {
        return $this->hasMany(Pedido::class);
    }

}
