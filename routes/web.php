<?php

use App\Http\Controllers\Admin\GestorController;
use App\Http\Controllers\ProdutoController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Gestor\DistribuidorController;
use App\Http\Controllers\Admin\ComissaoController;

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\UserCommissionController;
use App\Http\Controllers\Admin\GestorController as AdminGestorController;

use App\Http\Controllers\Gestor\GestorDashboardController;
use App\Http\Controllers\Gestor\DistribuidorController as GestorDistribuidorController;
use App\Http\Controllers\Gestor\GestorComissaoController;

use App\Http\Controllers\Distribuidor\DistribuidorDashboardController;
use App\Http\Controllers\Distribuidor\VendaController;


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
    
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');
    Route::resource('produtos', \App\Http\Controllers\Admin\ProdutoController::class);
    Route::resource('gestores', \App\Http\Controllers\Admin\GestorController::class)->parameters(['gestores' => 'gestor']);
    Route::resource('comissoes', \App\Http\Controllers\Admin\ComissaoController::class)->parameters(['comissoes' => 'comissao'])->except(['show']);
    Route::resource('distribuidores', \App\Http\Controllers\Admin\DistribuidorController::class)->names('admin.distribuidores');
});

Route::middleware(['auth', 'role:gestor'])->prefix('gestor')->name('gestor.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Gestor\DashboardController::class, 'index'])->name('dashboard');
});

Route::middleware(['auth', 'role:distribuidor'])->prefix('distribuidor')->name('distribuidor.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Distribuidor\DashboardController::class, 'index'])->name('dashboard');
});

require __DIR__.'/auth.php';
