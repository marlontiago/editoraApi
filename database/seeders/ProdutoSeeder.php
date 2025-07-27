<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Produto;

class ProdutoSeeder extends Seeder
{
    public function run(): void
    {
        $produtos = [
            [
                'nome' => 'Livro de Aventura',
                'descricao' => 'Uma jornada épica em um mundo mágico.',
                'preco' => 39.90,
                'quantidade_estoque' => 50,
                'imagem' => null,
            ],
            [
                'nome' => 'Livro de Romance',
                'descricao' => 'Uma história de amor com reviravoltas.',
                'preco' => 29.90,
                'quantidade_estoque' => 30,
                'imagem' => null,
            ],
            [
                'nome' => 'Guia do Programador',
                'descricao' => 'Aprenda a programar com exemplos práticos.',
                'preco' => 49.90,
                'quantidade_estoque' => 20,
                'imagem' => null,
            ],
            [
                'nome' => 'Caderno de Anotações',
                'descricao' => 'Caderno com capa dura e folhas pautadas.',
                'preco' => 15.00,
                'quantidade_estoque' => 100,
                'imagem' => null,
            ],
        ];

        foreach ($produtos as $produto) {
            Produto::create($produto);
        }
    }
}
