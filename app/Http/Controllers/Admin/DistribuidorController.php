<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Distribuidor;
use App\Models\User;
use App\Models\City;
use App\Models\Gestor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class DistribuidorController extends Controller
{
    public function index()
    {
        $distribuidores = Distribuidor::with(['user', 'cities'])->get();
        return view('admin.distribuidores.index', compact('distribuidores'));
    }

    public function create()
    {
        $cities = City::orderBy('name')->get();
        $gestores = Gestor::orderBy('nome_completo')->get();
        return view('admin.distribuidores.create', compact('cities', 'gestores'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'nome_completo' => 'required|string|max:255',
            'telefone' => 'required|string|max:20',
            'gestor_id' => 'required|exists:gestores,id',
            'cities'   => 'required|array|min:1',
            'cities.*' => 'exists:cities,id',
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $user->assignRole('distribuidor');

        $distribuidor = Distribuidor::create([
            'user_id' => $user->id,
            'gestor_id' => $request->gestor_id,
            'nome_completo' => $request->nome_completo,
            'telefone' => $request->telefone,
        ]);

        $distribuidor->cities()->sync($request->cities);

        return redirect()->route('admin.distribuidores.index')->with('success', 'Distribuidor cadastrado com sucesso.');
    }

    public function edit(Distribuidor $distribuidor)
    {
        $cities = City::orderBy('nome')->get();
        $selectedCities = $distribuidor->cities->pluck('id')->toArray();
        return view('admin.distribuidores.edit', compact('distribuidor', 'cities', 'selectedCities'));
    }

    public function update(Request $request, Distribuidor $distribuidor)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => ['required','email', Rule::unique('users','email')->ignore($distribuidor->user_id)],
            'password' => 'nullable|string|min:6|confirmed',
            'cities'   => 'required|array|min:1',
            'cities.*' => 'exists:cities,id',
        ]);

        $user = $distribuidor->user;
        $user->name  = $request->name;
        $user->email = $request->email;

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        $distribuidor->cities()->sync($request->cities);

        return redirect()->route('admin.distribuidores.index')->with('success', 'Distribuidor atualizado com sucesso.');
    }

    public function destroy(Distribuidor $distribuidor)
    {
        $distribuidor->cities()->detach();
        $distribuidor->delete();
        $distribuidor->user->delete();

        return redirect()->route('admin.distribuidores.index')->with('success', 'Distribuidor removido com sucesso.');
    }
}