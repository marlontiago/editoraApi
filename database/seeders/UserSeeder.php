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

        $admin->givePermissionTo([
            'pedido.criar',
            'relatorios.acessar',
            'gerenciar.usuarios',
            'estoque.gerenciar',
        ]);

    }
}
