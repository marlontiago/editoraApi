<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required','email'],
            'password' => ['required','string'],
        ]);

        if (!Auth::attempt($data)) {
            return response()->json([
                'ok' => false,
                'message' => 'Credenciais inválidas.',
            ], 401);
        }

        $user = $request->user();

        // IMPORTANTE: token com "ability" opcional
        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'ok' => true,
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    public function me(Request $request)
    {
        return response()->json([
            'ok' => true,
            'user' => $request->user(),
            'roles' => method_exists($request->user(), 'getRoleNames')
                ? $request->user()->getRoleNames()
                : [],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['ok' => true]);
    }
}
