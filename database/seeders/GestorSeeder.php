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
            'razao_social' => 'Gestor Representações LTDA',
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
    }
}
