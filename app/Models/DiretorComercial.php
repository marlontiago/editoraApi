<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiretorComercial extends Model
{
    protected $table = 'diretor_comercials';

    protected $fillable = [
        'nome',
        'email',
        'telefone',
        'percentual_vendas',
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
        return $this->hasOne(User::class, 'diretor_comercial_id');
    }

    public function pedidos()
    {
        return $this->hasMany(Pedido::class, 'diretor_comercial_id');
    }

    public function notasFiscais()
    {
        return $this->hasMany(NotaFiscal::class, 'diretor_comercial_id');
    }

    public function notasPagamento()
    {
        return $this->hasMany(NotaPagamento::class, 'diretor_comercial_id');
    }
    
}
