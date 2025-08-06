<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Gestor;
use App\Models\User;
use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use App\Models\Distribuidor;

class GestorController extends Controller
{
    public function index()
    {
        $gestores = Gestor::with('user', 'distribuidores.user')->paginate(10);
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
            'razao_social' => 'required|string|max:255',
            'cnpj' => 'required|string|max:20',
            'representante_legal' => 'required|string|max:255',
            'cpf' => 'required|string|max:20',
            'rg' => 'required|string|max:20',
            'telefone' => 'required|string|max:20',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'endereco_completo' => 'nullable|string|max:255',
            'percentual_vendas' => 'requi                   red|numeric|min:0|max:100',
            'vencimento_contrato' => 'nullable|date',
            'contrato_assinado' => 'nullable|boolean',
            'contrato' => 'nullable|file|mimes:pdf|max:2048',
            'cities' => 'array|nullable',
        ]);

        $contratoPath = $request->hasFile('contrato')
            ? $request->file('contrato')->store('contratos', 'public')
            : null;

        $user = User::create([
            'name' => $request->razao_social,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);
        $user->assignRole('gestor');

        $gestor = Gestor::create([
            'user_id' => $user->id,
            'razao_social' => $request->razao_social,
            'cnpj' => $request->cnpj,
            'representante_legal' => $request->representante_legal,
            'cpf' => $request->cpf,
            'rg' => $request->rg,
            'telefone' => $request->telefone,
            'email' => $request->email,
            'endereco_completo' => $request->endereco_completo,
            'percentual_vendas' => $request->percentual_vendas,
            'vencimento_contrato' => $request->vencimento_contrato,
            'contrato_assinado' => $request->boolean('contrato_assinado'),
            'contrato' => $contratoPath,
        ]);

        if ($request->has('cities')) {
            $gestor->cities()->sync($request->cities);
        }

        return redirect()->route('admin.gestores.index')->with('success', 'Gestor criado com sucesso!');
    }

    public function edit(Gestor $gestor)
    {
        $cities = City::orderBy('name')->get();
        $gestor->load('user', 'cities');
        return view('admin.gestores.edit', compact('gestor', 'cities'));
    }

    public function update(Request $request, Gestor $gestor)
    {
         $request->validate([
            'razao_social' => 'required|string|max:255',
            'cnpj' => 'required|string|max:20',
            'representante_legal' => 'required|string|max:255',
            'cpf' => 'required|string|max:20',
            'rg' => 'required|string|max:20',
            'telefone' => 'required|string|max:20',
            'email' => 'nullable|email|unique:users,email,' . $gestor->user_id,
            'password' => 'nullable|string|min:6',
            'endereco_completo' => 'nullable|string|max:255',
            'percentual_vendas' => 'required|numeric|min:0|max:100',
            'vencimento_contrato' => 'nullable|date',
            'contrato_assinado' => 'nullable|boolean',
            'contrato' => 'nullable|file|mimes:pdf|max:2048',
            'cities' => 'array|nullable',
        ]);

        if ($request->hasFile('contrato')) {
            $contratoPath = $request->file('contrato')->store('contratos', 'public');
            $gestor->contrato = $contratoPath;
        }

        $gestor->update([
            'razao_social' => $request->razao_social,
            'cnpj' => $request->cnpj,
            'representante_legal' => $request->representante_legal,
            'cpf' => $request->cpf,
            'rg' => $request->rg,
            'telefone' => $request->telefone,
            'email' => $request->email,
            'endereco_completo' => $request->endereco_completo,
            'percentual_vendas' => $request->percentual_vendas,
            'vencimento_contrato' => $request->vencimento_contrato,
            'contrato_assinado' => $request->boolean('contrato_assinado'),
        ]);

        $userData = [
    'name' => $request->razao_social,
];

if ($request->filled('email')) {
    $userData['email'] = $request->email;
}

$gestor->user->update($userData);

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

    public function vincularDistribuidores()
    {
        $gestores = Gestor::with('user')->get();
        $distribuidores = Distribuidor::with('user')->get();

        return view('admin.gestores.vincular', compact('gestores', 'distribuidores'));
    }

    public function storeVinculo(Request $request)
    {
        $request->validate([
            'gestor_id' => 'required|exists:gestores,id',
            'distribuidores' => 'array',
            'distribuidores.*' => 'exists:distribuidores,id',
        ]);

        foreach ($request->distribuidores as $distribuidorId) {
            $distribuidor = Distribuidor::find($distribuidorId);
            $distribuidor->gestor_id = $request->gestor_id;
            $distribuidor->save();
        }

        return redirect()->route('admin.admin.gestores.vincular')->with('success', 'Distribuidores vinculados com sucesso.');
    }
}
