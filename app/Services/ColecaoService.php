<?php

namespace App\Services;

use App\Models\Colecao;
use App\Models\Produto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ColecaoService
{
    public function rulesQuickCreate(): array
    {
        return [
            'codigo'      => ['required','integer', Rule::unique('colecoes', 'codigo')],
            'nome'        => ['required','string','max:255'],
            'produtos'    => ['nullable','array'], 
            'produtos.*'  => ['integer','exists:produtos,id'],
        ];
    }

    public function validateQuickCreate(Request $request): array
    {
        return $request->validate($this->rulesQuickCreate());
    }

    public function quickCreate(array $data): Colecao
    {
        return DB::transaction(function () use ($data) {
            $colecao = Colecao::create([
                'codigo' => $data['codigo'],
                'nome'   => $data['nome'],
            ]);

            if (!empty($data['produtos'])) {
                Produto::whereIn('id', $data['produtos'])
                    ->update(['colecao_id' => $colecao->id]);
            }

            return $colecao;
        });
    }

    public function delete(Colecao $colecao): void
    {
        DB::transaction(function () use ($colecao) {
            Produto::where('colecao_id', $colecao->id)
                ->update(['colecao_id' => null]);

            $colecao->delete();
        });
    }
}
