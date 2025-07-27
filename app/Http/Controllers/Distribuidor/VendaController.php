<?php

namespace App\Http\Controllers\Distribuidor;

use App\Http\Controllers\Controller;
use App\Models\Produto;
use App\Models\Venda;
use App\Models\Comissao;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VendaController extends Controller
{
    public function index()
    {
        $distribuidor = Auth::user()->distribuidor;

        $vendas = Venda::where('distribuidor_id', $distribuidor->id)
            ->with('produto')
            ->latest()
            ->paginate(10);

        $totalComissao = Venda::where('distribuidor_id', $distribuidor->id)->sum('comissao');

        return view('distribuidor.vendas.index', compact('vendas', 'totalComissao'));
    }

    public function create()
    {
        $produtos = Produto::orderBy('nome')->get();
        return view('distribuidor.vendas.create', compact('produtos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'produto_id' => 'required|exists:produtos,id',
            'quantidade' => 'required|integer|min:1',
        ]);

        $distribuidor = Auth::user()->distribuidor;

        // Busca percentual da comissão do distribuidor (ex: 10%)
        $comissaoDistribuidor = Comissao::where('tipo', 'distribuidor')->firstOrFail();
        $percentualComissao = $comissaoDistribuidor->percentual;

        DB::transaction(function () use ($request, $distribuidor, $percentualComissao) {
            // Bloqueia o produto para atualização concorrente de estoque
            $produto = Produto::where('id', $request->produto_id)->lockForUpdate()->first();

            if ($produto->quantidade_estoque < $request->quantidade) {
                abort(422, 'Estoque insuficiente para essa venda.');
            }

            $valorTotal = $produto->preco * $request->quantidade;
            $valorComissao = $valorTotal * ($percentualComissao / 100);

            // Cria a venda
            Venda::create([
                'distribuidor_id' => $distribuidor->id,
                'produto_id' => $produto->id,
                'quantidade' => $request->quantidade,
                'valor_total' => $valorTotal,
                'comissao' => $valorComissao,
            ]);

            // Baixa no estoque
            $produto->decrement('quantidade_estoque', $request->quantidade);
        });

        return to_route('distribuidor.vendas.index')->with('success', 'Venda registrada com sucesso!');
    }

    // (show, edit, update, destroy) -> não vamos expor por enquanto
}
