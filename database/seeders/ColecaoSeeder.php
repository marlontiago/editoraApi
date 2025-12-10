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
        // Já existentes
        Colecao::create(['codigo' => '3232', 'nome' => 'Conecta ENEM']);
        Colecao::create(['codigo' => '3233', 'nome' => 'Didático Profissional']);
        Colecao::create(['codigo' => '3234', 'nome' => 'Educação Financeira']);
        Colecao::create(['codigo' => '3235', 'nome' => 'Educação Trânsito']);
        Colecao::create(['codigo' => '3236', 'nome' => 'EJA - Educação Adultos']);
        Colecao::create(['codigo' => '3237', 'nome' => 'PNLD']);
        Colecao::create(['codigo' => '3238', 'nome' => 'Revisão dos Saberes']);
        Colecao::create(['codigo' => '3239', 'nome' => 'Robogarden']);
        Colecao::create(['codigo' => '3240', 'nome' => 'Sabe Brasil']);

        // Novos (sem repetir nomes já existentes)
        Colecao::create(['codigo' => '3241', 'nome' => 'Educação para o trânsito']);
        Colecao::create(['codigo' => '3242', 'nome' => 'Projeto Consciente de Educação Financeira']);
        Colecao::create(['codigo' => '3243', 'nome' => 'Coleção Conecta ENEM']);
        Colecao::create(['codigo' => '3244', 'nome' => 'Educação Financeira Sustentável']);
        Colecao::create(['codigo' => '3245', 'nome' => 'Projeto Educação em Saúde']);
        Colecao::create(['codigo' => '3246', 'nome' => 'Projeto Ação de Estímulo à Leitura e Escrita']);
        Colecao::create(['codigo' => '3247', 'nome' => 'Projeto Jovem Brasileiro']);
        Colecao::create(['codigo' => '3248', 'nome' => 'INTELIGÊNIOS']);
        Colecao::create(['codigo' => '3249', 'nome' => 'Projeto Histórias do Brasil Afro-Indígena']);
        Colecao::create(['codigo' => '3250', 'nome' => 'Projeto Peripécias']);
        Colecao::create(['codigo' => '3251', 'nome' => 'Projeto Robogarden']);
        Colecao::create(['codigo' => '3252', 'nome' => 'Projeto Povos do Brasil']);
        Colecao::create(['codigo' => '3253', 'nome' => 'Projeto Orquestrando']);
        Colecao::create(['codigo' => '3254', 'nome' => 'Projeto Meio Ambiente']);
        Colecao::create(['codigo' => '3255', 'nome' => 'Projeto Lúmina - Socioemocional']);
    }

}
