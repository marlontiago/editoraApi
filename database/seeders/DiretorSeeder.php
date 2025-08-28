<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\DiretorComercial;
use App\Models\User;

class DiretorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::firstOrCreate([
            'name' => 'Diretor Comercial João',
            'email' => 'dirjoao@example.com',
            'password' => Hash::make('senha123'),
        ]);
        $user->assignRole('diretor_comercial');

        DiretorComercial::firstOrCreate([
            'user_id' => $user->id,
        ], [
            'nome' => 'Diretor Comercial João',
            'email' => 'dirjoao@example.com',
            'telefone' => '41966665555',
            'logradouro' => 'Avenida dos Comerciários',
            'numero' => '400',
            'complemento' => 'Sala 404',
            'bairro' => 'Centro Empresarial',
            'cidade' => 'Curitiba',
            'cep' => '80000-000',
            'estado' => 'PR',
            'percentual_vendas' => 10.00,
        ]);

        $user2 = User::firstOrCreate([
            'name' => 'Diretora Comercial Maria',
            'email' => 'dirmaria@example.com',
            'password' => Hash::make('senha123'),
        ]);
        $user2->assignRole('diretor_comercial');

        DiretorComercial::firstOrCreate([
            'user_id' => $user2->id,
        ], [
            'nome' => 'Diretora Comercial Maria',
            'email' => 'dirmaria@example.com',
            'telefone' => '41955554444',
            'logradouro' => 'Rua das Empresas',
            'numero' => '500',
            'complemento' => 'Apto 505',
            'bairro' => 'Bairro Comercial',
            'cidade' => 'São Paulo',
            'cep' => '01000-000',
            'estado' => 'SP',
            'percentual_vendas' => 12.50,
        ]);
    }
}
