<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Anexo extends Model
{
    use HasFactory;

    protected $table = 'anexos';

    protected $fillable = [
        'tipo',
        'arquivo',
        'descricao',
        'data_assinatura',
        'data_vencimento',
        'assinado',
        'percentual_vendas',
        'ativo'
    ];

    public function anexavel()
    {
        return $this->morphTo();
    }
}
