<?php

namespace Database\Seeders;

use App\Models\Advogado;
use App\Models\Pedido;
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
    Role::firstOrCreate(['name' => 'diretor_comercial']);
    Role::firstOrCreate(['name' => 'advogado']);
    Role::firstOrCreate(['name' => 'cliente']);

    $this->call([
        PermissionSeeder::class,
        UserSeeder::class,
        ClienteSeeder::class,
        AdvogadoSeeder::class,
        DiretorSeeder::class,
        CitiesSeeder::class,
        //CitySeeder::class,
        ColecaoSeeder::class,
        //ProdutoSeeder::class,
        CityVinculosSeeder::class,
        GestorSeeder::class,
        DistribuidorSeeder::class,
        //PedidoSeeder::class,
    ]);
    
}
}
