<?php

namespace Database\Seeders;

use App\Models\Gestor;
use App\Models\GestorUf;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class GestorSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // ========== GESTOR #1 ==========
            $user1 = User::create([
                'name'     => 'Gestor Um',
                'email'    => 'gestor1@example.com',
                'password' => Hash::make('password'),
            ]);
            if (method_exists($user1, 'assignRole')) {
                $user1->assignRole('gestor');
            }

            /** @var \App\Models\Gestor $gestor1 */
            $gestor1 = Gestor::create([
                'user_id'             => $user1->id,

                // ENDEREÇO PRINCIPAL do gestor (isso NÃO é a UF de atuação!)
                'endereco'            => 'Rua dos Gerentes',
                'numero'              => '100',
                'complemento'         => '?',
                'bairro'              => 'Centro',
                'cidade'              => 'Curitiba',
                'uf'                  => 'PR',
                'cep'                 => '80000-001',

                // DADOS CADASTRAIS
                'razao_social'        => 'Gestor Um LTDA',
                'cnpj'                => '12.345.678/0001-01',
                'representante_legal' => 'João Representante',
                'cpf'                 => '123.456.789-01',
                'rg'                  => '12.345.678-0',

                // Contato "legado" (mantido para compat)
                'telefone'            => '41988887771',
                'email'               => 'gestor1@example.com',

                // Contratuais
                'percentual_vendas'   => 12.5,
                'vencimento_contrato' => now()->addYear(),
                'contrato_assinado'   => true,
            ]);

            // UFs de ATUAÇÃO do gestor #1 (pivô gestor_ufs, exclusivas no sistema)
            $ufs1 = ['PR', 'SC']; // ajuste como quiser
            $this->attachUfsExclusivas($gestor1, $ufs1);

            // ========== GESTOR #2 ==========
            $user2 = User::create([
                'name'     => 'Gestor Dois',
                'email'    => 'gestor2@example.com',
                'password' => Hash::make('password'),
            ]);
            if (method_exists($user2, 'assignRole')) {
                $user2->assignRole('gestor');
            }

            /** @var \App\Models\Gestor $gestor2 */
            $gestor2 = Gestor::create([
                'user_id'             => $user2->id,

                // ENDEREÇO PRINCIPAL do gestor
                'endereco'            => 'Av. Comercial',
                'numero'              => '200',
                'complemento'         => 'Conj. 12',
                'bairro'              => 'Bela Vista',
                'cidade'              => 'São Paulo',
                'uf'                  => 'SP',
                'cep'                 => '01000-000',

                // DADOS CADASTRAIS
                'razao_social'        => 'Gestor Dois ME',
                'cnpj'                => '98.765.432/0001-99',
                'representante_legal' => 'Maria Gestora',
                'cpf'                 => '987.654.321-00',
                'rg'                  => '99.888.777-6',

                'telefone'            => '11988887777',
                'email'               => 'gestor2@example.com',

                'percentual_vendas'   => 10.0,
                'vencimento_contrato' => now()->addMonths(18),
                'contrato_assinado'   => true,
            ]);

            // UFs de ATUAÇÃO do gestor #2 (não repita com o #1 para não colidir)
            $ufs2 = ['SP', 'RJ'];
            $this->attachUfsExclusivas($gestor2, $ufs2);
        });
    }

    /**
     * Anexa UFs de atuação respeitando a exclusividade global (unique em gestor_ufs.uf).
     * Se alguma UF já estiver ocupada, ela é ignorada silenciosamente.
     * Troque o comportamento para lançar exceção se preferir.
     */
    private function attachUfsExclusivas(Gestor $gestor, array $ufs): void
    {
        $ufs = collect($ufs)
            ->map(fn ($u) => strtoupper(trim((string) $u)))
            ->filter(fn ($u) => $u !== '')
            ->unique()
            ->values()
            ->all();

        if (empty($ufs)) {
            return;
        }

        // UFs já ocupadas por QUALQUER gestor
        $ocupadas = GestorUf::whereIn('uf', $ufs)->pluck('uf')->all();

        // UFs livres nesta execução
        $livres = array_values(array_diff($ufs, $ocupadas));

        if (!empty($livres)) {
            $gestor->ufs()->createMany(
                array_map(fn ($u) => ['uf' => $u], $livres)
            );
        }

        // Se quiser FALHAR quando houver colisão, troque pelo throw abaixo:
        // if (!empty($ocupadas)) {
        //     throw new \RuntimeException('UF(s) já ocupadas: '.implode(', ', $ocupadas));
        // }
    }
}
