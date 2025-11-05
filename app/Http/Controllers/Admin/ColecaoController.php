<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Colecao;
use App\Models\Produto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ColecaoController extends Controller
{
    public function quickCreate(Request $request)
    {
        $data = $request->validate([
            'codigo'   => ['required','string','max:100', Rule::unique('colecoes', 'codigo')],
            'nome'     => ['required','string','max:255'],
            'produtos' => ['array'],          // opcional
            'produtos.*' => ['integer','exists:produtos,id'],
        ]);

        DB::transaction(function() use ($data) {
            $colecao = Colecao::create([
                'codigo' => $data['codigo'],
                'nome'   => $data['nome'],
            ]);

            if (!empty($data['produtos'])) {
                // Define colecao_id para os produtos selecionados
                Produto::whereIn('id', $data['produtos'])
                    ->update(['colecao_id' => $colecao->id]);
            }
        });

        return back()->with('success', 'Coleção criada e produtos vinculados com sucesso!');
    }

    public function destroy(Colecao $colecao)
    {
        DB::transaction(function () use ($colecao) {
            // Desvincula os produtos desta coleção
            Produto::where('colecao_id', $colecao->id)->update(['colecao_id' => null]);

            // Exclui a coleção
            $colecao->delete();
        });

        return back()->with('success', 'Coleção excluída. Produtos vinculados foram mantidos sem coleção.');
    }
}
