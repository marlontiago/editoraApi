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

        // Gestor
        $userGestor = User::create([
            'name' => 'Gestor Teste',
            'email' => 'gestor@example.com',
            'password' => Hash::make('senha123'),
        ]);
        $userGestor->assignRole('gestor');

        $gestor = Gestor::create([
            'estado_uf' => 'RJ',
            'user_id' => $userGestor->id,
            'razao_social' => 'Jorge Gestor',
            'cnpj' => '12.345.678/0001-00',
            'representante_legal' => 'Jorge Representante',
            'cpf' => '123.456.789-00',
            'rg' => '12.345.678-9',
            'telefone' => '41999999999',
            'email' => 'gestor@example.com',
            'endereco_completo' => 'Rua dos Gerentes, 456, Curitiba - PR',
            'percentual_vendas' => 12.5,
            'vencimento_contrato' => now()->addYear(),
            'contrato_assinado' => true,
            'contrato' => null,
        ]);

        // Distribuidor
        $userDistribuidor = User::create([
            'name' => 'Distribuidor Teste',
            'email' => 'distribuidor@example.com',
            'password' => Hash::make('senha123'),
        ]);
        $userDistribuidor->assignRole('distribuidor');

        Distribuidor::create([
            'user_id' => $userDistribuidor->id,
            'gestor_id' => $gestor->id,
            'razao_social' => 'Distribuidora Teste LTDA',
            'cnpj' => '99.999.999/0001-00',
            'representante_legal' => 'Maria Oliveira',
            'cpf' => '999.999.999-99',
            'rg' => '12.345.678-9',
            'telefone' => '41999999998',
            'endereco_completo' => 'Rua das Flores, 321',
            'percentual_vendas' => 8.5,
            'vencimento_contrato' => now()->addMonths(18),
            'contrato_assinado' => true,
            'contrato' => null,
        ]);
    }
}
