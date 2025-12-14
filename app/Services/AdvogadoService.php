<?php

namespace App\Services;

use App\Models\Advogado;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdvogadoService
{
    public function paginateIndex(int $perPage = 10)
    {
        return Advogado::paginate($perPage);
    }

    public function rules(): array
    {
        return [
            'nome'              => ['required','string','max:255'],
            'email'             => ['required','email','max:255'],
            'telefone'          => ['nullable','string','max:50'],
            'percentual_vendas' => ['nullable','numeric','between:0,100'],
            'oab'               => ['required','string','max:50'],
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

    public function create(array $dados): Advogado
    {
        $advogado = null;

        DB::transaction(function () use (&$advogado, $dados) {
            $user = User::create([
                'name'     => $dados['nome'],
                'email'    => $dados['email'],
                'password' => bcrypt(Str::random(12)),
            ]);

            $dados['user_id'] = $user->id;

            $advogado = Advogado::create($dados);
        });

        return $advogado;
    }

    public function update(Advogado $advogado, array $dados): Advogado
    {
        DB::transaction(function () use ($advogado, $dados) {
            if ($advogado->user) {
                $advogado->user->update([
                    'name'  => $dados['nome'],
                    'email' => $dados['email'],
                ]);
            }

            $advogado->update($dados);
        });

        return $advogado->refresh();
    }

    public function delete(Advogado $advogado): void
    {
        // mantém seu comportamento atual: só deleta o Advogado (não deleta o User)
        $advogado->delete();
    }
}
