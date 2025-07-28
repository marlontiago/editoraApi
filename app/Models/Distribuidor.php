<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Distribuidor extends Model
{
    use HasFactory;

    protected $table = 'distribuidores';
    protected $fillable = ['user_id', 'gestor_id', 'nome_completo', 'telefone'];

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
        return $this->belongsToMany(City::class, 'city_distribuidor');
    }
}
