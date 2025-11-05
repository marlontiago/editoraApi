<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Colecao;

class ColecaoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
       
        Colecao::create(['codigo' => '3232',
                         'nome' => 'Conecta ENEM']);

        Colecao::create(['codigo' => '3233',
                         'nome' => 'Didático Profissional']);
        Colecao::create(['codigo' => '3234',
                         'nome' => 'Educação Financeira']);
        Colecao::create(['codigo' => '3235',
                         'nome' => 'Educação Trânsito']);
        Colecao::create(['codigo' => '3236',
                         'nome' => 'EJA - Educação Adultos']);
        Colecao::create(['codigo' => '3237',
                         'nome' => 'PNLD']);
        Colecao::create(['codigo' => '3238',
                         'nome' => 'Revisão dos Saberes']);
        Colecao::create(['codigo' => '3239',
                         'nome' => 'Robogarden']);
        Colecao::create(['codigo' => '3240',
                         'nome' => 'Sabe Brasil']);
    }
}
