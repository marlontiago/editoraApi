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
        $colecoes = [
            'Conecta ENEM',
            'Didático Profissional',
            'Educação Financeira',
            'Educação Trânsito',
            'EJA - Educação Adultos',
            'PNLD',
            'Revisão dos Saberes',
            'Robogarden',
            'Sabe Brasil',
        ];

        foreach ($colecoes as $nome) {
            Colecao::create(['nome' => $nome]);
        }
    }
}
