<?php

namespace App\Http\Controllers\Distribuidor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DistribuidorDashboardController extends Controller
{
    public function index()
    {
        return view('distribuidor.dashboard');
    }
}
