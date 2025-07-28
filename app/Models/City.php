<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'state', 'ibge_code'];

    public function gestores()
    {
        return $this->belongsToMany(Gestor::class, 'city_gestor');
    }

    public function distribuidores()
    {
        return $this->belongsToMany(Distribuidor::class, 'city_distribuidor');
    }
}
