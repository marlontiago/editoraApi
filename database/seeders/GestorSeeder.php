<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Gestor;
use Illuminate\Support\Facades\Hash;

class GestorSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrCreate([
            'email' => 'gestor@teste.com',
        ], [
            'name' => 'Gestor Exemplo',
            'password' => Hash::make('gestor123'),
        ]);

        $user->assignRole('gestor');

        Gestor::firstOrCreate([
            'user_id' => $user->id,
        ], [
            'estado_uf' => 'PR',
            'razao_social' => 'Daniel Gestor',
            'cnpj' => '12.345.678/0001-00',
            'representante_legal' => 'João Representante',
            'cpf' => '123.456.789-00',
            'rg' => '12.345.678-9',
            'telefone' => '41988887777',
            'email' => 'gestor@teste.com',
            'endereco_completo' => 'Rua dos Gerentes, 456, Curitiba - PR',
            'percentual_vendas' => 12.5,
            'vencimento_contrato' => now()->addYear(),
            'contrato_assinado' => true,
            'contrato' => null, // ou você pode usar um arquivo fake no futuro
        ]);

        $user2 = User::firstOrCreate([
            'email' => 'tuliogestor@example.com',
        ], [
            'name' => 'Tulio Gestor',
            'password' => Hash::make('senha123'),
        ]);
        $user2->assignRole('gestor');

        Gestor::firstOrCreate([
            'user_id' => $user2->id,
        ], [
            'estado_uf' => 'SP',
            'razao_social' => 'Tulio Gestor LTDA',
            'cnpj' => '98.765.432/0001-00',
            'representante_legal' => 'Tulio Silva',
            'cpf' => '987.654.321-00',
            'rg' => '98.765.432-1',
            'telefone' => '11999998888',
            'email' => 'tuliogestor@example.com',
            'endereco_completo' => 'Avenida dos Comerciantes, 789, São Paulo - SP',
            'percentual_vendas' => 10.0,
            'vencimento_contrato' => now()->addYear(),
            'contrato_assinado' => true,
            'contrato' => null, // ou você pode usar um arquivo fake no futuro
        ]);

        $user3 = User::firstOrCreate([
            'email' => 'bentogestor@example.com',
        ], [
            'name' => 'Bento Gestor',
            'password' => Hash::make('senha123'),
        ]);
        $user3->assignRole('gestor');

        Gestor::firstOrCreate([
            'user_id' => $user3->id,
        ], [
            'estado_uf' => 'MG',
            'razao_social' => 'Bento Gestor ME',
            'cnpj' => '11.223.344/0001-55',
            'representante_legal' => 'Bento Souza',
            'cpf' => '111.222.333-44',
            'rg' => '11.223.344-5',
            'telefone' => '31988887777',
            'email' => 'bentogestor@example.com',
            'endereco_completo' => 'Rua dos Administradores, 123, Belo Horizonte - MG',
            'percentual_vendas' => 15.0,
            'vencimento_contrato' => now()->addYear(),
            'contrato_assinado' => true,
            'contrato' => null, // ou você pode usar um arquivo fake no futuro
        ]);
    }
}
