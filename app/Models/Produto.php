<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
        'preco'                => 'decimal:2',
        'peso'                 => 'decimal:3',
        'quantidade_estoque'   => 'integer',
        'quantidade_por_caixa' => 'integer',
    ];

    protected $appends = ['imagem_url'];

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
        $raw = $this->imagem;
        if (!$raw) return null;

        $path = ltrim($raw, '/');

        // 1) URL absoluta já pronta
        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $raw;
        }

        // 2) Se veio com "storage/..." do seeder antigo, normaliza para o disco "public"
        if (Str::startsWith($path, 'storage/')) {
            $publicRel = Str::after($path, 'storage/'); // ex: "produtos/arquivo.jpg"
            if (Storage::disk('public')->exists($publicRel)) {
                return Storage::disk('public')->url($publicRel); // "/storage/produtos/arquivo.jpg"
            }
            // fallback: talvez o arquivo esteja mesmo no public/
            if (file_exists(public_path($path))) {
                return asset($path);
            }
        }

        // 3) Caminho relativo do disco "public" (ex: "produtos/arquivo.jpg")
        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->url($path); // "/storage/produtos/arquivo.jpg"
        }

        // 4) Arquivo direto em public/ (ex: "images/arquivo.jpg" ou "storage/produtos/arquivo.jpg")
        if (file_exists(public_path($path))) {
            return asset($path);
        }

        return null;
    }
}
