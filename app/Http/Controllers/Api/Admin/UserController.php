<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(private UserService $userService)
    {
        $this->middleware(['auth:sanctum']);
    }

    public function index(Request $request)
    {
        $usuarios = User::with('roles')->get();

        return UserResource::collection($usuarios);
    }

    public function store(Request $request)
    {
        $user = $this->userService->createUser($request->all());

        return new UserResource($user);
    }

    public function update(Request $request, User $usuario)
    {
        $user = $this->userService->updateUser($usuario, $request->all());

        return new UserResource($user);
    }

    public function show(User $usuario)
    {
        return new UserResource($usuario);
    }

    public function destroy(User $usuario)
    {
        $this->userService->deleteUser($usuario);

        return response()->json(['message' => 'Usuário removido com sucesso!']);
    }
}
