<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Distribuidor;
use App\Models\Gestor;
use App\Models\City;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function create()
    {
        $roles = Role::all();
        $gestores = Gestor::with('user')->get();
        return view('admin.usuarios.create', compact('roles', 'gestores'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'role'     => 'required|exists:roles,name',
            'telefone' => 'nullable|string|max:20',
            'gestor_id' => 'nullable|exists:gestores,id',
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $user->assignRole($request->role);

        if ($request->role === 'gestor') {
            Gestor::create([
                'user_id' => $user->id,
                'nome_completo' => $user->name,
                'telefone' => $request->telefone,
            ]);
        }

        if ($request->role === 'distribuidor') {
            Distribuidor::create([
                'user_id' => $user->id,
                'gestor_id' => $request->gestor_id,
                'nome_completo' => $user->name,
                'telefone' => $request->telefone,
            ]);
        }

        return redirect()->route('admin.dashboard')->with('success', 'Usuário criado com sucesso!');
    }
}
