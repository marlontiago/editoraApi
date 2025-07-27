<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Comissao;

class ComissaoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Comissao::firstOrCreate(['tipo' => 'distribuidor'], ['percentual' => 10]);
        Comissao::firstOrCreate(['tipo' => 'gestor'], ['percentual' => 5]); 
    }
}
