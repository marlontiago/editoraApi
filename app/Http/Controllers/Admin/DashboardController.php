<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Gestor;
use App\Models\Distribuidor;
use App\Models\Produto;
use App\Models\Pedido;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Aqui busca no banco e conta a quantidade de produtos, gestores e usuarios.
        $totalProdutos = Produto::count();
        $totalGestores = Gestor::count();
        $totalUsuarios = User::count();

        // Aqui gera a lista para listar no select na view.
        $gestoresList = Gestor::with('user:id,name')->orderBy('razao_social')->get();
        $distribuidoresList = Distribuidor::with('user:id,name')->orderBy('razao_social')->get();
        $gestoresComDistribuidores = Gestor::with([
            'user:id,name',
            'distribuidores.user:id,name',
        ])->orderBy('razao_social')->get();

        // Aqui valida os filtros vindos na requisição.
        $request->validate([
            'data_inicio'     => ['nullable', 'date'],
            'data_fim'        => ['nullable', 'date', 'after_or_equal:data_inicio'],
            'gestor_id'       => ['nullable', 'integer', 'exists:gestores,id'],
            'distribuidor_id' => ['nullable', 'integer', 'exists:distribuidores,id'],
        ]);

        // Aqui salva os filtros vindos da requisição em variáveis.
        $dataInicio     = $request->input('data_inicio');      
        $dataFim        = $request->input('data_fim');         
        $gestorId       = $request->input('gestor_id');
        $distribuidorId = $request->input('distribuidor_id');

        // Aqui monta a query para efetuar a busca.
        $baseQuery = Pedido::with([
            'gestor.user:id,name',
            'gestor.distribuidores.user:id,name',
            'distribuidor.user:id,name',
            'cidades:id,name',
        ]);

        // Se o usuário selecionou alguma data o filtro é aplicado aqui.
        if ($dataInicio && $dataFim) {
            $baseQuery->whereBetween('data', [
                Carbon::parse($dataInicio)->toDateString(),
                Carbon::parse($dataFim)->toDateString(),
            ]);
        } elseif ($dataInicio) {
            $baseQuery->where('data', '>=', Carbon::parse($dataInicio)->toDateString());
        } elseif ($dataFim) {
            $baseQuery->where('data', '<=', Carbon::parse($dataFim)->toDateString());
        }

        // Se o usuário selecionou algum gestor ou algum distribuidor o filtro é aplicado aqui.
        if ($gestorId) {
            $baseQuery->where('gestor_id', $gestorId);
        }
        if ($distribuidorId) {
            $baseQuery->where('distribuidor_id', $distribuidorId);
        }

        // Aqui é calculado o total de pedidos e a soma dos pedidos so período com base na $baseQuery montada acima.
        $totalPedidosPeriodo   = (clone $baseQuery)->count();
        $somaPeriodo           = (clone $baseQuery)->sum('valor_total');

        // Aqui faz a listagem paginada com base na $baseQuery montada.
        $pedidos = (clone $baseQuery)
            ->latest('id')
            ->paginate(20)
            ->appends($request->only('data_inicio','data_fim','gestor_id','distribuidor_id'));

        // Aqui mostra a soma com base na página atual e filtros aplicados.
        $somaPagina = $pedidos->getCollection()->sum('valor_total');

        // Aqui mostra a soma total de todos os pedidos salvos no banco, sem levar em consideração qualquer filtro.
        $somaGeralTodosPedidos = Pedido::sum('valor_total');

        return view('admin.dashboard', [
            'pedidos'                   => $pedidos,
            'totalGestores'             => $totalGestores,
            'totalProdutos'             => $totalProdutos,
            'totalUsuarios'             => $totalUsuarios,
            'totalPedidosPeriodo'       => $totalPedidosPeriodo,
            'somaPeriodo'               => $somaPeriodo,
            'somaPagina'                => $somaPagina,
            'somaGeralTodosPedidos'     => $somaGeralTodosPedidos,
            'dataInicio'                => $dataInicio,
            'dataFim'                   => $dataFim,
            'gestorId'                  => $gestorId,
            'distribuidorId'            => $distribuidorId,
            'gestoresList'              => $gestoresList,
            'distribuidoresList'        => $distribuidoresList,
            'gestoresComDistribuidores' => $gestoresComDistribuidores,
        ]);
    }
}
