<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Cliente;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ClienteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            

            $user = User::firstOrCreate([
                'email' => 'marlon@example.com',
                'name' => 'Marlon Silva',
                'password' => Hash::make('senha123'),
            ]);

            $user->assignRole('cliente');

            Cliente::firstOrCreate([
                'user_id' => $user->id,
            ], [
                'razao_social' => 'Marlon Silva ME',
                'email' => 'marlon@example.com',
                'cnpj' => '12.345.678/0001-90',
                'cpf' => '123.456.789-00',
                'inscr_estadual' => '123456789',
                'telefone' => '41999998888',
                'endereco' => 'Rua Exemplo',
                'numero' => '100',
                'complemento' => 'Apto 101',
                'bairro' => 'Centro',
                'cidade' => 'Curitiba',
                'uf' => 'PR',
                'cep' => '80000-000',
            ]);

            $user2 = User::firstOrCreate([
                'email' => 'ana@example.com',
                'name' => 'Ana Souza',
                'password' => Hash::make('senha123'),
            ]);
            $user2->assignRole('cliente');

            Cliente::updateOrCreate([
                'user_id' => $user2->id,
            ], [
                'razao_social' => 'Ana Souza LTDA',
                'email' => 'ana@example.com',
                'cnpj' => '98.765.432/0001-10',
                'cpf' => '987.654.321-00',
                'inscr_estadual' => '987654321',
                'telefone' => '41988887777',
                'endereco' => 'Avenida Exemplo',
                'numero' => '200',
                'complemento' => 'Sala 202',
                'bairro' => 'Batel',
                'cidade' => 'Curitiba',
                'uf' => 'PR',
                'cep' => '80000-001',
            ]
            );

            $user3 = User::firstOrCreate([
                'email' => 'jorge@example.com',
                'name' => 'Jorge Lima',
                'password' => Hash::make('senha123'),
            ]);
            $user3->assignRole('cliente');

            Cliente::updateOrCreate([
                'user_id' => $user3->id,
            ], [
                'razao_social' => 'Jorge Lima EPP',
                'email' => 'jorge@example.com',
                'cnpj' => '11.222.333/0001-44',
                'cpf' => '111.222.333-44',
                'inscr_estadual' => '1122334455',
                'telefone' => '41977776666',
                'endereco' => 'Travessa Exemplo',
                'numero' => '300',
                'complemento' => 'Casa',
                'bairro' => 'Água Verde',
                'cidade' => 'Curitiba',
                'uf' => 'PR',
                'cep' => '80000-002',
            ]);



        });
    }
}
