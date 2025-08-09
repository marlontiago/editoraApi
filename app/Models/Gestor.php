<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gestor extends Model
{
    use HasFactory;

    protected $table = 'gestores';
    protected $fillable = [
        'user_id',
        'estado_uf',
        'razao_social',
        'cnpj',
        'representante_legal',
        'cpf',
        'rg',
        'telefone',
        'email',
        'endereco_completo',
        'percentual_vendas',
        'vencimento_contrato',
        'contrato_assinado',
        'contrato',
    ];

    protected $casts = [
        'vencimento_contrato' => 'date',
    ];
    

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function distribuidores()
    {
        return $this->hasMany(Distribuidor::class);
    }

    public function cities()
    {
        return $this->belongsToMany(City::class, 'city_gestor');
    }
}
