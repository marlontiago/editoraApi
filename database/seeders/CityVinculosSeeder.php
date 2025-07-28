<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Distribuidor;
use App\Models\Gestor;
use Illuminate\Database\Seeder;

class CityVinculosSeeder extends Seeder
{
    public function run(): void
    {
        $city = City::first(); // pega a primeira cidade criada
        $gestor = Gestor::first();
        $distribuidor = Distribuidor::first();

        if ($city && $gestor) {
            $gestor->cities()->syncWithoutDetaching([$city->id]);
        }

        if ($city && $distribuidor) {
            $distribuidor->cities()->syncWithoutDetaching([$city->id]);
        }
    }
}
