<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Venda;
use App\Models\Produto;
use App\Models\Distribuidor;

class VendaSeeder extends Seeder
{
    /**
     * Quantas vendas por distribuidor vamos criar
     */
    private int $vendasPorDistribuidor = 5;

    public function run(): void
    {
        $distribuidores = Distribuidor::with('gestor')->get();
        $produtos = Produto::all();

        if ($distribuidores->isEmpty() || $produtos->isEmpty()) {
            $this->command->warn('Nenhum distribuidor ou produto encontrado. Pulei o VendaSeeder.');
            return;
        }

        DB::transaction(function () use ($distribuidores, $produtos) {
            foreach ($distribuidores as $distribuidor) {
                for ($i = 0; $i < $this->vendasPorDistribuidor; $i++) {

                    // Data aleatória nos últimos 60 dias
                    $data = Carbon::now()->subDays(rand(0, 60))->startOfDay();

                    // Primeiro criamos a venda com valor_total = 0 (vamos atualizar depois)
                    $venda = Venda::create([
                        'distribuidor_id' => $distribuidor->id,
                        'gestor_id'       => $distribuidor->gestor_id,
                        'data'            => $data,
                        'valor_total'     => 0,
                    ]);

                    // Seleciona de 1 a 3 produtos aleatórios
                    $itens = $produtos->random(rand(1, min(3, $produtos->count())));

                    $valorTotal = 0;

                    foreach ($itens as $produto) {
                        $quantidade     = rand(1, 5);
                        $precoUnitario  = $produto->preco; // ajuste o nome do campo se necessário
                        $subtotal       = $quantidade * $precoUnitario;
                        $valorTotal    += $subtotal;

                        // Grava no pivot
                        $venda->produtos()->attach($produto->id, [
                            'quantidade'      => $quantidade,
                            'preco_unitario'  => $precoUnitario,
                            'created_at'      => now(),
                            'updated_at'      => now(),
                        ]);

                        // Baixa de estoque se a coluna existir
                        if (array_key_exists('estoque', $produto->getAttributes())) {
                            $produto->decrement('estoque', $quantidade);
                        }
                    }

                    // Atualiza o valor total da venda
                    $venda->update(['valor_total' => $valorTotal]);
                }
            }
        });

        $this->command->info('Vendas geradas com sucesso!');
    }
}
