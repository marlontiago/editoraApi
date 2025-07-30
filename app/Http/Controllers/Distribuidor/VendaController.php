<?php

namespace App\Http\Controllers\Distribuidor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Venda;
use App\Models\Produto;
use Carbon\Carbon;
use App\Exports\DistribuidorVendasExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

class VendaController extends Controller
{
    public function index(Request $request)
    {
        $vendas = $this->filtrarVendas($request)->paginate(10);

        return view('distribuidor.vendas.index', compact('vendas'));
    }

    public function create()
    {
        $produtos = Produto::all();
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

        return redirect()->route('distribuidor.vendas.index')->with('success', 'Venda registrada com sucesso.');

        } catch (\Exception $e) {
            DB::rollBack();
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
