<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Gestor;
use App\Models\Distribuidor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('admin123'),
        ]);
        $admin->assignRole('admin');

        // ========= GESTOR (endereços separados - NOVO PADRÃO) =========
        $userGestor = User::create([
            'name' => 'Gestor Teste',
            'email' => 'gestor@example.com',
            'password' => Hash::make('senha123'),
        ]);
        $userGestor->assignRole('gestor');

        $gestor = Gestor::create([
            'estado_uf'           => 'RJ',
            'user_id'             => $userGestor->id,
            'razao_social'        => 'Jorge Gestor',
            'cnpj'                => '12.345.678/0001-00',
            'representante_legal' => 'Jorge Representante',
            'cpf'                 => '123.456.789-00',
            'rg'                  => '12.345.678-9',
            'telefone'            => '41999999999',
            'email'               => 'gestor@example.com',

            // Endereço (SEPARADO)
            'endereco'            => 'Rua dos Gerentes',
            'numero'              => '456',
            'complemento'         => null,
            'bairro'              => 'Centro',
            'cidade'              => 'Curitiba',
            'uf'                  => 'PR',
            'cep'                 => '80000-000',

            // Contratuais
            'percentual_vendas'   => 12.5,
            'vencimento_contrato' => now()->addYear(),
            'contrato_assinado'   => true,
        ]);

        // ========= DISTRIBUIDOR (padrão ANTIGO com endereco_completo) =========
        $userDistribuidor = User::create([
            'name' => 'Distribuidor Teste',
            'email' => 'distribuidor@example.com',
            'password' => Hash::make('senha123'),
        ]);
        $userDistribuidor->assignRole('distribuidor');

        Distribuidor::create([
            'user_id'             => $userDistribuidor->id,
            'gestor_id'           => $gestor->id,
            'razao_social'        => 'Distribuidora Teste LTDA',
            'cnpj'                => '99.999.999/0001-00',
            'representante_legal' => 'Maria Oliveira',
            'cpf'                 => '999.999.999-99',
            'rg'                  => '12.345.678-9',
            'telefone'            => '41999999998',

            // Endereço (ANTIGO)
            'endereco_completo'   => 'Rua das Flores, 321 - Batel, Curitiba - PR, 80010-000',

            // Contratuais (mantém conforme sua migration de distribuidores)
            'percentual_vendas'   => 8.5,
            'vencimento_contrato' => now()->addMonths(18),
            'contrato_assinado'   => true,

            // Se sua migration de distribuidores ainda tiver a coluna "contrato", deixe como null:
            'contrato'            => null,
        ]);
    }
}
