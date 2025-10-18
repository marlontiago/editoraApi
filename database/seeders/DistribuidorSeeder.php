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
        // Garante dois gestores para vincular (usa os existentes ou cria placeholders mínimos)
        $gestor1 = Gestor::first() ?: $this->criarGestorMinimo(
            email: 'gestor1@example.com',
            nome: 'Gestor 1',
            ufAtuacao: 'PR',
            cnpj: '12.345.678/0001-01'
        );

        $gestor2 = Gestor::skip(1)->first() ?: $this->criarGestorMinimo(
            email: 'gestor2@example.com',
            nome: 'Gestor 2',
            ufAtuacao: 'SP',
            cnpj: '12.345.678/0001-02'
        );

        // ===== Distribuidor 1 (vinculado ao Gestor 1) =====
        $user1 = User::firstOrCreate(
            ['email' => 'distribuidor1@example.com'],
            ['name' => 'Distribuidor 1', 'password' => Hash::make('distribuidor123')]
        );
        if (method_exists($user1, 'assignRole')) {
            $user1->assignRole('distribuidor');
        }

        Distribuidor::updateOrCreate(
            ['user_id' => $user1->id],
            [
                'gestor_id'           => $gestor1->id,

                'razao_social'        => 'Distribuidora Um LTDA',
                'cnpj'                => '99.999.999/0001-01',
                'representante_legal' => 'Carlos Silva',
                'cpf'                 => '999.999.999-01',
                'rg'                  => '10.111.222-3',

                // JSON
                'emails'              => ['distribuidor1@example.com', 'financeiro.dist1@example.com'],
                'telefones'           => ['41999990001', '4133330001'],

                // Endereço principal
                'endereco'            => 'Rua das Flores',
                'numero'              => '321',
                'complemento'         => null,
                'bairro'              => 'Batel',
                'cidade'              => 'Curitiba',
                'uf'                  => 'PR',
                'cep'                 => '80010-000',

                // Endereço secundário (exemplo)
                'endereco2'           => 'Av. República',
                'numero2'             => '1000',
                'complemento2'        => 'Conj. 302',
                'bairro2'             => 'Centro',
                'cidade2'             => 'Ponta Grossa',
                'uf2'                 => 'PR',
                'cep2'                => '84010-000',

                'percentual_vendas'   => 8.50,
                'vencimento_contrato' => now()->addMonths(18),
                'contrato_assinado'   => true,
            ]
        );

        // ===== Distribuidor 2 (vinculado ao Gestor 2) =====
        $user2 = User::firstOrCreate(
            ['email' => 'distribuidor2@example.com'],
            ['name' => 'Distribuidor 2', 'password' => Hash::make('distribuidor123')]
        );
        if (method_exists($user2, 'assignRole')) {
            $user2->assignRole('distribuidor');
        }

        Distribuidor::updateOrCreate(
            ['user_id' => $user2->id],
            [
                'gestor_id'           => $gestor2->id,

                'razao_social'        => 'Distribuidora Dois EIRELI',
                'cnpj'                => '99.999.999/0001-02',
                'representante_legal' => 'Ana Souza',
                'cpf'                 => '999.999.999-02',
                'rg'                  => '20.333.444-5',

                // JSON
                'emails'              => ['distribuidor2@example.com'],
                'telefones'           => ['11999990002'],

                'endereco'            => 'Av. Rio Branco',
                'numero'              => '900',
                'complemento'         => 'Sala 702',
                'bairro'              => 'Centro',
                'cidade'              => 'São Paulo',
                'uf'                  => 'SP',
                'cep'                 => '01006-000',

                // Sem endereço secundário
                'endereco2'           => null,
                'numero2'             => null,
                'complemento2'        => null,
                'bairro2'             => null,
                'cidade2'             => null,
                'uf2'                 => null,
                'cep2'                => null,

                'percentual_vendas'   => 7.75,
                'vencimento_contrato' => now()->addYear()->addMonths(6),
                'contrato_assinado'   => true,
            ]
        );
    }

    private function criarGestorMinimo(string $email, string $nome, string $ufAtuacao, string $cnpj): Gestor
    {
        $user = User::firstOrCreate(
            ['email' => $email],
            ['name' => $nome, 'password' => Hash::make('gestor123')]
        );
        if (method_exists($user, 'assignRole')) {
            $user->assignRole('gestor');
        }

        return Gestor::updateOrCreate(
            ['user_id' => $user->id],
            [
                'estado_uf'            => $ufAtuacao,
                'razao_social'         => "{$nome} LTDA",
                'cnpj'                 => $cnpj,
                'representante_legal'  => 'Responsável Padrão',
                'cpf'                  => '123.456.789-99',
                'rg'                   => '12.345.678-9',
                'telefone'             => '41988887770', // (Gestor ainda mantém campo telefone)
                'email'                => $email,

                'endereco'             => 'Rua Exemplo',
                'numero'               => '100',
                'complemento'          => null,
                'bairro'               => 'Centro',
                'cidade'               => $ufAtuacao === 'SP' ? 'São Paulo' : 'Curitiba',
                'uf'                   => $ufAtuacao,
                'cep'                  => '80000-000',

                'percentual_vendas'    => 10.00,
                'vencimento_contrato'  => now()->addYear(),
                'contrato_assinado'    => true,
            ]
        );
    }
}
