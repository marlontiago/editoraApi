<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Distribuidor extends Model
{
    use HasFactory;

    protected $table = 'distribuidores';
    protected $fillable = [
        'user_id',
        'gestor_id',
        'razao_social',
        'cnpj',
        'representante_legal',
        'cpf',
        'rg',
        'telefone',
        'endereco_completo',
        'percentual_vendas',
        'vencimento_contrato',
        'contrato_assinado',
        'contrato',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function gestor()
    {
        return $this->belongsTo(Gestor::class);
    }

    public function vendas()
    {
        return $this->hasMany(Venda::class);
    }

    public function cities()
    {
        return $this->belongsToMany(City::class);
    }
}
