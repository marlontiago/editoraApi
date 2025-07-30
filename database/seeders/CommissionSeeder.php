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

        Commission::create([
            'user_id' => 2,
            'tipo_usuario' => 'gestor',
            'percentage' => 5.00,
        ]);

        Commission::create([
            'user_id' => 3,
            'tipo_usuario' => 'distribuidor',
            'percentage' => 8.50,
        ]);
    }
}
