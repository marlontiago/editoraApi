<?php

namespace App\Services;

use App\Models\DiretorComercial;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DiretorComercialService
{
    public function paginateIndex(int $perPage = 10)
    {
        return DiretorComercial::paginate($perPage);
    }

    public function rules(): array
    {
        return [
            'nome'              => ['required','string','max:255'],
            'email'             => ['required','email','max:255'],
            'telefone'          => ['nullable','string','max:50'],
            'percentual_vendas' => ['nullable','numeric','between:0,100'],
            'logradouro'        => ['nullable','string','max:255'],
            'numero'            => ['nullable','string','max:50'],
            'complemento'       => ['nullable','string','max:255'],
            'bairro'            => ['nullable','string','max:255'],
            'cidade'            => ['nullable','string','max:255'],
            'estado'            => ['nullable','string','max:2'],
            'cep'               => ['nullable','string','max:20'],
        ];
    }

    public function validate(Request $request): array
    {
        return $request->validate($this->rules());
    }

    public function create(array $dados): DiretorComercial
    {
        return DB::transaction(function () use ($dados) {
            $user = User::create([
                'name'     => $dados['nome'],
                'email'    => $dados['email'],
                'password' => bcrypt(Str::random(12)),
            ]);

            $dados['user_id'] = $user->id;

            return DiretorComercial::create($dados);
        });
    }

    public function update(DiretorComercial $diretor, array $dados): DiretorComercial
    {
        DB::transaction(function () use ($diretor, $dados) {
            if ($diretor->user) {
                $diretor->user->update([
                    'name'  => $dados['nome'],
                    'email' => $dados['email'],
                ]);
            }

            $diretor->update($dados);
        });

        return $diretor->refresh();
    }

    public function delete(DiretorComercial $diretor): void
    {
        // Mantém o comportamento atual: deleta só o DiretorComercial
        $diretor->delete();
    }
}
