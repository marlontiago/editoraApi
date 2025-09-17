<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Pedido;
use App\Models\Produto;
use App\Models\Cliente;
use App\Models\Gestor;
use App\Models\Distribuidor;
use App\Models\City;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PedidoSeeder extends Seeder
{
    public function run(): void
    {
        // Pega dados auxiliares
        $clientes       = Cliente::all();
        $gestores       = Gestor::all();
        $distribuidores = Distribuidor::all();
        $cidades        = City::all();
        $produtos       = Produto::all();

        if ($clientes->isEmpty() || $produtos->isEmpty()) {
            $this->command->warn('Precisa ter clientes e produtos cadastrados antes de rodar PedidoSeeder.');
            return;
        }

        for ($i = 1; $i <= 5; $i++) {
            DB::transaction(function () use ($clientes, $gestores, $distribuidores, $cidades, $produtos, $i) {
                $cliente      = $clientes->random();
                $gestor       = $gestores->random();
                $distribuidor = $distribuidores->random();
                $cidade       = $cidades->random();

                $pedido = Pedido::create([
                    'cliente_id'      => $cliente->id,
                    'gestor_id'       => $gestor->id ?? null,
                    'distribuidor_id' => $distribuidor->id ?? null,
                    'data'            => Carbon::now()->subDays(rand(0,10)),
                    'status'          => 'em_andamento',
                    'observacoes'     => "Pedido de teste {$i}",
                ]);

                // associa cidade
                $pedido->cidades()->sync([$cidade->id]);

                // seleciona 2–4 produtos
                $itens = $produtos->random(rand(2,4));

                $pesoTotal = 0;
                $totalCaixas = 0;
                $valorBruto = 0;
                $valorTotal = 0;

                foreach ($itens as $produto) {
                    $qtd = rand(1, 200);
                    $desconto = rand(0, 20); // % de desconto

                    $precoUnit = $produto->preco;
                    $subBruto  = $precoUnit * $qtd;
                    $precoDesc = $precoUnit * (1 - ($desconto/100));
                    $subDesc   = $precoDesc * $qtd;

                    $pesoItem  = ($produto->peso ?? 0) * $qtd;
                    $caixas    = ceil($qtd / max(1, (int)$produto->quantidade_por_caixa));

                    $pedido->produtos()->attach($produto->id, [
                        'quantidade'           => $qtd,
                        'preco_unitario'       => $precoUnit,
                        'desconto_item'        => $desconto,
                        'desconto_aplicado'    => $desconto,
                        'subtotal'             => $subDesc,
                        'peso_total_produto'   => $pesoItem,
                        'caixas'               => $caixas,
                    ]);

                    $pesoTotal   += $pesoItem;
                    $totalCaixas += $caixas;
                    $valorBruto  += $subBruto;
                    $valorTotal  += $subDesc;
                }

                // atualiza totais
                $pedido->update([
                    'peso_total'   => $pesoTotal,
                    'total_caixas' => $totalCaixas,
                    'valor_bruto'  => $valorBruto,
                    'valor_total'  => $valorTotal,
                ]);
            });
        }
    }
}
