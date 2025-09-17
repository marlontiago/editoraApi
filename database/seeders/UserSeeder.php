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
        // --- Admin ---
        $admin = User::create([
            'name'     => 'Admin',
            'email'    => 'admin@example.com',
            'password' => Hash::make('admin123'),
        ]);
        if (method_exists($admin, 'assignRole')) {
            $admin->assignRole('admin');
        }

        // --- Gestor (endereços separados) ---
        $userGestor = User::create([
            'name'     => 'Gestor Teste',
            'email'    => 'gestor@example.com',
            'password' => Hash::make('senha123'),
        ]);
        if (method_exists($userGestor, 'assignRole')) {
            $userGestor->assignRole('gestor');
        }

        $gestor = Gestor::create([
            'user_id'             => $userGestor->id,
            'estado_uf'           => 'RJ',
            'razao_social'        => 'Jorge Gestor',
            'cnpj'                => '12.345.678/0001-00',
            'representante_legal' => 'Jorge Representante',
            'cpf'                 => '123.456.789-00',
            'rg'                  => '12.345.678-9',
            'telefone'            => '41999999999',
            'email'               => 'gestor@example.com',

            // Endereço fracionado
            'endereco'            => 'Rua dos Gerentes',
            'numero'              => '456',
            'complemento'         => null,
            'bairro'              => 'Centro',
            'cidade'              => 'Curitiba',
            'uf'                  => 'PR',
            'cep'                 => '80000-000',

            // Contratuais
            'percentual_vendas'   => 12.50,
            'vencimento_contrato' => now()->addYear(),
            'contrato_assinado'   => true,
        ]);

        // --- Distribuidor (endereços separados) ---
        $userDistribuidor = User::create([
            'name'     => 'Distribuidor Teste',
            'email'    => 'distribuidor@example.com',
            'password' => Hash::make('senha123'),
        ]);
        if (method_exists($userDistribuidor, 'assignRole')) {
            $userDistribuidor->assignRole('distribuidor');
        }

        Distribuidor::create([
            'user_id'             => $userDistribuidor->id,
            'gestor_id'           => $gestor->id,

            'razao_social'        => 'Distribuidora Teste LTDA',
            'cnpj'                => '99.999.999/0001-00',
            'representante_legal' => 'Maria Oliveira',
            'cpf'                 => '999.999.999-99',
            'rg'                  => '12.345.678-9',
            'telefone'            => '41999999998',

            // Endereço fracionado (NOVO)
            'endereco'            => 'Rua das Flores',
            'numero'              => '321',
            'complemento'         => null,
            'bairro'              => 'Batel',
            'cidade'              => 'Curitiba',
            'uf'                  => 'PR',
            'cep'                 => '80010-000',

            'percentual_vendas'   => 8.50,
            // Se quiser simular início + validade, pode preencher só o vencimento aqui:
            'vencimento_contrato' => now()->addMonths(18),
            'contrato_assinado'   => true,
        ]);
    }
}
