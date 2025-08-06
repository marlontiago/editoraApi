<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Distribuidor;
use App\Models\User;
use App\Models\City;
use App\Models\Gestor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

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
        $gestores = Gestor::orderBy('razao_social')->get();
        return view('admin.distribuidores.create', compact('cities', 'gestores'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'gestor_id' => 'required|exists:gestores,id',
            'razao_social' => 'required|string|max:255',
            'cnpj' => 'required|string|max:20',
            'representante_legal' => 'required|string|max:255',
            'cpf' => 'required|string|max:20',
            'rg' => 'required|string|max:20',
            'telefone' => 'nullable|string|max:20',
            'endereco_completo' => 'nullable|string|max:255',
            'percentual_vendas' => 'required|numeric|min:0|max:100',
            'vencimento_contrato' => 'nullable|date',
            'contrato_assinado' => 'boolean',
            'contrato' => 'nullable|file|mimes:pdf|max:2048',
            'cities' => 'required|array|min:1',
            'cities.*' => 'exists:cities,id',
        ]);

        $contratoPath = $request->hasFile('contrato') 
            ? $request->file('contrato')->store('contratos', 'public') 
            : null;

            $cidadesEmUso = DB::table('city_distribuidor')
                ->whereIn('city_id', $request->cities)
                ->pluck('city_id')
                ->toArray();

            if (!empty($cidadesEmUso)) {
                $nomesCidades = \App\Models\City::whereIn('id', $cidadesEmUso)->pluck('name')->toArray();
                return back()->withErrors([
                    'cities' => 'As seguintes cidades já possuem um distribuidor: ' . implode(', ', $nomesCidades)
                ])->withInput();
            }

        $user = User::create([
            'name'     => $request->razao_social,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $user->assignRole('distribuidor');

        $distribuidor = Distribuidor::create([
            'user_id' => $user->id,
            'gestor_id' => $request->gestor_id,
            'razao_social' => $request->razao_social,
            'cnpj' => $request->cnpj,
            'representante_legal' => $request->representante_legal,
            'cpf' => $request->cpf,
            'rg' => $request->rg,
            'telefone' => $request->telefone,
            'endereco_completo' => $request->endereco_completo,
            'percentual_vendas' => $request->percentual_vendas,
            'vencimento_contrato' => $request->vencimento_contrato,
            'contrato_assinado' => $request->has('contrato_assinado'),
            'contrato' => $contratoPath, // 📎 salvando o caminho do PDF
        ]);

        $distribuidor->cities()->sync($request->cities);

        return redirect()->route('admin.distribuidores.index')->with('success', 'Distribuidor cadastrado com sucesso.');
    }

    public function edit(Distribuidor $distribuidor)
    {
        $cities = City::orderBy('name')->get();
        $gestores = Gestor::orderBy('razao_social')->get();
        $selectedCities = $distribuidor->cities->pluck('id')->toArray();
        return view('admin.distribuidores.edit', compact('distribuidor', 'cities', 'selectedCities', 'gestores'));
    }

    public function update(Request $request, Distribuidor $distribuidor)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $distribuidor->user_id . ',id',
            'password' => 'nullable|string|min:6|confirmed',
            'gestor_id' => 'required|exists:gestores,id',
            'razao_social' => 'required|string|max:255',
            'cnpj' => 'required|string|max:20',
            'representante_legal' => 'required|string|max:255',
            'cpf' => 'required|string|max:20',
            'rg' => 'required|string|max:20',
            'telefone' => 'nullable|string|max:20',
            'endereco_completo' => 'nullable|string|max:255',
            'percentual_vendas' => 'required|numeric|min:0|max:100',
            'vencimento_contrato' => 'nullable|date',
            'contrato_assinado' => 'boolean',
            'contrato' => 'nullable|file|mimes:pdf|max:2048',
            'cities' => 'required|array|min:1',
            'cities.*' => 'exists:cities,id',
        ]);
        
        $cidadesEmUso = DB::table('city_distribuidor')
        ->whereIn('city_id', $request->cities)
        ->where('distribuidor_id', '!=', $distribuidor->id)
        ->pluck('city_id')
        ->toArray();

        if (!empty($cidadesEmUso)) {
            $nomesCidades = City::whereIn('id', $cidadesEmUso)->pluck('name')->toArray();
            return back()->withErrors([
                'cities' => 'As seguintes cidades já possuem um distribuidor: ' . implode(', ', $nomesCidades)
            ])->withInput();
        }

        $user = $distribuidor->user;
        $user->name  = $request->name;
        $user->email = $request->email;

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        $distribuidor->update([
            'gestor_id' => $request->gestor_id,
            'razao_social' => $request->razao_social,
            'cnpj' => $request->cnpj,
            'representante_legal' => $request->representante_legal,
            'cpf' => $request->cpf,
            'rg' => $request->rg,
            'telefone' => $request->telefone,
            'endereco_completo' => $request->endereco_completo,
            'percentual_vendas' => $request->percentual_vendas,
            'vencimento_contrato' => $request->vencimento_contrato,
            'contrato_assinado' => $request->has('contrato_assinado'),
        ]);

        if ($request->hasFile('contrato')) {
            $contratoPath = $request->file('contrato')->store('contratos', 'public');
            $distribuidor->contrato = $contratoPath;
            $distribuidor->save();
        }

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