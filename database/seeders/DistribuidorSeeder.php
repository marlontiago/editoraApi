<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Distribuidor;
use App\Models\Gestor;
use Illuminate\Support\Facades\Hash;

class DistribuidorSeeder extends Seeder
{
    public function run(): void
    {
        $gestor = Gestor::first(); // assume que já existe pelo UserSeeder

        // ---- 1º Distribuidor ----
        $user = User::firstOrCreate(
            ['email' => 'distribuidor@teste.com'],
            ['name' => 'Distribuidor Exemplo', 'password' => Hash::make('distribuidor123')]
        );
        if (method_exists($user, 'assignRole')) {
            $user->assignRole('distribuidor');
        }

        Distribuidor::firstOrCreate(
            ['user_id' => $user->id],
            [
                'gestor_id'           => $gestor?->id,
                'razao_social'        => 'Distribuidora Exemplo LTDA',
                'cnpj'                => '12.345.678/0001-99',
                'representante_legal' => 'João da Silva',
                'cpf'                 => '123.456.789-00',
                'rg'                  => '12.345.678-9',
                'telefone'            => '41999998888',

                // Endereço fracionado (NOVO)
                'endereco'            => 'Rua Exemplo',
                'numero'              => '123',
                'complemento'         => null,
                'bairro'              => 'Centro',
                'cidade'              => 'Curitiba',
                'uf'                  => 'PR',
                'cep'                 => '80010-000',

                'percentual_vendas'   => 10.00,
                'vencimento_contrato' => now()->addYear(),
                'contrato_assinado'   => true,
            ]
        );

        // ---- 2º Distribuidor ----
        $user2 = User::firstOrCreate(
            ['email' => 'carolinedist@example.com'],
            ['name' => 'Caroline Distribuidora', 'password' => Hash::make('senha123')]
        );
        if (method_exists($user2, 'assignRole')) {
            $user2->assignRole('distribuidor');
        }

        Distribuidor::firstOrCreate(
            ['user_id' => $user2->id],
            [
                'gestor_id'           => $gestor?->id,
                'razao_social'        => 'Caroline Distribuidora ME',
                'cnpj'                => '98.765.432/0001-99',
                'representante_legal' => 'Caroline Souza',
                'cpf'                 => '987.654.321-00',
                'rg'                  => '98.765.432-1',
                'telefone'            => '11988887777',

                // Endereço fracionado
                'endereco'            => 'Avenida Exemplo',
                'numero'              => '456',
                'complemento'         => null,
                'bairro'              => 'Jardins',
                'cidade'              => 'São Paulo',
                'uf'                  => 'SP',
                'cep'                 => '01415-000',

                'percentual_vendas'   => 15.00,
                'vencimento_contrato' => now()->addYear(),
                'contrato_assinado'   => true,
            ]
        );

        // ---- 3º Distribuidor ----
        $user3 = User::firstOrCreate(
            ['email' => 'rodrigodist@example.com'],
            ['name' => 'Rodrigo Distribuidor', 'password' => Hash::make('senha123')]
        );
        if (method_exists($user3, 'assignRole')) {
            $user3->assignRole('distribuidor');
        }

        Distribuidor::firstOrCreate(
            ['user_id' => $user3->id],
            [
                'gestor_id'           => $gestor?->id,
                'razao_social'        => 'Rodrigo Distribuidor EPP',
                'cnpj'                => '11.222.333/0001-44',
                'representante_legal' => 'Rodrigo Lima',
                'cpf'                 => '111.222.333-44',
                'rg'                  => '11.222.333-4',
                'telefone'            => '21977776666',

                // Endereço fracionado
                'endereco'            => 'Travessa Exemplo',
                'numero'              => '789',
                'complemento'         => null,
                'bairro'              => 'Copacabana',
                'cidade'              => 'Rio de Janeiro',
                'uf'                  => 'RJ',
                'cep'                 => '22010-000',

                'percentual_vendas'   => 12.00,
                'vencimento_contrato' => now()->addYear(),
                'contrato_assinado'   => true,
            ]
        );
    }
}
