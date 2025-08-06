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
            'Coleção Aprender e Crescer',
            'Coleção Mundo do Saber',
            'Coleção Descobertas',
            'Coleção Leitura Viva',
            'Coleção Primeiros Passos',
        ];

        foreach ($colecoes as $nome) {
            Colecao::create(['nome' => $nome]);
        }
    }
}
