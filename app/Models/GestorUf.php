<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GestorUf extends Model
{
    protected $table = 'gestor_ufs';
    protected $fillable = ['gestor_id','uf'];

    public function gestor()
    {
        return $this->belongsTo(Gestor::class);
    }
}
