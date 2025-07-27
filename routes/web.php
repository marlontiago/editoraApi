<?php

use App\Http\Controllers\Admin\GestorController;
use App\Http\Controllers\ProdutoController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

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
});

// Gestor
Route::middleware(['auth', 'role:gestor'])->prefix('gestor')->name('gestor.')->group(function () {
    Route::get('/', [App\Http\Controllers\Gestor\GestorDashboardController::class, 'index'])->name('dashboard');
});

// Distribuidor
Route::middleware(['auth', 'role:distribuidor'])->prefix('distribuidor')->name('distribuidor.')->group(function () {
    Route::get('/', [App\Http\Controllers\Distribuidor\DistribuidorDashboardController::class, 'index'])->name('dashboard');
});


require __DIR__.'/auth.php';
