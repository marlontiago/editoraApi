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

                 
    }
}
