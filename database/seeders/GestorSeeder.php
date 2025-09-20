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
        // garante o usuário do gestor
        $user = User::firstOrCreate(
            ['email' => 'gestor@example.com'],
            ['name' => 'Gestor Exemplo', 'password' => Hash::make('gestor123')]
        );
        if (method_exists($user, 'assignRole')) {
            $user->assignRole('gestor');
        }

        // seed do Gestor (endereço fracionado + campos contratuais atuais)
        Gestor::updateOrCreate(
            ['user_id' => $user->id],
            [
                'estado_uf'            => 'PR',
                'razao_social'         => 'Gestor Exemplo LTDA',
                'cnpj'                 => '12.345.678/0001-00',
                'representante_legal'  => 'João Representante',
                'cpf'                  => '123.456.789-00',
                'rg'                   => '12.345.678-9',
                'telefone'             => '41988887777',
                'email'                => 'gestor@example.com',

                // Endereço fracionado
                'endereco'             => 'Rua dos Gerentes',
                'numero'               => '456',
                'complemento'          => null,
                'bairro'               => 'Centro',
                'cidade'               => 'Curitiba',
                'uf'                   => 'PR',
                'cep'                  => '80000-000',

                // Contratuais (sem início/validade; vencimento direto)
                'percentual_vendas'    => 12.50,
                'vencimento_contrato'  => now()->addYear(),
                'contrato_assinado'    => true,
            ]
        );
    }
}
