<?php

namespace App\Services;

use App\Models\User;
use App\Models\Distribuidor;
use App\Models\Gestor;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserService
{
    public function createUser($data)
    {
        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $user->assignRole($data['role']);

        if ($data['role'] === 'gestor') {
            Gestor::create([
                'user_id' => $user->id,
                'nome_completo' => $user->name,
                'telefone' => $data['telefone'],
            ]);
        }

        if ($data['role'] === 'distribuidor') {
            Distribuidor::create([
                'user_id' => $user->id,
                'gestor_id' => $data['gestor_id'],
                'nome_completo' => $user->name,
                'telefone' => $data['telefone'],
            ]);
        }

        return $user;
    }

    public function updateUser(User $user, $data)
    {
        $user->update([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => $data['password'] ? Hash::make($data['password']) : $user->password,
        ]);

        // Atualiza o papel
        $user->syncRoles([$data['role']]);

        // Remove registros antigos se mudou de papel
        if ($data['role'] === 'gestor') {
            $user->gestor()->updateOrCreate(
                ['user_id' => $user->id],
                ['nome_completo' => $user->name, 'telefone' => $data['telefone']]
            );
            $user->distribuidor()->delete();
        } elseif ($data['role'] === 'distribuidor') {
            $user->distribuidor()->updateOrCreate(
                ['user_id' => $user->id],
                ['nome_completo' => $user->name, 'telefone' => $data['telefone'], 'gestor_id' => $data['gestor_id']]
            );
            $user->gestor()->delete();
        } else {
            $user->gestor()->delete();
            $user->distribuidor()->delete();
        }

        return $user;
    }

    public function deleteUser(User $user)
    {
        $user->delete();
    }
}
