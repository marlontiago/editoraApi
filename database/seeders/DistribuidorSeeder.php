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
    }
}
