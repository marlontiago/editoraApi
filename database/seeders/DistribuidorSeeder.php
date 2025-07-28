<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Distribuidor;
use App\Models\Gestor;
use Illuminate\Support\Facades\Hash;

class DistribuidorSeeder extends Seeder
{
    public function run(): void
    {
        $gestor = Gestor::first();

        $user = User::firstOrCreate([
            'email' => 'distribuidor@teste.com',
        ], [
            'name' => 'Distribuidor Exemplo',
            'password' => Hash::make('distribuidor123'),
        ]);

        $user->assignRole('distribuidor');

        Distribuidor::firstOrCreate([
            'user_id' => $user->id,
        ], [
            'gestor_id' => $gestor->id,
            'nome_completo' => 'Distribuidor Exemplo',
            'telefone' => '41999998888',
        ]);
    }
}
