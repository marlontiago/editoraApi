<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    public function run(): void
{

    Role::firstOrCreate(['name' => 'admin']);
    Role::firstOrCreate(['name' => 'gestor']);
    Role::firstOrCreate(['name' => 'distribuidor']);

    $this->call([
        CitySeeder::class,
        UserSeeder::class,
        CommissionSeeder::class,
        CityVinculosSeeder::class,
        ProdutoSeeder::class,
    ]);
}
}
