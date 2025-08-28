<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Advogado extends Model
{
    protected $table = 'advogados';

    protected $fillable = [
        'nome',
        'email',
        'telefone',
        'percentual_vendas',
        'oab',
        'logradouro',
        'numero',
        'complemento',
        'bairro',
        'cidade',
        'estado',
        'cep',
    ];

    public function user()
    {
        return $this->hasOne(User::class, 'advogado_id');
    }

    public function pedidos()
    {
        return $this->hasMany(Pedido::class, 'advogado_id');
    }

    public function notasFiscais()
    {
        return $this->hasMany(NotaFiscal::class, 'advogado_id');
    }

    public function notasPagamento()
    {
        return $this->hasMany(NotaPagamento::class, 'advogado_id');
    }
}
