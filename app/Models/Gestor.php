<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gestor extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'nome_completo', 'telefone'];

    public function user(){
        
        return $this->belongsTo(User::class);

    }

    public function distribuidores(){

        return $this->hasMany(Distribuidor::class);
        
    }
}
