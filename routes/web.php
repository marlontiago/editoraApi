<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardRedirectController;
use App\Http\Controllers\Distribuidor\VendaController;
use App\Http\Controllers\Admin\PedidoController;
use App\Http\Controllers\Admin\GestorController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProdutoController;
use App\Http\Controllers\Admin\DistribuidorController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\CidadeController;
use App\Http\Controllers\Admin\ClienteController;
use App\Http\Controllers\Admin\NotaFiscalController;
use App\Http\Controllers\Admin\NotaPagamentoController;
use App\Http\Controllers\Admin\AdvogadoController;
use App\Http\Controllers\Admin\DiretorComercialController;

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
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('produtos',ProdutoController::class)->except("show");
    Route::resource('usuarios', UserController::class);
    Route::resource('clientes', ClienteController::class)->parameters(['clientes' => 'cliente'])->except('show');
    Route::resource('advogados', AdvogadoController::class);
    Route::resource('diretor-comercials', DiretorComercialController::class)->parameters(['diretor-comercials' => 'diretor_comercial']);


    Route::resource('distribuidores', DistribuidorController::class)->names('distribuidores')->parameters(['distribuidores' => 'distribuidor'])->except('show');
    Route::get('distribuidores/por-gestor/{gestor}', [DistribuidorController::class, 'porGestor'])->name('distribuidores.por-gestor');
    Route::get('/cidades/por-distribuidor/{id}', [CidadeController::class, 'cidadesPorDistribuidor']);

    Route::resource('gestores', GestorController::class)->parameters(['gestores' => 'gestor'])->except('show');
    Route::get('gestores/vincular', [GestorController::class, 'vincularDistribuidores'])->name('gestores.vincular');
    Route::post('gestores/vincular', [GestorController::class, 'storeVinculo'])->name('gestores.vincular.salvar');
    Route::get('gestores/{gestor}/cidades', [GestorController::class, 'cidadesPorGestor'])->name('gestores.cidades');
    Route::get('/cidades/por-gestor/{gestor}', [CidadeController::class, 'cidadesPorGestor'])->name('cidades.por-gestor');
    
    Route::get('cidades/por-uf/{uf}', [CidadeController::class, 'porUf']);

    Route::get('/pedidos/create', [PedidoController::class, 'create'])->name('pedidos.create');
    Route::post('/pedidos', [PedidoController::class, 'store'])->name('pedidos.store');
    Route::get('/pedidos', [PedidoController::class, 'index'])->name('pedidos.index');
    Route::get('/pedidos/{pedido}', [PedidoController::class, 'show'])->name('pedidos.show');
    Route::get('/pedidos/{pedido}/exportar/{tipo}', [PedidoController::class, 'exportar'])->name('pedidos.exportar');
    Route::get('/pedidos/{pedido}/edit', [PedidoController::class, 'edit'])->name('pedidos.edit');
    Route::put('/pedidos/{pedido}', [PedidoController::class, 'update'])->name('pedidos.update');
    Route::get('/dashboard/export/excel', [DashboardController::class, 'exportExcel'])->name('admin.dashboard.export.excel');
    Route::get('/dashboard/export/pdf', [DashboardController::class, 'exportPdf'])->name('admin.dashboard.export.pdf');
        
    Route::post('/pedidos/{pedido}/emitir-nota', [NotaFiscalController::class, 'emitir'])->name('pedidos.emitir-nota');
    Route::get('/notas/{nota}', [NotaFiscalController::class, 'show'])->name('notas.show');
    Route::post('/notas/{nota}/faturar', [NotaFiscalController::class, 'faturar'])->name('notas.faturar');
    Route::get('/notas/{nota}/pdf', [NotaFiscalController::class, 'pdf'])->name('notas.pdf');    

    Route::get('/notas/{nota}/pagamentos/create', [NotaPagamentoController::class, 'create'])->name('notas.pagamentos.create');
    Route::post('/notas/{nota}/pagamentos',        [NotaPagamentoController::class, 'store'])->name('notas.pagamentos.store');
    Route::get('notas/{nota}/pagamentos/{pagamento}', [NotaPagamentoController::class, 'show'])->name('notas.pagamentos.show');
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
