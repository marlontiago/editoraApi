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
        // ===== Gestor 1 =====
        $user1 = User::firstOrCreate(
            ['email' => 'gestor1@example.com'],
            ['name' => 'Gestor 1', 'password' => Hash::make('gestor123')]
        );
        if (method_exists($user1, 'assignRole')) {
            $user1->assignRole('gestor');
        }

        Gestor::updateOrCreate(
            ['user_id' => $user1->id],
            [
                'estado_uf'            => 'PR',
                'razao_social'         => 'Gestor Um LTDA',
                'cnpj'                 => '12.345.678/0001-01',
                'representante_legal'  => 'João Representante',
                'cpf'                  => '123.456.789-01',
                'rg'                   => '12.345.678-0',
                'telefone'             => '41988887771',
                'email'                => 'gestor1@example.com',

                // Endereço
                'endereco'             => 'Rua dos Gerentes',
                'numero'               => '100',
                'complemento'          => null,
                'bairro'               => 'Centro',
                'cidade'               => 'Curitiba',
                'uf'                   => 'PR',
                'cep'                  => '80000-001',

                // Contratuais
                'percentual_vendas'    => 12.50,
                'vencimento_contrato'  => now()->addYear(),
                'contrato_assinado'    => true,
            ]
        );

        // ===== Gestor 2 =====
        $user2 = User::firstOrCreate(
            ['email' => 'gestor2@example.com'],
            ['name' => 'Gestor 2', 'password' => Hash::make('gestor123')]
        );
        if (method_exists($user2, 'assignRole')) {
            $user2->assignRole('gestor');
        }

        Gestor::updateOrCreate(
            ['user_id' => $user2->id],
            [
                'estado_uf'            => 'SP',
                'razao_social'         => 'Gestor Dois EIRELI',
                'cnpj'                 => '12.345.678/0001-02',
                'representante_legal'  => 'Maria Gerente',
                'cpf'                  => '123.456.789-02',
                'rg'                   => '12.345.678-1',
                'telefone'             => '11988887772',
                'email'                => 'gestor2@example.com',

                // Endereço
                'endereco'             => 'Av. Paulista',
                'numero'               => '1500',
                'complemento'          => 'Conj. 1203',
                'bairro'               => 'Bela Vista',
                'cidade'               => 'São Paulo',
                'uf'                   => 'SP',
                'cep'                  => '01310-000',

                // Contratuais
                'percentual_vendas'    => 10.00,
                'vencimento_contrato'  => now()->addMonths(18),
                'contrato_assinado'    => true,
            ]
        );
    }
}
