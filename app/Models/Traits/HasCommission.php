<?php

namespace App\Models\Traits;

use App\Models\Commission;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasCommission
{
    public function commissions(): HasMany
    {
        return $this->hasMany(Commission::class);
    }

    public function currentCommission(?\Carbon\Carbon $date = null): ?Commission
    {
        $date = $date ?? now();
        return $this->commissions()
            ->where(function ($q) use ($date) {
                $q->whereNull('valid_from')->orWhere('valid_from', '<=', $date);
            })
            ->where(function ($q) use ($date) {
                $q->whereNull('valid_to')->orWhere('valid_to', '>=', $date);
            })
            ->where('active', true)
            ->orderByDesc('created_at')
            ->first();
    }

    public function commissionPercentage(?\Carbon\Carbon $date = null): ?float
    {
        return optional($this->currentCommission($date))->percentage;
    }
}
