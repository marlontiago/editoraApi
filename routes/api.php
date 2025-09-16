<?php

use App\Http\Controllers\Admin\ClienteController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\ProdutoController;
use App\Http\Controllers\Admin\GestorController;
use App\Http\Controllers\Admin\DistribuidorController;
use App\Http\Controllers\Admin\PedidoController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\NotaFiscalController;

Route::middleware([])
    ->prefix('admin')
    ->name('api.admin.')
    ->group(function () {

        // ===== Produtos CRUD =====
        Route::apiResource('produtos', ProdutoController::class);

        // ===== Gestores CRUD =====
        Route::get('gestores',                 [GestorController::class, 'index']);
        Route::post('gestores',                [GestorController::class, 'store']);
        Route::get('gestores/{gestor}/edit',   [GestorController::class, 'edit']);   // dados p/ edição + cities
        Route::put('gestores/{gestor}',        [GestorController::class, 'update']);
        Route::delete('gestores/{gestor}',     [GestorController::class, 'destroy']);

        // ===== Distribuidores CRUD =====
        Route::get('distribuidores',                        [DistribuidorController::class, 'index']);
        Route::post('distribuidores',                       [DistribuidorController::class, 'store']);
        Route::get('distribuidores/{distribuidor}/edit',    [DistribuidorController::class, 'edit']);
        Route::put('distribuidores/{distribuidor}',         [DistribuidorController::class, 'update']);
        Route::delete('distribuidores/{distribuidor}',      [DistribuidorController::class, 'destroy']);
        Route::get('distribuidores/por-gestor/{gestor}',    [DistribuidorController::class, 'porGestor']);

        // ===== Clientes CRUD =====
        
        Route::apiResource('clientes', ClienteController::class);

        // Cidades vinculadas à UF do gestor
        Route::get('gestores/{gestor}/cidades', [GestorController::class, 'cidadesPorGestor']);

        // Vínculos gestor <-> distribuidores
        Route::get('gestores/vinculos',  [GestorController::class, 'vincularDistribuidores']);
        Route::post('gestores/vinculos', [GestorController::class, 'storeVinculo']);

        // ===== Pedidos =====
        Route::get('pedidos',                [PedidoController::class, 'index'])->name('pedidos.index');
        Route::post('pedidos',               [PedidoController::class, 'store'])->name('pedidos.store');
        Route::get('pedidos/{pedido}',       [PedidoController::class, 'show'])->name('pedidos.show');
        Route::put('pedidos/{pedido}',       [PedidoController::class, 'update'])->name('pedidos.update');
        Route::get('pedidos/{pedido}/edit',  [PedidoController::class, 'edit'])->name('pedidos.edit');
        // Exportar PDF pelo API devolve 406 (já tratado no controller)
        Route::get('pedidos/{pedido}/exportar/{tipo}', [PedidoController::class, 'exportar'])->name('pedidos.exportar');

        // ===== Nota Fiscal ======

        Route::post('notas/emitir/{pedido}', [NotaFiscalController::class, 'emitir'])->name('notas.emitir');
        Route::get('notas/{nota}', [NotaFiscalController::class, 'show'])->name('notas.show');
        Route::post('notas/{nota}/faturar', [NotaFiscalController::class, 'faturar'])->name('notas.faturar');
        Route::get('notas/{nota}/pdf', [NotaFiscalController::class, 'pdf'])->name('notas.pdf');

        // ===== Dashboard =====
        Route::get('dashboard',                               [DashboardController::class, 'index']);
        Route::get('dashboard/charts/notas-pagas',            [DashboardController::class, 'chartNotasPagas']);
        Route::get('dashboard/charts/vendas-por-gestor',      [DashboardController::class, 'chartVendasPorGestor']);
        Route::get('dashboard/charts/vendas-por-distribuidor',[DashboardController::class, 'chartVendasPorDistribuidor']);
        Route::get('dashboard/charts/vendas-por-cliente',     [DashboardController::class, 'chartVendasPorCliente']);
        Route::get('dashboard/charts/vendas-por-cidade',      [DashboardController::class, 'chartVendasPorCidade']);

        // Exports (na API o controller deve devolver 406 se Accept: application/json)
        Route::get('dashboard/export/excel', [DashboardController::class, 'exportExcel']);
        Route::get('dashboard/export/pdf',   [DashboardController::class, 'exportPdf']);
    });
