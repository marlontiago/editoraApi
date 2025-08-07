<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pedido;
use App\Models\Produto;
use App\Models\City;
use App\Models\Distribuidor;
use App\Models\Gestor;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class PedidoController extends Controller
{
    public function index()
    {
        $pedidos = Pedido::with(['cidades', 'gestor', 'distribuidor.user'])->latest()->get();
        return view('admin.pedidos.index', compact('pedidos'));
    }

    public function create()
    {
        $gestores = Gestor::with('user')->orderBy('razao_social')->get();
        $distribuidores = Distribuidor::with('user')->orderBy('razao_social')->get();
        $produtos = Produto::orderBy('nome')->get();
        $cidades = City::orderBy('name')->get();        

        return view('admin.pedidos.create', compact('produtos', 'cidades', 'gestores', 'distribuidores'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'data' => 'required|date',
            'produtos' => 'required|array|min:1',
            'produtos.*.id' => 'required|exists:produtos,id',
            'produtos.*.quantidade' => 'required|integer|min:1',
            'desconto' => 'nullable|numeric|min:0|max:100',
        ]);

        try {
            DB::beginTransaction();

            $pedido = Pedido::create([
                'gestor_id' => $request->gestor_id,
                'distribuidor_id' => $request->distribuidor_id,
                'data' => $request->data,
                'status' => 'em_andamento',
                'desconto' => $request->desconto ?? 0,
            ]);

            // Relacionar cidades (do gestor e distribuidor)
            $cidadesGestor = $request->gestor_id
                ? Gestor::with('cities')->find($request->gestor_id)?->cities ?? collect()
                : collect();

            $cidadesDistribuidor = $request->distribuidor_id
                ? Distribuidor::with('cities')->find($request->distribuidor_id)?->cities ?? collect()
                : collect();

            $todasCidades = $cidadesGestor->merge($cidadesDistribuidor)->unique('id');
            $pedido->cidades()->sync($todasCidades->pluck('id'));

            $pesoTotal = 0;
            $totalCaixas = 0;
            $valorBruto = 0;
            $valorComDesconto = 0;
            $desconto = is_numeric($request->desconto) ? floatval($request->desconto) : 0;

            foreach ($validated['produtos'] as $produtoData) {
                //  trava a linha do produto
                $produto = Produto::whereKey($produtoData['id'])->lockForUpdate()->firstOrFail();
                $quantidade = (int) $produtoData['quantidade'];

                //  validação de estoque
                $disponivel = (int) $produto->quantidade_estoque;
                if ($disponivel < $quantidade) {
                    throw new \RuntimeException(
                        "Estoque insuficiente para o produto {$produto->nome}. Disponível: {$disponivel}, solicitado: {$quantidade}"
                    );
                }

                // cálculos
                $precoUnitario = (float) $produto->preco;
                $subtotalBruto = $precoUnitario * $quantidade;

                $precoComDesconto = $precoUnitario * (1 - ($desconto / 100));
                $subtotalComDesconto = $precoComDesconto * $quantidade;

                $pesoTotalProduto = (float) ($produto->peso ?? 0) * $quantidade;
                $porCaixa = max(1, (int) $produto->quantidade_por_caixa);
                $caixas = (int) ceil($quantidade / $porCaixa);

                // pivot
                $pedido->produtos()->attach($produto->id, [
                    'quantidade'           => $quantidade,
                    'preco_unitario'       => $precoUnitario,
                    'desconto_aplicado'    => $desconto,
                    'subtotal'             => $subtotalComDesconto,
                    'peso_total_produto'   => $pesoTotalProduto,
                    'caixas'               => $caixas,
                ]);

                //  debita estoque
                $produto->decrement('quantidade_estoque', $quantidade);

                // acumuladores
                $pesoTotal        += $pesoTotalProduto;
                $totalCaixas      += $caixas;
                $valorBruto       += $subtotalBruto;
                $valorComDesconto += $subtotalComDesconto;
            }

            $pedido->update([
                'peso_total' => $pesoTotal,
                'total_caixas' => $totalCaixas,
                'valor_bruto' => $valorBruto,
                'valor_total' => $valorComDesconto,
            ]);

            DB::commit();

            return redirect()->route('admin.pedidos.index')->with('success', 'Pedido criado com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Erro ao criar pedido: ' . $e->getMessage()]);
        }
    }

    public function show(Pedido $pedido)
    {
        $pedido->load(['cidades', 'gestor', 'distribuidor.user', 'produtos']);
        return view('admin.pedidos.show', compact('pedido'));
    }

    public function exportar(Pedido $pedido, string $tipo)
    {
        $pedido->load(['produtos', 'cidades', 'gestor', 'distribuidor.user']);

        if (!in_array($tipo, ['relatorio', 'orcamento'])) abort(404);

        $view = $tipo === 'relatorio'
            ? 'admin.pedidos.pdf.relatorio'
            : 'admin.pedidos.pdf.orcamento';

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView($view, [
            'pedido' => $pedido,
            'tipo'   => $tipo,
        ])
        ->setPaper('a4')
        ->setOptions([
            'defaultFont'     => 'DejaVu Sans',
            'isRemoteEnabled' => true, // permite carregar imagens via caminho/URL
        ]);

        return $pdf->download("pedido-{$pedido->id}-{$tipo}.pdf");
    }



}
