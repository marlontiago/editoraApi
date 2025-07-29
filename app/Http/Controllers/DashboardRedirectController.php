<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class DashboardRedirectController extends Controller
{
    public function redirect()
    {
        $user = Auth::user();

        if ($user->hasRole('admin')) {
            return redirect()->route('admin.dashboard');
        }

        if ($user->hasRole('gestor')) {
            return redirect()->route('gestor.dashboard');
        }

        if ($user->hasRole('distribuidor')) {
            return redirect()->route('distribuidor.dashboard');
        }

        // fallback (se o usuário não tiver papel)
        return abort(403, 'Usuário sem papel válido.');
    }
}
