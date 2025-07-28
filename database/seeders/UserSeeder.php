<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Gestor;
use App\Models\Distribuidor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('admin123'),
        ]);
        $admin->assignRole('admin');

        // Gestor
        $userGestor = User::create([
            'name' => 'Gestor Teste',
            'email' => 'gestor@example.com',
            'password' => Hash::make('senha123'),
        ]);
        $userGestor->assignRole('gestor');

        $gestor = Gestor::create([
            'user_id' => $userGestor->id,
            'nome_completo' => 'Gestor Teste',
            'telefone' => '41999999999',
        ]);

        // Distribuidor
        $userDistribuidor = User::create([
            'name' => 'Distribuidor Teste',
            'email' => 'distribuidor@example.com',
            'password' => Hash::make('senha123'),
        ]);
        $userDistribuidor->assignRole('distribuidor');

        Distribuidor::create([
            'user_id' => $userDistribuidor->id,
            'gestor_id' => $gestor->id,
            'nome_completo' => 'Distribuidor Teste',
            'telefone' => '41999999998',
        ]);
    }
}
