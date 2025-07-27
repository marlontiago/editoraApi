<?php

use App\Http\Controllers\Admin\GestorController;
use App\Http\Controllers\ProdutoController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Gestor\DistribuidorController;
use App\Http\Controllers\Distribuidor\VendaController;
use App\Http\Controllers\Admin\ComissaoController;

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

// Admin
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [App\Http\Controllers\Admin\AdminDashboardController::class, 'index'])->name('dashboard');
    Route::resource('produtos', ProdutoController::class);
    Route::resource('gestores', GestorController::class)->parameters(['gestores' => 'gestor']);
    Route::resource('comissoes', \App\Http\Controllers\Admin\ComissaoController::class)->parameters(['comissoes' => 'comissao']);
});

// Gestor
Route::middleware(['auth', 'role:gestor'])->prefix('gestor')->name('gestor.')->group(function () {
    Route::get('/', [App\Http\Controllers\Gestor\GestorDashboardController::class, 'index'])->name('dashboard');
    Route::resource('distribuidores', DistribuidorController::class)->parameters(['distribuidores' => 'distribuidor']);
    Route::resource('comissoes', ComissaoController::class)->only(['index', 'edit', 'update']);
});

// Distribuidor
Route::middleware(['auth', 'role:distribuidor'])->prefix('distribuidor')->name('distribuidor.')->group(function () {
    Route::get('/', [App\Http\Controllers\Distribuidor\DistribuidorDashboardController::class, 'index'])->name('dashboard');
    Route::resource('vendas', VendaController::class)->only(['index','create','store']);
});


require __DIR__.'/auth.php';
