<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Produto extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'produtos';

    protected $fillable = [
        'codigo',
        'descricao',
        'preco',
        'imagem',
        'quantidade_estoque',
        'quantidade_por_caixa',
        'colecao_id',
        'titulo',
        'isbn',
        'autores',
        'edicao',
        'ano',
        'numero_paginas',
        'peso',
        'ano_escolar',
    ];

    protected $casts = [
        'preco'                => 'decimal:2',
        'peso'                 => 'decimal:3',
        'quantidade_estoque'   => 'integer',
        'quantidade_por_caixa' => 'integer',
        'ano'                  => 'integer',
    ];

    protected $appends = ['imagem_url'];

    // ===== Relationships =====
    public function vendas()
    {
        // Mantido para compatibilidade se você ainda usa Venda em algum lugar
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
                'caixas',
            ])
            ->withTimestamps();
    }

    // ===== Accessors =====
    public function getImagemUrlAttribute(): ?string
    {
        $raw = $this->imagem;
        if (!$raw) return null;

        $path = ltrim($raw, '/');

        // 1) URL absoluta
        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $raw;
        }

        // 2) Normaliza "storage/..." antigo para disco public
        if (Str::startsWith($path, 'storage/')) {
            $publicRel = Str::after($path, 'storage/');
            if (Storage::disk('public')->exists($publicRel)) {
                return Storage::disk('public')->url($publicRel);
            }
            if (file_exists(public_path($path))) {
                return asset($path);
            }
        }

        // 3) Caminho relativo no disco public (ex: "produtos/arquivo.jpg")
        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->url($path);
        }

        // 4) Arquivo direto em public/
        if (file_exists(public_path($path))) {
            return asset($path);
        }

        return null;
    }
}
