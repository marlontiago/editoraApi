<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;

// Admin API Controllers
use App\Http\Controllers\Api\Admin\UserController as ApiAdminUserController;
use App\Http\Controllers\Api\Admin\DiretorComercialController as ApiAdminDiretorComercialController;
use App\Http\Controllers\Api\Admin\ClienteController as ApiAdminClienteController;
use App\Http\Controllers\Api\Admin\AdvogadoController as ApiAdminAdvogadoController;

use App\Http\Controllers\Api\Admin\GestorController as ApiAdminGestorController;
use App\Http\Controllers\Api\Admin\GestorAnexoController as ApiAdminGestorAnexoController;

use App\Http\Controllers\Api\Admin\DistribuidorController as ApiDistribuidorController;
use App\Http\Controllers\Api\Admin\DistribuidorAnexoController as ApiDistribuidorAnexoController;
use App\Http\Controllers\Api\Admin\CidadeController as ApiCidadeController;

use App\Http\Controllers\Api\Admin\ProdutoController as ApiProdutoController;
use App\Http\Controllers\Api\Admin\PedidoController as ApiPedidoController;
use App\Http\Controllers\Api\Admin\NotaFiscalController as ApiNotaFiscalController;
use App\Http\Controllers\Api\Admin\NotaPagamentoController as ApiNotaPagamentoController;

use App\Http\Controllers\Api\Admin\ColecaoController as ApiAdminColecaoController;
use App\Http\Controllers\Api\Admin\DashboardController as ApiAdminDashboardController;

// PlugNotas (web)
use App\Http\Controllers\PlugNotasSetupController;
use App\Http\Controllers\NotaFiscalPlugNotasController as C;
use App\Http\Controllers\WebhookPlugNotasController as W;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// =======================
// Autenticação (Sanctum)
// =======================
Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
});

