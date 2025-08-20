<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Commission;
use App\Models\Gestor;
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

        $user = User::findOrFail($request->user_id);

        if($user->hasRole('gestor')){

                $tipo = 'gestor';

            }elseif ($user->hasRole('distribuidor')){

                $tipo = 'distribuidor';

            }else{
                
                return back()->withErrors(['user_id' => 'Este usuário não possui um papel válido para cadastrar comissão']);
            }

        Commission::create([
            'user_id' => $request->user_id,
            'tipo_usuario' => $tipo,
            'percentage' => $request->percentage,
        ]);

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

        $commission->update([
            'percentage' => $request->percentage,
        ]);

        return redirect()->route('admin.comissoes.index')->with('success', 'Comissão atualizada com sucesso.');
    }

    public function destroy(Commission $commission)
    {
        $commission->delete();
        return redirect()->route('admin.comissoes.index')->with('success', 'Comissão excluída com sucesso.');
    }
}
