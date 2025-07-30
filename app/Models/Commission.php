<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Commission extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'tipo_usuario', 'percentage'
    ];

    protected $casts = [
        'percentage' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeGestores($query)
    {
        return $query->where('tipo_usuario', 'gestor');
    }

    public function scopeDistribuidores($query)
    {
        return $query->where('tipo_usuario', 'distribuidor');
    }
}
