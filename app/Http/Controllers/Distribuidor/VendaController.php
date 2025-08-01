<?php

namespace App\Http\Controllers\Distribuidor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Venda;
use App\Models\Produto;
use Carbon\Carbon;
use App\Exports\DistribuidorVendasExport;
use App\Models\Commission;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

class VendaController extends Controller
{
    public function index(Request $request)
    {
        
        // Filtra as vendas com os parâmetros vindos pela URL na $request
        $vendas = $this->filtrarVendas($request)->paginate(10);
        // Pega o distribuidor na variável
        $distribuidor = Auth::user()->distribuidor;
        // Pega o gestor desse distribuidor
        $gestor = $distribuidor->gestor;        

        //Pega o valor da comissão do distribuidor 
        $comissaoDistribuidor = Commission::where('user_id', $distribuidor->user_id)
        ->where('tipo_usuario', 'distribuidor')
        ->orderByDesc('id')
        ->value('percentage');

        //Pega o valor da comissão do gestor
        $comissaoGestor = Commission::where('user_id', $gestor->user_id)
        ->where('tipo_usuario', 'gestor')
        ->orderByDesc('id')
        ->value('percentage');

        //Calcula o valor total das vendas com o filtro aplicado
        $vendas = $this->filtrarVendas($request)->paginate(10);
        foreach ($vendas as $venda) {           
            $venda->comissao_distribuidor_valor = $venda->valor_total * ($comissaoDistribuidor / 100);
            $venda->comissao_gestor_valor = $venda->valor_total * ($comissaoGestor / 100);
        }

        //Envia a resposta no formato JSON
        if($request->wantsJson())
        {
            return response()->json([
                'vendas' => $vendas,
                'comissaoDistribuidor' => $$comissaoDistribuidor,
                'comissaoGestor' => $comissaoGestor,
            ]);
        }

        return view('distribuidor.vendas.index', compact(
            'vendas',
            'comissaoDistribuidor',
            'comissaoGestor',
        ));
    }

    public function create(Request $request)
    {
        $produtos = Produto::all();

        if($request->wantsJson())
        {
            return response()->json([
                'produtos' => $produtos,
            ]);
        }

        return view('distribuidor.vendas.create', compact('produtos'));
    }

    public function store(Request $request)
    {        
        $request->validate([
            'produtos' => 'required|array',
            'produtos.*.id' => 'required|exists:produtos,id',
            'produtos.*.quantidade' => 'required|integer|min:1',
        ]);        

        $distribuidor = Auth::user()->distribuidor;
        $gestorId = $distribuidor->gestor_id;
        $valorTotal = 0;

        DB::beginTransaction();

        try {
        // Verifica se todos os produtos têm estoque suficiente
        foreach ($request->produtos as $produto) {
            $produtoModel = Produto::find($produto['id']);

            if ($produtoModel->quantidade_estoque < $produto['quantidade']) {
                throw new \Exception("Estoque insuficiente para o produto: {$produtoModel->nome}");
            }

            $valorTotal += $produtoModel->preco * $produto['quantidade'];
        }

        // Cria a venda
        $venda = Venda::create([
            'distribuidor_id' => $distribuidor->id,
            'gestor_id' => $gestorId,
            'data' => Carbon::today()->toDateString(),
            'valor_total' => $valorTotal,
        ]);

        
        // Associa produtos à venda e dá baixa no estoque
        foreach ($request->produtos as $produto) {
            $produtoModel = Produto::find($produto['id']);

            $venda->produtos()->attach($produto['id'], [
                'quantidade' => $produto['quantidade'],
                'preco_unitario' => $produtoModel->preco,
            ]);

            $produtoModel->quantidade_estoque -= $produto['quantidade'];
            $produtoModel->save();
        }

        DB::commit();

        if($request->wantsJson())
        {
            return response()->json([
                'message' => 'Venda registrada com sucesso.',
            ]);
        }

        return redirect()->route('distribuidor.vendas.index')->with('success', 'Venda registrada com sucesso.');

        } catch (\Exception $e) {
            DB::rollBack();
            if($request->wantsJson())
                {
                    return response()->json([
                        'message' => 'Erro ao registrar venda',
                        'error' => $e->getMessage(),
                    ], 500);
                }

            return redirect()->back()->withErrors(['error' => 'Erro ao registrar venda: ' . $e->getMessage()])->withInput();
        }
        }

    public function exportExcel(Request $request)
    {
        
        return Excel::download(new DistribuidorVendasExport($request), 'vendas_distribuidor.xlsx');
    }

    public function exportPdf(Request $request)
    {
        $vendas = $this->filtrarVendas($request)->get();
        $pdf = Pdf::loadView('distribuidor.vendas.pdf', compact('vendas'));
        return $pdf->download('vendas_distribuidor.pdf');
    }

// Método interno para reutilizar filtro
    private function filtrarVendas(Request $request)
    {

        //dd('filtrarVendas chamado');
        $distribuidor = Auth::user()->distribuidor;
        $query = Venda::with(['produtos'])->where('distribuidor_id', $distribuidor->id);

        if ($request->filled('periodo')) {
        if ($request->periodo === 'semana') {

            $inicioSemana = Carbon::now()->startOfWeek(Carbon::MONDAY)->toDateString();
            $fimSemana = Carbon::now()->endOfWeek(Carbon::SUNDAY)->toDateString();

            $query->whereBetween('data', [$inicioSemana, $fimSemana]);

            } elseif ($request->periodo === 'mes') {
                $hoje = Carbon::now();
                $query->whereMonth('data', $hoje->month)->whereYear('data', $hoje->year);
            }
    }
            if ($request->filled('inicio') && $request->filled('fim')) {
                $query->whereBetween('data', [$request->inicio, $request->fim]);
            }

            return $query->orderByDesc('data');
        }
}
