<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Produto extends Model
{
    use HasFactory;

    protected $fillable = [
        'nome', 'descricao', 'preco', 'imagem', 'quantidade_estoque',
        'colecao_id', 'titulo', 'isbn', 'autores', 'edicao', 'ano',
        'numero_paginas', 'peso', 'ano_escolar'
    ];

    public function vendas()
    {

        return $this->belongsToMany(Venda::class)->withPivot('quantidade', 'preco_unitario');
        
    }

    public function colecao()
    {
        return $this->belongsTo(Colecao::class);
    }
}
