<?php

use App\Http\Controllers\Admin\ProdutoController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\GestorController;
use App\Http\Controllers\Admin\DistribuidorController;


Route::apiResource('produtos', ProdutoController::class);

Route::prefix('admin')->group(function () {
    // Gestores CRUD
    Route::get('gestores',              [GestorController::class, 'index']);       // lista
    Route::post('gestores',             [GestorController::class, 'store']);       // cria
    Route::get('gestores/{gestor}/edit',[GestorController::class, 'edit']);        // dados para edição + cities
    Route::put('gestores/{gestor}',     [GestorController::class, 'update']);      // atualiza
    Route::delete('gestores/{gestor}',  [GestorController::class, 'destroy']);     // remove

    // Cidades vinculadas à UF do gestor com ocupação
    Route::get('gestores/{gestor}/cidades', [GestorController::class, 'cidadesPorGestor']);

    // Vínculos gestor <-> distribuidores
    Route::get('gestores/vinculos',     [GestorController::class, 'vincularDistribuidores']);
    Route::post('gestores/vinculos',    [GestorController::class, 'storeVinculo']);

    Route::get('distribuidores', [DistribuidorController::class, 'index']);
    Route::post('distribuidores', [DistribuidorController::class, 'store']);
    Route::get('distribuidores/{distribuidor}/edit', [DistribuidorController::class, 'edit']);
    Route::put('distribuidores/{distribuidor}', [DistribuidorController::class, 'update']);
    Route::delete('distribuidores/{distribuidor}', [DistribuidorController::class, 'destroy']);

    // sempre JSON
    Route::get('distribuidores/por-gestor/{gestor}', [DistribuidorController::class, 'porGestor']);
});