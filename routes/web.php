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
use App\Http\Controllers\Admin\DistribuidorAnexoController;
use App\Http\Controllers\RelatoriosController;
use App\Http\Controllers\NotaFiscalPlugNotasController;
use App\Http\Controllers\NotaFiscalPlugBridgeController;
use App\Http\Controllers\Admin\GestorAnexoController;
use App\Models\Gestor;

Route::pattern('gestor', '[0-9]+');        // {gestor} numérico
Route::pattern('distribuidor', '[0-9]+');  // {distribuidor} numérico

Route::get('/', function () {
    return view('auth.login');
});



Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Redireciona para o dashboard correto por papel
    Route::get('/dashboard', [DashboardRedirectController::class, 'redirect'])->name('dashboard.redirect');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    // Dashboard + charts
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/charts/notas-pagas', [DashboardController::class, 'chartNotasPagas'])->name('dashboard.charts.notas_pagas');
    Route::get('/dashboard/charts/vendas-por-gestor', [DashboardController::class, 'chartVendasPorGestor'])->name('dashboard.charts.vendas_por_gestor');
    Route::get('/dashboard/charts/vendas-por-distribuidor', [DashboardController::class, 'chartVendasPorDistribuidor'])->name('dashboard.charts.vendas_por_distribuidor');
    Route::get('/dashboard/charts/vendas-por-cliente', [DashboardController::class, 'chartVendasPorCliente'])->name('dashboard.charts.vendas_por_cliente');
    Route::get('/dashboard/charts/vendas-por-cidade',  [DashboardController::class, 'chartVendasPorCidade'])->name('dashboard.charts.vendas_por_cidade');
    // Export do dashboard
    Route::get('/dashboard/export/excel', [DashboardController::class, 'exportExcel'])->name('dashboard.export.excel');
    Route::get('/dashboard/export/pdf', [DashboardController::class, 'exportPdf'])->name('dashboard.export.pdf');
    // Recursos
    Route::resource('produtos', ProdutoController::class)->except('show');
    Route::resource('usuarios', UserController::class);
    Route::resource('clientes', ClienteController::class)->parameters(['clientes' => 'cliente']);
    Route::resource('advogados', AdvogadoController::class);
    Route::resource('diretor-comercials', DiretorComercialController::class)->parameters(['diretor-comercials' => 'diretor_comercial']);

    Route::resource('distribuidores', DistribuidorController::class)->names('distribuidores')->parameters(['distribuidores' => 'distribuidor']);
    Route::get('distribuidores/por-gestor/{gestor}', [DistribuidorController::class, 'porGestor'])->name('distribuidores.por-gestor')->whereNumber('gestor');
    Route::get('/cidades/por-distribuidor/{distribuidor}', [CidadeController::class, 'porDistribuidor'])->name('cidades.por-distribuidor')->whereNumber('distribuidor');
    Route::delete('distribuidores/{distribuidor}/anexos/{anexo}', [DistribuidorController::class, 'destroyAnexo'])->name('distribuidores.anexos.destroy')->whereNumber('distribuidor');
    Route::post('distribuidores/{distribuidor}/anexos/{anexo}/ativar', [DistribuidorController::class, 'ativarAnexo'])->name('distribuidores.anexos.ativar')->whereNumber('distribuidor');
    // routes/web.php (ou api.php, como preferir)
    Route::get('distribuidores/cidades-por-ufs', [DistribuidorController::class, 'cidadesPorUfs'])->name('distribuidores.cidadesPorUfs');
    Route::get('distribuidores/cidades-por-gestor', [DistribuidorController::class, 'cidadesPorGestor'])->name('distribuidores.cidadesPorGestor');
    Route::get('distribuidores/{distribuidor}/anexos/{anexo}/edit', [DistribuidorAnexoController::class, 'edit'])->name('distribuidores.anexos.edit');
    Route::put('distribuidores/{distribuidor}/anexos/{anexo}', [DistribuidorAnexoController::class, 'update'])->name('distribuidores.anexos.update');

    Route::post('gestores/{gestor}/anexos/{anexo}/ativar', [GestorController::class, 'ativarAnexo'])->name('gestores.anexos.ativar');
    Route::get('gestores/vincular', [GestorController::class, 'vincularDistribuidores'])->name('gestores.vincular');
    Route::post('gestores/vincular', [GestorController::class, 'storeVinculo'])->name('gestores.vincular.salvar');
    Route::resource('gestores', GestorController::class)->parameters(['gestores' => 'gestor']);
    Route::get('gestores/{gestor}/anexos/{anexo}/edit', [GestorAnexoController::class, 'edit'])->name('gestores.anexos.edit');
    Route::put('gestores/{gestor}/anexos/{anexo}', [GestorAnexoController::class, 'update'])->name('gestores.anexos.update');

    // JSON: UFs do gestor (para filtrar selects sem recarregar a página)
    Route::get('gestores/{gestor}/ufs', [GestorController::class, 'ufs'])->name('gestores.ufs');
    Route::get('/utils/cidades-por-ufs', [GestorController::class, 'cidadesPorUfs'])->name('utils.cidades-por-ufs');

    // (Opcional) se você usa esta rota em algum lugar, o método existe no controller
    // Route::get('gestores/{gestor}/cidades', [GestorController::class, 'cidadesPorGestor'])->name('gestores.cidades')->whereNumber('gestor');

    Route::get('/cidades/por-gestor/{gestor}', [CidadeController::class, 'cidadesPorGestor'])->name('cidades.por-gestor')->whereNumber('gestor');
    Route::delete('gestores/{gestor}/anexos/{anexo}', [GestorController::class, 'destroyAnexo'])->name('gestores.anexos.destroy')->whereNumber('gestor');

    Route::get('cidades/por-uf/{uf}', [CidadeController::class, 'porUf']);
    Route::get('cidades/busca', [CidadeController::class, 'search'])->name('cidades.search');

    Route::get('/pedidos/create', [PedidoController::class, 'create'])->name('pedidos.create');
    Route::post('/pedidos', [PedidoController::class, 'store'])->name('pedidos.store');
    Route::get('/pedidos', [PedidoController::class, 'index'])->name('pedidos.index');
    Route::get('/pedidos/{pedido}', [PedidoController::class, 'show'])->name('pedidos.show');
    Route::get('/pedidos/{pedido}/exportar/{tipo}', [PedidoController::class, 'exportar'])->name('pedidos.exportar');
    Route::get('/pedidos/{pedido}/edit', [PedidoController::class, 'edit'])->name('pedidos.edit');
    Route::put('/pedidos/{pedido}', [PedidoController::class, 'update'])->name('pedidos.update');

    // Notas Fiscais & Pagamentos
    Route::post('/pedidos/{pedido}/emitir-nota', [NotaFiscalController::class, 'emitir'])->name('pedidos.emitir-nota');
    Route::get('/notas/{nota}', [NotaFiscalController::class, 'show'])->name('notas.show');
    Route::post('/notas/{nota}/faturar', [NotaFiscalController::class, 'faturar'])->name('notas.faturar');
    Route::get('/notas/{nota}/pdf', [NotaFiscalController::class, 'pdf'])->name('notas.pdf');

    Route::get('/notas/{nota}/pagamentos/create', [NotaPagamentoController::class, 'create'])->name('notas.pagamentos.create');
    Route::post('/notas/{nota}/pagamentos', [NotaPagamentoController::class, 'store'])->name('notas.pagamentos.store');
    Route::get('notas/{nota}/pagamentos/{pagamento}', [NotaPagamentoController::class, 'show'])->name('notas.pagamentos.show');

    // Relatórios
    Route::get('/relatorios', [RelatoriosController::class, 'index'])->name('relatorios.index');

    Route::post('notas/{nota}/plugnotas/emitir',    [NotaFiscalPlugBridgeController::class, 'emitir'])->name('notas.plug.emitir');
    Route::get ('notas/{nota}/plugnotas/consultar', [NotaFiscalPlugBridgeController::class, 'consultar'])->name('notas.plug.consultar');
    Route::get ('notas/{nota}/plugnotas/pdf',       [NotaFiscalPlugBridgeController::class, 'pdf'])->name('notas.plug.pdf');
    Route::get ('notas/{nota}/plugnotas/xml',       [NotaFiscalPlugBridgeController::class, 'xml'])->name('notas.plug.xml');
});

Route::post('/notas/{nota}/emitir',   [NotaFiscalPlugNotasController::class, 'emitir'])->name('notas.emitir');
Route::get('/notas/{nota}/consultar',[NotaFiscalPlugNotasController::class, 'consultar'])->name('notas.consultar');
Route::get('/notas/{nota}/pdf',      [NotaFiscalPlugNotasController::class, 'pdf'])->name('notas.pdf');
Route::get('/notas/{nota}/xml',      [NotaFiscalPlugNotasController::class, 'xml'])->name('notas.xml');
Route::post('/webhooks/plugnotas', [\App\Http\Controllers\WebhookPlugNotasController::class, 'handle']);



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
