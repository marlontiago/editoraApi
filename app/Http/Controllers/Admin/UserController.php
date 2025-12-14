<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\Distribuidor;
use App\Models\Gestor;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use App\Services\UserService;

class UserController extends Controller
{
    public function __construct(private UserService $userService)
    {
        $this->middleware(['auth']);
    }

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
        $user = $this->userService->createUser($request->validated());

        if($request->wantsJson())
        {
            return new UserResource($user);
        }

        return redirect()->route('admin.usuarios.index')->with('success', 'Usuário criado com sucesso.');
    }

    public function update(StoreUserRequest $request, User $usuario)
    {
        $user = $this->userService->updateUser($usuario, $request->validated());

        return redirect()->route('admin.usuarios.index')->with('success', 'Usuário atualizado com sucesso!');
    }

    public function show(User $usuario)
    {
        return new UserResource($usuario->load('roles'));
    }

    public function destroy(User $usuario)
    {
        $this->userService->deleteUser($usuario);

        return redirect()->route('admin.usuarios.index')->with('success', 'Usuário removido com sucesso!');
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
