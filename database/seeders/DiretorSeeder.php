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

        
    }
}
