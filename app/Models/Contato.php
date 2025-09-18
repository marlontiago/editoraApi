<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contato extends Model
{
    use HasFactory;

    protected $table = 'contatos';

    protected $fillable = [
        'nome',
        'email',
        'telefone',
        'whatsapp',
        'cargo',
        'tipo',
        'preferencial',
        'observacoes',
    ];

    protected $casts = [
        'preferencial' => 'boolean',
    ];

    // Dono do contato: Gestor, Distribuidor, etc.
    public function contatavel()
    {
        return $this->morphTo();
    }
}
