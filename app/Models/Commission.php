<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Commission extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'percentage', 'valid_from', 'valid_to', 'active', 'notes'
    ];

    protected $casts = [
        'valid_from' => 'date',
        'valid_to'   => 'date',
        'active'     => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeCurrent(Builder $query, $date = null)
    {
        $date = $date ?: now()->toDateString();

        return $query
            ->where(function ($q) use ($date) {
                $q->whereNull('valid_from')->orWhere('valid_from', '<=', $date);
            })
            ->where(function ($q) use ($date) {
                $q->whereNull('valid_to')->orWhere('valid_to', '>=', $date);
            })
            ->where('active', true)
            ->orderByDesc('created_at');
    }
}
