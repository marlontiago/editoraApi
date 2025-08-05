<?php

use App\Http\Controllers\Admin\ProdutoController;
use Illuminate\Support\Facades\Route;

Route::apiResource('produtos', ProdutoController::class);