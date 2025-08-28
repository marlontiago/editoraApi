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
        $gestor = Gestor::first();

        $user = User::firstOrCreate([
            'email' => 'distribuidor@teste.com',
        ], [
            'name' => 'Distribuidor Exemplo',
            'password' => Hash::make('distribuidor123'),
        ]);

        $user->assignRole('distribuidor');

        Distribuidor::firstOrCreate([
            'user_id' => $user->id,
        ], [
            'gestor_id' => $gestor->id,
            'razao_social' => 'Distribuidora Exemplo LTDA',
            'cnpj' => '12.345.678/0001-99',
            'representante_legal' => 'João da Silva',
            'cpf' => '123.456.789-00',
            'rg' => '12.345.678-9',
            'telefone' => '41999998888',
            'endereco_completo' => 'Rua Exemplo, 123, Curitiba - PR',
            'percentual_vendas' => 10,
            'vencimento_contrato' => now()->addYear(),
            'contrato_assinado' => true,
        ]);

        $user2 = User::firstOrCreate([
            'email' => 'carolinedist@example.com',
        ], [
            'name' => 'Caroline Distribuidora',
            'password' => Hash::make('senha123'),
        ]);
        $user2->assignRole('distribuidor');

        Distribuidor::firstOrCreate([
            'user_id' => $user2->id,
        ], [
            'gestor_id' => $gestor->id,
            'razao_social' => 'Caroline Distribuidora ME',
            'cnpj' => '98.765.432/0001-99',
            'representante_legal' => 'Caroline Souza',
            'cpf' => '987.654.321-00',
            'rg' => '98.765.432-1',
            'telefone' => '11988887777',
            'endereco_completo' => 'Avenida Exemplo, 456, São Paulo - SP',
            'percentual_vendas' => 15,
            'vencimento_contrato' => now()->addYear(),
            'contrato_assinado' => true,
        ]);

        $user3 = User::firstOrCreate([
            'email' => 'rodrigodist@example.com',
        ], [
            'name' => 'Rodrigo Distribuidor',
            'password' => Hash::make('senha123'),
        ]);
        $user3->assignRole('distribuidor');

        Distribuidor::firstOrCreate([
            'user_id' => $user3->id,
        ], [
            'gestor_id' => $gestor->id,
            'razao_social' => 'Rodrigo Distribuidor EPP',
            'cnpj' => '11.222.333/0001-44',
            'representante_legal' => 'Rodrigo Lima',
            'cpf' => '111.222.333-44',
            'rg' => '11.222.333-4',
            'telefone' => '21977776666',
            'endereco_completo' => 'Travessa Exemplo, 789, Rio de Janeiro - RJ',
            'percentual_vendas' => 12,
            'vencimento_contrato' => now()->addYear(),
            'contrato_assinado' => true,
        ]);
    }
}
