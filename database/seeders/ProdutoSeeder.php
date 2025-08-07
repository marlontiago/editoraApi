<?php

namespace Database\Seeders;

use App\Models\Produto;
use Illuminate\Database\Seeder;

class ProdutoSeeder extends Seeder
{
    public function run(): void
    {
        Produto::create([
            'nome' => 'Livro Exemplo',
            'titulo' => 'Matemática 5º ano',
            'isbn' => '978-85-0000-000-0',
            'autores' => 'João Silva, Maria Souza',
            'edicao' => '3ª',
            'ano' => 2023,
            'numero_paginas' => 200,
            'peso' => 0.450,
            'ano_escolar' => 'Fund 2',
            'quantidade_estoque' => 50,
            'quantidade_por_caixa' => 10,
            'preco' => 35.00,
            'colecao_id' => 1, // Assumindo que já exista uma coleção com ID 1
        ]);
    }
}
