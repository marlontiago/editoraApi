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
        // garante um gestor para vincular
        $gestor = Gestor::first();
        if (!$gestor) {
            // cria um gestor mínimo caso não exista
            $gestorUser = User::firstOrCreate(
                ['email' => 'gestor@example.com'],
                ['name' => 'Gestor Exemplo', 'password' => Hash::make('gestor123')]
            );
            if (method_exists($gestorUser, 'assignRole')) {
                $gestorUser->assignRole('gestor');
            }
            $gestor = Gestor::updateOrCreate(
                ['user_id' => $gestorUser->id],
                [
                    'estado_uf'           => 'PR',
                    'razao_social'        => 'Gestor Exemplo LTDA',
                    'cnpj'                => '12.345.678/0001-00',
                    'representante_legal' => 'João Representante',
                    'cpf'                 => '123.456.789-00',
                    'rg'                  => '12.345.678-9',
                    'telefone'            => '41988887777',
                    'email'               => 'gestor@example.com',
                    'endereco'            => 'Rua dos Gerentes',
                    'numero'              => '456',
                    'complemento'         => null,
                    'bairro'              => 'Centro',
                    'cidade'              => 'Curitiba',
                    'uf'                  => 'PR',
                    'cep'                 => '80000-000',
                    'percentual_vendas'   => 12.50,
                    'vencimento_contrato' => now()->addYear(),
                    'contrato_assinado'   => true,
                ]
            );
        }

        // garante o usuário do distribuidor
        $user = User::firstOrCreate(
            ['email' => 'distribuidor@example.com'],
            ['name' => 'Distribuidor Exemplo', 'password' => Hash::make('distribuidor123')]
        );
        if (method_exists($user, 'assignRole')) {
            $user->assignRole('distribuidor');
        }

        // seed do Distribuidor (endereço fracionado + campos contratuais atuais)
        Distribuidor::updateOrCreate(
            ['user_id' => $user->id],
            [
                'gestor_id'           => $gestor?->id,

                'razao_social'        => 'Distribuidora Exemplo LTDA',
                'cnpj'                => '99.999.999/0001-00',
                'representante_legal' => 'Maria Oliveira',
                'cpf'                 => '999.999.999-99',
                'rg'                  => '12.345.678-9',
                'telefone'            => '41999999998',

                'endereco'            => 'Rua das Flores',
                'numero'              => '321',
                'complemento'         => null,
                'bairro'              => 'Batel',
                'cidade'              => 'Curitiba',
                'uf'                  => 'PR',
                'cep'                 => '80010-000',

                'percentual_vendas'   => 8.50,
                'vencimento_contrato' => now()->addMonths(18),
                'contrato_assinado'   => true,
            ]
        );
    }
}
