<?php

namespace Database\Seeders;

use App\Models\City;
use Illuminate\Database\Seeder;

class CitySeeder extends Seeder
{
    public function run(): void
    {
        City::insert([
            ['name' => 'Rio Branco', 'state' => 'AC'],
            ['name' => 'Maceió', 'state' => 'AL'],
            ['name' => 'Macapá', 'state' => 'AP'],
            ['name' => 'Manaus', 'state' => 'AM'],
            ['name' => 'Salvador', 'state' => 'BA'],
            ['name' => 'Fortaleza', 'state' => 'CE'],
            ['name' => 'Brasília', 'state' => 'DF'],
            ['name' => 'Vitória', 'state' => 'ES'],
            ['name' => 'Goiânia', 'state' => 'GO'],
            ['name' => 'São Luís', 'state' => 'MA'],
            ['name' => 'Cuiabá', 'state' => 'MT'],
            ['name' => 'Campo Grande', 'state' => 'MS'],
            ['name' => 'Belo Horizonte', 'state' => 'MG'],
            ['name' => 'Belém', 'state' => 'PA'],
            ['name' => 'João Pessoa', 'state' => 'PB'],
            ['name' => 'Curitiba', 'state' => 'PR'],
            ['name' => 'Recife', 'state' => 'PE'],
            ['name' => 'Teresina', 'state' => 'PI'],
            ['name' => 'Rio de Janeiro', 'state' => 'RJ'],
            ['name' => 'Natal', 'state' => 'RN'],
            ['name' => 'Porto Alegre', 'state' => 'RS'],
            ['name' => 'Porto Velho', 'state' => 'RO'],
            ['name' => 'Boa Vista', 'state' => 'RR'],
            ['name' => 'Florianópolis', 'state' => 'SC'],
            ['name' => 'São Paulo', 'state' => 'SP'],
            ['name' => 'Aracaju', 'state' => 'SE'],
            ['name' => 'Palmas', 'state' => 'TO'],
        ]);
    }
}
