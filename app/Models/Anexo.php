<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Anexo extends Model
{
    protected $table = 'anexos';

    protected $fillable = [
        'tipo','cidade_id','arquivo','descricao',
        'data_assinatura','data_vencimento','assinado',
        'percentual_vendas','ativo',
    ];

    protected $casts = [
        'assinado' => 'bool',
        'ativo'    => 'bool',
    ];

    public function anexavel(): MorphTo
    {
        return $this->morphTo();
    }

    public function cidade(): BelongsTo
    {
        return $this->belongsTo(City::class, 'cidade_id');
    }

    /* Scopes úteis */
    public function scopeAtivos($q){ return $q->where('ativo', true); }
    public function scopeTipo($q, $tipo){ return $q->where('tipo', $tipo); }
    public function scopeCidade($q, $cidadeId){ return $q->where('cidade_id', $cidadeId); }
}
