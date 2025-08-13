<?php

namespace Database\Seeders;

use App\Models\Produto;
use Illuminate\Database\Seeder;

class ProdutoSeeder extends Seeder
{
    public function run(): void
    {
        Produto::create([
            'nome' => 'Algoritmos e Programação de Computadores',
            'titulo' => 'Algoritmos',
            'isbn' => '978-85-00012-000-0',
            'autores' => 'Bill Gates, Steve Jobs',
            'edicao' => '3ª',
            'ano' => 1980,
            'numero_paginas' => 790,
            'peso' => 0.890,
            'ano_escolar' => 'Fund 2',
            'quantidade_estoque' => 500,
            'quantidade_por_caixa' => 8,
            'preco' => 172.90,
            'colecao_id' => 1, 
        ]);

        Produto::create([
            'nome' => 'Anatomia',
            'titulo' => 'Anatomia Humana',
            'isbn' => '978-85-0000-210-0',
            'autores' => 'Paulo Muzy, Cariane',
            'edicao' => '3ª',
            'ano' => 2024,
            'numero_paginas' => 200,
            'peso' => 0.750,
            'ano_escolar' => 'Fund 1',
            'quantidade_estoque' => 500,
            'quantidade_por_caixa' => 13,
            'preco' => 3.00,
            'colecao_id' => 1, 
        ]);

        Produto::create([
            'nome' => 'Mecânica',
            'titulo' => 'Mecânica básica Industrial',
            'isbn' => '978-85-0220-210-5',
            'autores' => 'Ozzy Osbourne, Jimmy Hetfield',
            'edicao' => '3ª',
            'ano' => 1993,
            'numero_paginas' => 700,
            'peso' => 1.230,
            'ano_escolar' => 'Fund 1',
            'quantidade_estoque' => 500,
            'quantidade_por_caixa' => 13,
            'preco' => 89.00,
            'colecao_id' => 1, 
        ]);

        Produto::create([
            'nome' => 'Gastronomique',
            'titulo' => 'Le Cordon Bleu',
            'isbn' => '908-85-1230-198-4',
            'autores' => 'Erick Jacquin, Paola Carosela',
            'edicao' => '3ª',
            'ano' => 2018,
            'numero_paginas' => 400,
            'peso' => 1.130,
            'ano_escolar' => 'Fund 1',
            'quantidade_estoque' => 900,
            'quantidade_por_caixa' => 22,
            'preco' => 17.90,
            'colecao_id' => 1, 
        ]);
    }
}
