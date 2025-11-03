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
    public function index(Request $request)
    {
        $q = trim($request->get('q', ''));
        $colecoes = Colecao::when($q, fn($qq) => $qq->where('nome','ilike',"%{$q}%"))
            ->withCount('produtos')
            ->orderBy('nome')
            ->paginate(15)
            ->withQueryString();

        return view('admin.colecoes.index', compact('colecoes','q'));
    }

    public function create()
    {
        // traga só campos úteis para listar
        $produtos = Produto::orderBy('titulo')->get(['id','titulo','isbn','ano','edicao']);
        return view('admin.colecoes.create', compact('produtos'));
    }

    public function store(Request $request)
    {
        $dados = $request->validate([
            'nome' => ['required','string','max:255', Rule::unique('colecoes','nome')],
            'produtos' => ['array'],         // opcional
            'produtos.*' => ['integer','exists:produtos,id'],
        ]);

        DB::transaction(function() use ($dados) {
            $colecao = Colecao::create(['nome' => $dados['nome']]);

            if (!empty($dados['produtos'])) {
                // Atribui a coleção a todos os produtos selecionados
                Produto::whereIn('id', $dados['produtos'])->update(['colecao_id' => $colecao->id]);
            }
        });

        return redirect()->route('admin.colecoes.index')->with('success','Coleção criada com sucesso!');
    }


    public function edit(Colecao $colecao)
    {
        $produtos = Produto::orderBy('titulo')->get(['id','titulo','isbn','ano','edicao','colecao_id']);
        $selecionados = $colecao->produtos()->pluck('id')->toArray();

        return view('admin.colecoes.edit', compact('colecao','produtos','selecionados'));
    }

    public function update(Request $request, Colecao $colecao)
    {
        $dados = $request->validate([
            'nome' => ['required','string','max:255', Rule::unique('colecoes','nome')->ignore($colecao->id)],
            'produtos' => ['array'],
            'produtos.*' => ['integer','exists:produtos,id'],
        ]);

        DB::transaction(function() use ($dados, $colecao) {
            $colecao->update(['nome' => $dados['nome']]);

            // limpa vínculos antigos desta coleção
            Produto::where('colecao_id', $colecao->id)->update(['colecao_id' => null]);

            // reatribui os selecionados
            $ids = $dados['produtos'] ?? [];
            if ($ids) {
                Produto::whereIn('id', $ids)->update(['colecao_id' => $colecao->id]);
            }
        });

        return redirect()->route('admin.colecoes.index')->with('success','Coleção atualizada com sucesso!');
    }

    public function show(Request $request, \App\Models\Colecao $colecao)
    {
        $q = trim($request->get('q', ''));

        $produtos = Produto::where('colecao_id', $colecao->id)
            ->when($q, function ($qq) use ($q) {
                return $qq->where(function ($w) use ($q) {
                    $w->where('titulo', 'ilike', "%{$q}%")
                    ->orWhere('isbn', 'ilike', "%{$q}%");
                });
            })
            ->orderBy('titulo')
            ->paginate(24)
            ->withQueryString();

        return view('admin.colecoes.show', compact('colecao', 'produtos', 'q'));
    }


    public function destroy(Colecao $colecao)
    {
        DB::transaction(function() use ($colecao) {
            // desassocia os produtos
            Produto::where('colecao_id', $colecao->id)->update(['colecao_id' => null]);
            $colecao->delete();
        });

        return redirect()->route('admin.colecoes.index')->with('success','Coleção removida.');
    }
}