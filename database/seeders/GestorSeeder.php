<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Gestor;
use Illuminate\Support\Facades\Hash;

class GestorSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrCreate([
            'email' => 'gestor@teste.com',
        ], [
            'name' => 'Gestor Exemplo',
            'password' => Hash::make('gestor123'),
        ]);

        $user->assignRole('gestor');

        Gestor::firstOrCreate([
            'user_id' => $user->id,
        ], [
            'nome_completo' => 'Gestor Exemplo',
            'telefone' => '41988887777',
        ]);
    }
}
