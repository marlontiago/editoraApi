<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comissao extends Model
{
    protected $table = 'commissions'; // <-- Nome correto da tabela

    protected $fillable = [
        'user_id',
        'percentage',
        'valid_from',
        'active',
    ];

    protected $casts = [
    'valid_from' => 'date',
    'valid_to' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
