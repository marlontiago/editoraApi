<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Support\Formatters;
use Illuminate\Support\Facades\Storage;

class Produto extends Model
{
    use HasFactory;

    protected $table = 'produtos';

    protected $fillable = [
        'nome', 'descricao', 'preco', 'imagem', 'quantidade_estoque','quantidade_por_caixa',
        'colecao_id', 'titulo', 'isbn', 'autores', 'edicao', 'ano',
        'numero_paginas', 'peso', 'ano_escolar'
    ];

    protected $casts = [
        'preco'               => 'decimal:2',
        'peso'                => 'decimal:3',
        'quantidade_estoque'  => 'integer',
        'quantidade_por_caixa'=> 'integer',
    ];

    public function vendas()
    {

        return $this->belongsToMany(Venda::class)->withPivot('quantidade', 'preco_unitario');
        
    }

    public function colecao()
    {
        return $this->belongsTo(Colecao::class);
    }

    public function pedidos()
    {
        return $this->belongsToMany(Pedido::class, 'pedido_produto')
            ->withPivot([
                'quantidade',
                'preco_unitario',
                'desconto_aplicado',
                'subtotal',
                'peso_total_produto',
                'caixas'
            ])
            ->withTimestamps();
    }

    public function getImagemUrlAttribute(): ?string
    {
        $path = $this->imagem;

        if (! $path) {
            return null;
        }

        // se estiver no disco "public" padrão
        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->url($path); // ex: /storage/produtos/arquivo.jpg
        }

        // fallback: se o caminho já for público (ex: começa com http)
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        // fallback final: tenta caminho público direto (se você salva 'storage/...' no DB)
        if (file_exists(public_path($path))) {
            return asset($path);
        }

        return null;
    }

}
