<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\Distribuidor;
use App\Models\Gestor;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;


class UserController extends Controller
{

    public function index(Request $request)
    {

        $usuarios = User::with('roles')->get();

        if($request->wantsJson())
        {
            return UserResource::collection($usuarios);
        }

        return view('admin.usuarios.index', compact('usuarios'));

    }

    public function store(StoreUserRequest $request)
    {
        
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

        if($request->wantsJson())
        {
            return new UserResource($user);
        }

        return redirect()->route('admin.usuarios.index')->with('success', 'Usuário criado com sucesso.');
    }

    public function update(StoreUserRequest $request, User $usuario)
    {
        $usuario->update([
        'name'     => $request->name,
        'email'    => $request->email,
        'password' => $request->filled('password') ? Hash::make($request->password) : $usuario->password,
        ]);

        // Atualiza o papel (remove todos e adiciona o novo)
        $usuario->syncRoles([$request->role]);

        // Remove registros antigos se mudou de papel
        if ($request->role === 'gestor') {
        // Atualiza ou cria dados do gestor
        $usuario->gestor()->updateOrCreate(
            ['user_id' => $usuario->id],
            ['nome_completo' => $usuario->name, 'telefone' => $request->telefone]
        );
        // Remove distribuidor se existir
        $usuario->distribuidor()->delete();
        } elseif ($request->role === 'distribuidor') {
        // Atualiza ou cria dados do distribuidor
        $usuario->distribuidor()->updateOrCreate(
            ['user_id' => $usuario->id],
            ['nome_completo' => $usuario->name, 'telefone' => $request->telefone, 'gestor_id' => $request->gestor_id]
        );
        // Remove gestor se existir
        $usuario->gestor()->delete();
        } else {
        // Se não for gestor nem distribuidor, remove os dois
        $usuario->gestor()->delete();
        $usuario->distribuidor()->delete();
        }

        return redirect()->route('admin.usuarios.index')->with('success', 'Usuário atualizado com sucesso!');
    }

    public function show(User $usuario)
    {
        return new UserResource($usuario->load('roles'));
    }


    public function destroy(User $usuario)
    {
        $usuario->delete();
        return response()->json(['message' => 'Usuário escluído com sucesso.'], 200);
    }

    public function edit(User $usuario)
    {
        $roles = Role::all();
        $gestores = Gestor::with('user')->get();
        return view ('admin.usuarios.edit', compact('gestores', 'roles', 'usuario'));
    }


    public function create()
    {
        $roles = Role::all(); 
        $gestores = Gestor::with('user')->get();
        return view('admin.usuarios.create', compact('roles', 'gestores'));
    }
}
