<?php

use App\Http\Controllers\Admin\ProdutoController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PlugNotasSetupController;
use App\Http\Controllers\NotaFiscalPlugNotasController as C;
use App\Http\Controllers\WebhookPlugNotasController as W;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Admin\GestorController as ApiAdminGestorController;
use App\Http\Controllers\Api\Admin\DistribuidorController as ApiDistribuidorController;
use App\Http\Controllers\Api\Admin\CidadeController as ApiCidadeController;
use App\Http\Controllers\Api\Admin\DistribuidorAnexoController as ApiDistribuidorAnexoController;

// Autenticação
Route::post('/auth/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
});

// Rotas de Admin
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    // Gestores
    Route::get('gestores', [ApiAdminGestorController::class, 'index']);
    Route::post('gestores', [ApiAdminGestorController::class, 'store']);      
    Route::get('gestores/{gestor}', [ApiAdminGestorController::class, 'show']);
    Route::put('gestores/{gestor}', [ApiAdminGestorController::class, 'update']); 
    Route::delete('gestores/{gestor}', [ApiAdminGestorController::class, 'destroy']);
    Route::get('gestores/vincular', [ApiAdminGestorController::class, 'vincularDistribuidores']);
    Route::post('gestores/vincular', [ApiAdminGestorController::class, 'storeVinculo']);
    Route::delete('gestores/{gestor}/anexos/{anexo}', [ApiAdminGestorController::class, 'destroyAnexo']);
    Route::post('gestores/{gestor}/anexos/{anexo}/ativar', [ApiAdminGestorController::class, 'ativarAnexo']);
    Route::get('gestores/{gestor}/anexos/{anexo}', [ApiAdminGestorController::class, 'showAnexo']);
    Route::put('gestores/{gestor}/anexos/{anexo}', [ApiAdminGestorController::class, 'updateAnexo']);
    // auxiliares 
    Route::get('gestores/{gestor}/ufs', [ApiAdminGestorController::class, 'ufs']);
    Route::get('utils/cidades-por-ufs', [ApiAdminGestorController::class, 'cidadesPorUfs']);
    // Distribuidores CRUD
    Route::apiResource('distribuidores', ApiDistribuidorController::class)->parameters(['distribuidores' => 'distribuidor']);
    Route::get('distribuidores/por-gestor/{gestor}', [ApiDistribuidorController::class, 'porGestor'])->whereNumber('gestor');
    Route::get('cidades/por-distribuidor/{distribuidor}', [ApiCidadeController::class, 'porDistribuidor'])->whereNumber('distribuidor');
    Route::delete('distribuidores/{distribuidor}/anexos/{anexo}', [ApiDistribuidorController::class, 'destroyAnexo'])->whereNumber('distribuidor');
    Route::post('distribuidores/{distribuidor}/anexos/{anexo}/ativar', [ApiDistribuidorController::class, 'ativarAnexo'])->whereNumber('distribuidor');
    // Filtros de cidades
    Route::get('distribuidores/cidades-por-ufs', [ApiDistribuidorController::class, 'cidadesPorUfs'])->name('admin.distribuidores.cidadesPorUfs');
    Route::get('distribuidores/cidades-por-gestor', [ApiDistribuidorController::class, 'cidadesPorGestor'])->name('admin.distribuidores.cidadesPorGestor');
    // Anexos (controller separado)
    Route::get('distribuidores/{distribuidor}/anexos/{anexo}', [ApiDistribuidorAnexoController::class, 'show'])->name('admin.distribuidores.anexos.show');
    Route::put('distribuidores/{distribuidor}/anexos/{anexo}', [ApiDistribuidorAnexoController::class, 'update'])->name('admin.distribuidores.anexos.update');

});


Route::apiResource('produtos', ProdutoController::class);

Route::post('/plugnotas/notas/{nota}/emitir',    [C::class, 'emitir']);
Route::get ('/plugnotas/notas/{nota}/consultar',[C::class, 'consultar']);
Route::get ('/plugnotas/notas/{nota}/pdf',       [C::class, 'pdf']);
Route::get ('/plugnotas/notas/{nota}/xml',       [C::class, 'xml']);
Route::post('/plugnotas/setup/empresa', [PlugNotasSetupController::class, 'empresa']);

// webhook (a PlugNotas chamaria isto)
Route::post('/plugnotas/webhook', [W::class, 'handle']);



