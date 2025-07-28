<?php

namespace Database\Seeders;

use App\Models\Commission;
use App\Models\User;
use Illuminate\Database\Seeder;

class CommissionSeeder extends Seeder
{
    public function run(): void
    {
        $gestor = User::where('email', 'gestor@example.com')->first();
        $distribuidor = User::where('email', 'distribuidor@example.com')->first();

        Commission::insert([
            [
                'user_id' => $gestor->id,
                'percentage' => 5.00,
                'valid_from' => now()->toDateString(),
                'active' => true,
            ],
            [
                'user_id' => $distribuidor->id,
                'percentage' => 8.50,
                'valid_from' => now()->toDateString(),
                'active' => true,
            ],
        ]);
    }
}
