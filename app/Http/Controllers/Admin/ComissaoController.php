<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comissao;
use App\Models\Distribuidor;
use App\Models\Gestor;
use App\Models\City;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ComissaoController extends Controller
{
    public function index()
    {
        $comissoes = Comissao::with('user')->paginate(10);
        return view('admin.comissoes.index', compact('comissoes'));
    }

    public function create()
    {
        $usuarios = User::all();
        return view('admin.comissoes.create', compact('usuarios'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'percentage' => 'required|numeric|min:0',
            'valid_from' => 'required|date',
        ]);

        Comissao::create($request->only('user_id', 'percentage', 'valid_from'));

        return redirect()->route('admin.comissoes.index')->with('success', 'Comissão cadastrada com sucesso.');
    }

    public function edit(Comissao $comissao)
    {
        $users = User::all();
        return view('admin.comissoes.edit', compact('comissao', 'users'));
    }

    public function update(Request $request, Comissao $comissao)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'percentage' => 'required|numeric|min:0',
            'valid_from' => 'required|date',
        ]);

        $comissao->update($request->only('user_id', 'percentage', 'valid_from'));

        return redirect()->route('admin.comissoes.index')->with('success', 'Comissão atualizada com sucesso.');
    }

    public function destroy(Comissao $comissao)
    {
        $comissao->delete();

        return redirect()->route('admin.comissoes.index')->with('success', 'Comissão removida com sucesso.');
    }
}
