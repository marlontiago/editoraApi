<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Advogado;
use App\Models\User;

class AdvogadoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::firstOrCreate([
            'name' => 'Advogado Cléber',
            'email' => 'advogadocleber@example.com',
            'password' => Hash::make('senha123'),
        ]);
        $user->assignRole('advogado');

        Advogado::firstOrCreate([
            'user_id' => $user->id,
        ], [
            'nome' => 'Advogado Cléber',
            'email' => 'advogadocleber@example.com',
            'telefone' => '41988887777',
            'oab' => 'OAB123456',
            'logradouro' => 'Rua das Flores',
            'numero' => '200',
            'complemento' => 'Sala 202',
            'bairro' => 'Centro',
            'cidade' => 'Curitiba',
            'cep' => '80000-000',
            'estado' => 'PR',
        ]);

        $user2 = User::firstOrCreate([
            'name' => 'Advogada Fernanda',
            'email' => 'advogadafernanda@example.com',
            'password' => Hash::make('senha123'),
        ]);
        $user2->assignRole('advogado');

        Advogado::firstOrCreate([
            'user_id' => $user2->id,
        ], [
            'nome' => 'Advogada Fernanda',
            'email' => 'advogadafernanda@example.com',
            'telefone' => '41977776666',
            'oab' => 'OAB654321',
            'logradouro' => 'Avenida Central',
            'numero' => '300',
            'complemento' => 'Apto 303',
            'bairro' => 'Bela Vista',
            'cidade' => 'São Paulo',
            'cep' => '01000-000',
            'estado' => 'SP',
        ]);

        $user3 = User::firstOrCreate([
            'name' => 'Advogado Ricardo',
            'email' => 'advogadoricardo@example.com',
            'password' => Hash::make('senha123'),
        ]);
        $user3->assignRole('advogado');

        Advogado::firstOrCreate([
            'user_id' => $user3->id,
        ], [
            'nome' => 'Advogado Ricardo',
            'email' => 'advogadoricardo@example.com',
            'telefone' => '41966665555',
            'oab' => 'OAB789012',
            'logradouro' => 'Praça da Liberdade',
            'numero' => '400',
            'complemento' => 'Conjunto 404',
            'bairro' => 'Liberdade',
            'cidade' => 'Belo Horizonte',
            'cep' => '30000-000',
            'estado' => 'MG',
        ]);            
    }
}
