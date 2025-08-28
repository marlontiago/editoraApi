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
            'imagem' => 'images/algoritmo.jpg',
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
            'colecao_id' => 2, 
            'imagem' => 'images/anatomia.png',
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
            'colecao_id' => 3, 
            'imagem' => 'images/mecanica.jpeg',
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
            'colecao_id' => 4, 
            'imagem' => 'images/gastronomia.jpg',
        ]);

        Produto::create([
            'nome' => 'História do Brasil',
            'titulo' => 'Brasil: Uma História',
            'isbn' => '978-85-0000-999-9',
            'autores' => 'Boris Casoy, Datena',
            'edicao' => '3ª',
            'ano' => 2020,
            'numero_paginas' => 300,
            'peso' => 0.630,
            'ano_escolar' => 'Fund 1',
            'quantidade_estoque' => 700,
            'quantidade_por_caixa' => 10,
            'preco' => 23.90,
            'colecao_id' => 5, 
            'imagem' => 'images/historia.jpg',
        ]);

        Produto::create([
            'nome' => 'Geografia',
            'titulo' => 'Geografia Geral e do Brasil',
            'isbn' => '978-85-0000-321-0',
            'autores' => 'André Trigueiro, Carlos Nascimento',
            'edicao' => '3ª',
            'ano' => 2021,
            'numero_paginas' => 250,
            'peso' => 0.530,
            'ano_escolar' => 'Fund 1',
            'quantidade_estoque' => 600,
            'quantidade_por_caixa' => 15,
            'preco' => 19.90,
            'colecao_id' => 6,
            'imagem' => 'images/geografia.jpeg', 
        ]);

        Produto::create([
            'nome' => 'Física',
            'titulo' => 'Física para Cientistas e Engenheiros',
            'isbn' => '978-85-0000-654-3',
            'autores' => 'Neil deGrasse Tyson, Carl Sagan',
            'edicao' => '3ª',
            'ano' => 2019,
            'numero_paginas' => 450,
            'peso' => 1.000,
            'ano_escolar' => 'Fund 1',
            'quantidade_estoque' => 800,
            'quantidade_por_caixa' => 12,
            'preco' => 45.90,
            'colecao_id' => 7, 
            'imagem' => 'images/fisica.jpeg',
        ]);

        Produto::create([
            'nome' => 'Química',
            'titulo' => 'Química: A Ciência Central',
            'isbn' => '978-85-0000-987-6',
            'autores' => 'Marie Curie, Rosalind Franklin',
            'edicao' => '3ª',
            'ano' => 2022,
            'numero_paginas' => 350,
            'peso' => 0.870,
            'ano_escolar' => 'Fund 1',
            'quantidade_estoque' => 750,
            'quantidade_por_caixa' => 14,
            'preco' => 39.90,
            'colecao_id' => 1,
            'imagem' => 'images/quimica.png',
        ]);

        Produto::create([
            'nome' => 'Biologia',
            'titulo' => 'Biologia Molecular da Célula',
            'isbn' => '978-85-0000-111-2',
            'autores' => 'Charles Darwin, Gregor Mendel',
            'edicao' => '3ª',
            'ano' => 2023,
            'numero_paginas' => 500,
            'peso' => 1.150,
            'ano_escolar' => 'Fund 1',
            'quantidade_estoque' => 650,
            'quantidade_por_caixa' => 11,
            'preco' => 55.90,
            'colecao_id' => 9,
            'imagem' => 'images/biologia.jpeg',
        ]);
    }
}
