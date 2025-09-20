<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'Admin', 'password' => Hash::make('admin123')]
        );
        if (method_exists($admin, 'assignRole')) {
            $admin->assignRole('admin');
        }

        // Gestor
        $gestorUser = User::firstOrCreate(
            ['email' => 'gestor@example.com'],
            ['name' => 'Gestor Exemplo', 'password' => Hash::make('gestor123')]
        );
        if (method_exists($gestorUser, 'assignRole')) {
            $gestorUser->assignRole('gestor');
        }

        // Distribuidor
        $distUser = User::firstOrCreate(
            ['email' => 'distribuidor@example.com'],
            ['name' => 'Distribuidor Exemplo', 'password' => Hash::make('distribuidor123')]
        );
        if (method_exists($distUser, 'assignRole')) {
            $distUser->assignRole('distribuidor');
        }
    }
}
