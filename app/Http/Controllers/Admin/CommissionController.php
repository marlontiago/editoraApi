<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Commission;
use App\Models\User;
use Illuminate\Http\Request;

class CommissionController extends Controller
{
    public function index()
    {
        $commissions = Commission::with('user')->paginate(10);
        return view('admin.comissoes.index', compact('commissions'));
    }

    public function create()
    {
        $usuariosComComissao = Commission::pluck('user_id');
        $usuarios = User::whereNotIn('id', $usuariosComComissao)->get();
        return view('admin.comissoes.create', compact('usuarios'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'percentage' => 'required|numeric|min:0',
            
        ]);

        Commission::create($request->only('user_id', 'percentage'));

        return redirect()->route('admin.comissoes.index')->with('success', 'Comissão cadastrada com sucesso.');
    }

    public function edit(Commission $commission)
    {
        $users = User::all();
        return view('admin.comissoes.edit', compact('commission', 'users'));
    }

    public function update(Request $request, Commission $commission)
    {
        $request->validate([
            'percentage' => 'required|numeric|min:0',
            
        ]);

        $commission->update($request->only('percentage'));

        return redirect()->route('admin.comissoes.index')->with('success', 'Comissão atualizada com sucesso.');
    }

    public function destroy(Commission $commission)
    {
        $commission->delete();
        return redirect()->route('admin.comissoes.index')->with('success', 'Comissão excluída com sucesso.');
    }
}
