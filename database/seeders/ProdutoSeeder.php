<?php

namespace Database\Seeders;

use App\Models\Produto;
use Illuminate\Database\Seeder;

class ProdutoSeeder extends Seeder
{
    public function run(): void
    {
        Produto::create([
            'nome' => 'Capa Dura Premium',
            'descricao' => 'Alta qualidade para livros.',
            'preco' => 50.00,
            'quantidade_estoque' => 100,
        ]);
    }
}
