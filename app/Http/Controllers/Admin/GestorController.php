<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Gestor;
use App\Models\User;
use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class GestorController extends Controller
{
    public function index()
    {
        $gestores = Gestor::with('user')->get();
        return view('admin.gestores.index', compact('gestores'));
    }

    public function create()
    {
        $cities = City::all();
        return view('admin.gestores.create', compact('cities'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nome_completo' => 'required|string|max:255',
            'telefone' => 'required|string|max:20',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'cities' => 'array|nullable',
        ]);

        $user = User::create([
            'name' => $request->nome_completo,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);
        $user->assignRole('gestor');

        $gestor = Gestor::create([
            'user_id' => $user->id,
            'nome_completo' => $request->nome_completo,
            'telefone' => $request->telefone,
        ]);

        if ($request->has('cities')) {
            $gestor->cities()->sync($request->cities);
        }

        return redirect()->route('admin.gestores.index')->with('success', 'Gestor criado com sucesso!');
    }

    public function edit(Gestor $gestor)
    {
        $cities = City::all();
        $gestor->load('user', 'cities');
        return view('admin.gestores.edit', compact('gestor', 'cities'));
    }

    public function update(Request $request, Gestor $gestor)
    {
        $request->validate([
            'nome_completo' => 'required|string|max:255',
            'telefone' => 'required|string|max:20',
            'email' => 'required|email|unique:users,email,' . $gestor->user_id,
            'cities' => 'array|nullable',
        ]);

        $gestor->update([
            'nome_completo' => $request->nome_completo,
            'telefone' => $request->telefone,
        ]);

        $gestor->user->update([
            'name' => $request->nome_completo,
            'email' => $request->email,
        ]);

        if ($request->has('cities')) {
            $gestor->cities()->sync($request->cities);
        }

        return redirect()->route('admin.gestores.index')->with('success', 'Gestor atualizado com sucesso!');
    }

    public function destroy(Gestor $gestor)
    {
        $gestor->cities()->detach();
        $gestor->user->delete(); // remove também o user
        $gestor->delete();

        return redirect()->route('admin.gestores.index')->with('success', 'Gestor removido com sucesso!');
    }
}
