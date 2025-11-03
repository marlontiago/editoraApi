<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Colecao extends Model
{
    use HasFactory;
    
    protected $table = 'colecoes';

    protected $fillable = ['nome'];

    public function produtos()
    {
        return $this->hasMany(Produto::class, 'colecao_id');
    }
}