// =======================
// Rotas Admin (API)
// =======================
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {

    // -----------------------
    // Usuários
    // -----------------------
    Route::apiResource('usuarios', ApiAdminUserController::class)
        ->parameters(['usuarios' => 'usuario']);

    // -----------------------
    // Diretor Comercial
    // -----------------------
    Route::apiResource('diretor-comercials', ApiAdminDiretorComercialController::class)
        ->parameters(['diretor-comercials' => 'diretor_comercial']);

    // -----------------------
    // Clientes
    // -----------------------
    Route::apiResource('clientes', ApiAdminClienteController::class)
        ->parameters(['clientes' => 'cliente']);

    // -----------------------
    // Advogados
    // -----------------------
    Route::apiResource('advogados', ApiAdminAdvogadoController::class);

    // =========================================================
    // Gestores (ATENÇÃO: rotas fixas antes de {gestor})
    // =========================================================

    // Vincular distribuidores (fixas ANTES de /gestores/{gestor})
    Route::get('gestores/vincular', [ApiAdminGestorController::class, 'vincularDistribuidores']);
    Route::post('gestores/vincular', [ApiAdminGestorController::class, 'storeVinculo']);

    // CRUD básico
    Route::get('gestores', [ApiAdminGestorController::class, 'index']);
    Route::post('gestores', [ApiAdminGestorController::class, 'store']);

    // Anexos/Contratos do gestor
    Route::delete('gestores/{gestor}/anexos/{anexo}', [ApiAdminGestorController::class, 'destroyAnexo'])
        ->whereNumber('gestor');

    Route::post('gestores/{gestor}/anexos/{anexo}/ativar', [ApiAdminGestorController::class, 'ativarAnexo'])
        ->whereNumber('gestor');

    // Controller separado (show/update anexo)
    Route::get('gestores/{gestor}/anexos/{anexo}', [ApiAdminGestorAnexoController::class, 'show'])
        ->whereNumber('gestor');

    Route::put('gestores/{gestor}/anexos/{anexo}', [ApiAdminGestorAnexoController::class, 'update'])
        ->whereNumber('gestor');

    // UFs do gestor
    Route::get('gestores/{gestor}/ufs', [ApiAdminGestorController::class, 'ufs'])
        ->whereNumber('gestor');

    // Auxiliares
    Route::get('utils/cidades-por-ufs', [ApiAdminGestorController::class, 'cidadesPorUfs']);

    // Rotas com {gestor} por último (e com whereNumber)
    Route::get('gestores/{gestor}', [ApiAdminGestorController::class, 'show'])
        ->whereNumber('gestor');

    Route::put('gestores/{gestor}', [ApiAdminGestorController::class, 'update'])
        ->whereNumber('gestor');

    Route::delete('gestores/{gestor}', [ApiAdminGestorController::class, 'destroy'])
        ->whereNumber('gestor');


    // =========================================================
    // Distribuidores (ATENÇÃO: rotas fixas antes do apiResource)
    // =========================================================

    // Filtros de cidades (fixas ANTES de /distribuidores/{distribuidor})
    Route::get('distribuidores/cidades-por-ufs', [ApiDistribuidorController::class, 'cidadesPorUfs'])
        ->name('admin.distribuidores.cidadesPorUfs');

    Route::get('distribuidores/cidades-por-gestor', [ApiDistribuidorController::class, 'cidadesPorGestor'])
        ->name('admin.distribuidores.cidadesPorGestor');

    // Extra: distribuidores por gestor
    Route::get('distribuidores/por-gestor/{gestor}', [ApiDistribuidorController::class, 'porGestor'])
        ->whereNumber('gestor');

    // Cidades por distribuidor
    Route::get('cidades/por-distribuidor/{distribuidor}', [ApiCidadeController::class, 'porDistribuidor'])
        ->whereNumber('distribuidor');

    // Anexos do distribuidor (ação no controller principal)
    Route::delete('distribuidores/{distribuidor}/anexos/{anexo}', [ApiDistribuidorController::class, 'destroyAnexo'])
        ->whereNumber('distribuidor');

    Route::post('distribuidores/{distribuidor}/anexos/{anexo}/ativar', [ApiDistribuidorController::class, 'ativarAnexo'])
        ->whereNumber('distribuidor');

    // Anexos (controller separado)
    Route::get('distribuidores/{distribuidor}/anexos/{anexo}', [ApiDistribuidorAnexoController::class, 'show'])
        ->name('admin.distribuidores.anexos.show')
        ->whereNumber('distribuidor');

    Route::put('distribuidores/{distribuidor}/anexos/{anexo}', [ApiDistribuidorAnexoController::class, 'update'])
        ->whereNumber('distribuidor');

    // Resource por último (para não “capturar” rotas fixas)
    Route::apiResource('distribuidores', ApiDistribuidorController::class)
        ->parameters(['distribuidores' => 'distribuidor']);


    // -----------------------
    // Produtos
    // -----------------------
    Route::apiResource('produtos', ApiProdutoController::class)->except(['show']);
    Route::post('produtos/import', [ApiProdutoController::class, 'import']);

    // -----------------------
    // Pedidos
    // -----------------------
    Route::get('pedidos/create', [ApiPedidoController::class, 'create']);
    Route::get('pedidos', [ApiPedidoController::class, 'index']);
    Route::post('pedidos', [ApiPedidoController::class, 'store']);
    Route::get('pedidos/{pedido}', [ApiPedidoController::class, 'show']);
    Route::put('pedidos/{pedido}', [ApiPedidoController::class, 'update']);

    // -----------------------
    // Nota Fiscal
    // -----------------------
    Route::post('pedidos/{pedido}/emitir-nota', [ApiNotaFiscalController::class, 'emitir']);
    Route::get('notas/{nota}', [ApiNotaFiscalController::class, 'show']);
    Route::post('notas/{nota}/faturar', [ApiNotaFiscalController::class, 'faturar']);
    Route::get('notas/{nota}/pdf', [ApiNotaFiscalController::class, 'pdf']);

    // -----------------------
    // Nota Pagamento
    // -----------------------
    Route::get('notas/{nota}/pagamentos/create', [ApiNotaPagamentoController::class, 'create']);
    Route::post('notas/{nota}/pagamentos', [ApiNotaPagamentoController::class, 'store']);
    Route::get('notas/{nota}/pagamentos/{pagamento}', [ApiNotaPagamentoController::class, 'show']);

    // -----------------------
    // Coleções
    // -----------------------
    Route::post('colecoes/quick-create', [ApiAdminColecaoController::class, 'quickCreate']);
    Route::delete('colecoes/{colecao}', [ApiAdminColecaoController::class, 'destroy']);

    // -----------------------
    // Dashboard
    // -----------------------
    Route::get('dashboard', [ApiAdminDashboardController::class, 'index']);

    Route::get('dashboard/charts/notas-pagas', [ApiAdminDashboardController::class, 'chartNotasPagas']);
    Route::get('dashboard/charts/vendas-por-gestor', [ApiAdminDashboardController::class, 'chartVendasPorGestor']);
    Route::get('dashboard/charts/vendas-por-distribuidor', [ApiAdminDashboardController::class, 'chartVendasPorDistribuidor']);
    Route::get('dashboard/charts/vendas-por-cliente', [ApiAdminDashboardController::class, 'chartVendasPorCliente']);
    Route::get('dashboard/charts/vendas-por-cidade', [ApiAdminDashboardController::class, 'chartVendasPorCidade']);

    Route::get('dashboard/export/excel', [ApiAdminDashboardController::class, 'exportExcel']);
    Route::get('dashboard/export/pdf', [ApiAdminDashboardController::class, 'exportPdf']);
});

// =======================
// PlugNotas (rotas web/API “ponte”)
// =======================
Route::post('/plugnotas/notas/{nota}/emitir',     [C::class, 'emitir']);
Route::get ('/plugnotas/notas/{nota}/consultar', [C::class, 'consultar']);
Route::get ('/plugnotas/notas/{nota}/pdf',       [C::class, 'pdf']);
Route::get ('/plugnotas/notas/{nota}/xml',       [C::class, 'xml']);

Route::post('/plugnotas/setup/empresa', [PlugNotasSetupController::class, 'empresa']);

// Webhook (a PlugNotas chama aqui)
Route::post('/plugnotas/webhook', [W::class, 'handle']);
