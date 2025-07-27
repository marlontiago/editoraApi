<?php

namespace App\Http\Controllers\Gestor;

use App\Http\Controllers\Controller;
use App\Models\Distribuidor;
use Illuminate\Http\Request;

class DistribuidorController extends Controller
{
    
    public function index()
    {
        $distribuidores = Distribuidor::where('gestor_id', auth()->user()->gestor->id)->with('user')->latest()->paginate(10);
        return view('gestor.distribuidores.index', compact('distribuidores'));
    }


    public function create()
    {
        return view('gestor.distribuidores.create');
    }

    
    public function store(Request $request)
    {
        $request->validate([
            'nome_completo' => 'required|string|max:255',
            'telefone'      => 'nullable|string|max:20',
            'email'         => 'required|email|unique:users,email',
            'senha'         => 'required|min:6',
        ]);

        $user = \App\Models\User::create([
            'name' => $request->nome_completo,
            'email' => $request->email,
            'password' => bcrypt($request->senha),
        ]);
        $user->assignRole('distribuidor');

        Distribuidor::create([
            'user_id' => $user->id,
            'gestor_id' => auth()->user()->gestor->id,
            'nome_completo' => $request->nome_completo,
            'telefone' => $request->telefone,
        ]);
        
        return to_route('gestor.distribuidores.index')->with('success', 'Distribuidor criado com sucesso!');
    }

    
    public function show(string $id)
    {
        //
    }

    
    public function edit(Distribuidor $distribuidor)
    {
        return view('gestor.distribuidores.edit', compact('distribuidor'));
    }

    
    public function update(Request $request, Distribuidor $distribuidor)
    {
        $request->validate([
            'nome_completo' => 'required|string|max:255',
            'telefone' => 'nullable|string|max:20',
        ]);

        $distribuidor->update([
            'nome_completo' => $request->nome_completo,
            'telefone' => $request->telefone,
        ]);

        $distribuidor->user->update([
            'name' => $request->nome_completo,
        ]);

        return to_route('gestor.distribuidores.index')->with('success', 'Distribuidor atualizado com sucesso!');
    }

    
    public function destroy(Distribuidor $distribuidor)
    {
        $distribuidor->user->delete();
        $distribuidor->delete();

        return to_route('gestor.distribuidores.index')->with('success', 'Distribuidor excluído com sucesso!');
    }
}
