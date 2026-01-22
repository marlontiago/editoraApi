<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::firstOrCreate(['name' => 'pedido.criar']);
        Permission::firstOrCreate(['name' => 'relatorios.acessar']);
        Permission::firstOrCreate(['name' => 'gerenciar.usuarios']);
        Permission::firstOrCreate(['name' => 'estoque.gerenciar']);
        Permission::firstOrCreate(['name' => 'dashboard.acessar']);
    }
}
