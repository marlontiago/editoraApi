<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Gestor;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class GestorController extends Controller
{
    public function index()
    {
        $gestores = Gestor::whereHas('user')->latest()->paginate(10);

        foreach ($gestores as $gestor) {
            if (!$gestor->user) {
                Log::warning("Gestor sem usuário vinculado: ID {$gestor->id}");
            }}
        return view('admin.gestores.index', compact('gestores'));
    }

    public function create()
    {
    
        return view('admin.gestores.create', ['ping' => 'pong']);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nome_completo' => 'required|string|max:255',
            'telefone'      => 'nullable|string|max:20',
            'email'         => 'required|email|unique:users,email',
            'senha'         => 'required|min:6',
        ]);

        // 1) Cria o usuário
        $user = User::create([
            'name'     => $request->nome_completo,
            'email'    => $request->email,
            'password' => bcrypt($request->senha),
        ]);
        $user->assignRole('gestor');

        // 2) Cria o gestor vinculado ao user
        Gestor::create([
            'user_id'       => $user->id,
            'nome_completo' => $request->nome_completo,
            'telefone'      => $request->telefone,
        ]);

        return to_route('admin.gestores.index')->with('success', 'Gestor criado com sucesso!');
    }

    public function edit(Gestor $gestor)
    {
        return view('admin.gestores.edit', compact('gestor'));
    }

    public function update(Request $request, Gestor $gestor)
    {
        $request->validate([
            'nome_completo' => 'required|string|max:255',
            'telefone'      => 'nullable|string|max:20',
        ]);

        $gestor->update([
            'nome_completo' => $request->nome_completo,
            'telefone'      => $request->telefone,
        ]);

        $gestor->user->update([
            'name' => $request->nome_completo,
        ]);

        return to_route('admin.gestores.index')->with('success', 'Gestor atualizado com sucesso!');
    }

    public function destroy(Gestor $gestor)
    {
        if ($gestor->user) {
            $gestor->user->delete();
        }

        $gestor->delete();

        return to_route('admin.gestores.index')->with('success', 'Gestor excluído com sucesso!');
    }

}
