<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UsuarioPermissaoController extends Controller
{
    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'permissions'   => ['nullable', 'array'],
            'permissions.*' => ['string'],
        ]);

        $permissions = $data['permissions'] ?? [];

        if (auth()->check() && $user->id === auth()->id() && $user->hasRole('admin')) {
            $permissions = array_unique(array_merge($permissions, [
                'pedido.criar',
                'relatorios.acessar',
                'gerenciar.usuarios',
                'estoque.gerenciar',
                'dashboard.acessar',
            ]));
        }

        $user->syncPermissions($permissions);

        return back()->with('success', 'Permissões atualizadas com sucesso.');
    }
}
