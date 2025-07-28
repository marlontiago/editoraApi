<?php

namespace Database\Seeders;

use App\Models\City;
use Illuminate\Database\Seeder;

class CitySeeder extends Seeder
{
    public function run(): void
    {
        City::insert([
            ['name' => 'Curitiba', 'state' => 'PR'],
            ['name' => 'São Paulo', 'state' => 'SP'],
            ['name' => 'Rio de Janeiro', 'state' => 'RJ'],
            ['name' => 'Belo Horizonte', 'state' => 'MG'],
            ['name' => 'Porto Alegre', 'state' => 'RS'],
        ]);
    }
}
