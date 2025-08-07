<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardRedirectController;
use App\Http\Controllers\Distribuidor\VendaController;
use App\Http\Controllers\Admin\PedidoController;

Route::get('/', function () {
    return view('auth.login');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/dashboard', [DashboardRedirectController::class, 'redirect'])->name('dashboard.redirect');
    
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');
    Route::resource('produtos', \App\Http\Controllers\Admin\ProdutoController::class)->except("show");
    Route::resource('gestores', \App\Http\Controllers\Admin\GestorController::class)->parameters(['gestores' => 'gestor'])->except('show');
    Route::resource('comissoes', \App\Http\Controllers\Admin\CommissionController::class)->parameters(['comissoes' => 'commission'])->except(['show']);
    Route::resource('distribuidores', \App\Http\Controllers\Admin\DistribuidorController::class)->names('distribuidores')->parameters(['distribuidores' => 'distribuidor'])->except('show');

    Route::get('gestores/vincular', [\App\Http\Controllers\Admin\GestorController::class, 'vincularDistribuidores'])->name('admin.gestores.vincular');
    Route::post('gestores/vincular', [\App\Http\Controllers\Admin\GestorController::class, 'storeVinculo'])->name('admin.gestores.vincular.salvar');

    Route::resource('usuarios', \App\Http\Controllers\Admin\UserController::class);

    Route::get('/pedidos/create', [PedidoController::class, 'create'])->name('pedidos.create');
    Route::post('/pedidos', [PedidoController::class, 'store'])->name('pedidos.store');
    Route::get('/pedidos', [PedidoController::class, 'index'])->name('pedidos.index');
    Route::get('/pedidos/{pedido}', [PedidoController::class, 'show'])->name('pedidos.show');
    
    Route::get('/cidades/por-gestor/{id}', [\App\Http\Controllers\Admin\CidadeController::class, 'cidadesPorGestor']);
    Route::get('/cidades/por-distribuidor/{id}', [\App\Http\Controllers\Admin\CidadeController::class, 'cidadesPorDistribuidor']);
    
});

Route::middleware(['auth', 'role:gestor'])->prefix('gestor')->name('gestor.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Gestor\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/relatorios/vendas', [\App\Http\Controllers\Gestor\RelatorioVendasController::class, 'index'])->name('relatorios.vendas');
    Route::get('/relatorios/vendas/export/excel', [\App\Http\Controllers\Gestor\RelatorioVendasController::class, 'exportExcel'])->name('relatorios.vendas.export.excel');
    Route::get('/relatorios/vendas/export/pdf', [\App\Http\Controllers\Gestor\RelatorioVendasController::class, 'exportPdf'])->name('relatorios.vendas.export.pdf');
});

Route::middleware(['auth', 'role:distribuidor'])->prefix('distribuidor')->name('distribuidor.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Distribuidor\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/vendas', [\App\Http\Controllers\Distribuidor\VendaController::class, 'index'])->name('vendas.index');
    Route::get('/vendas/create', [\App\Http\Controllers\Distribuidor\VendaController::class, 'create'])->name('vendas.create');
    Route::post('/vendas', [\App\Http\Controllers\Distribuidor\VendaController::class, 'store'])->name('vendas.store');
    Route::get('/vendas/export/excel', [VendaController::class, 'exportExcel'])->name('vendas.export.excel');
    Route::get('/vendas/export/pdf', [VendaController::class, 'exportPdf'])->name('vendas.export.pdf');
});

require __DIR__.'/auth.php';
