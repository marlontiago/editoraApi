<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Produto;
use App\Models\Colecao;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Services\ProdutoService;

class ProdutoController extends Controller
{
    /** Helper: normaliza números pt-BR "1.234,56" -> "1234.56" */
    private function toFloat($v)
    {
        if ($v === null) return null;
        $v = trim((string) $v);
        if ($v === '') return null; 

        // se já for um número (ex: 12.34) retorna direto (evita remoção indevida)
        if (is_numeric($v)) {
            return $v;
        }

        // Formato pt-BR: contém vírgula -> remover pontos de milhar e trocar vírgula por ponto
        if (strpos($v, ',') !== false) {
            $v = str_replace(['.', ' '], '', $v); 
            $v = str_replace(',', '.', $v);       
            return $v;
        }

        // Caso genérico: remover espaços e tentar trocar vírgula só por segurança
        $v = str_replace(' ', '', $v);
        $v = str_replace(',', '.', $v);

        return $v;
    }

    /** Helper: aplica normalizações no request */
    private function normalize(Request $request): void
    {
        $request->merge([
            //'isbn'  => preg_replace('/\D/', '', (string) $request->input('isbn')),
            'preco' => $this->toFloat($request->input('preco')),
            'peso'  => $this->toFloat($request->input('peso')),
        ]);
    }

    /** Regras de validação compartilhadas */
    private function rules(bool $isUpdate = false): array
    {
        return [
            'codigo' => [
            'required',
            'integer',
            'min:1',
            $isUpdate
                ? Rule::unique('produtos', 'codigo')->ignore(request()->route('produto'))
                : 'unique:produtos,codigo'
        ],
            'titulo' => ['required','string','max:255'],
            'isbn' => [
                'nullable','string','max:17', // cabe "978-65-88702-17-8"
                function ($attribute, $value, $fail) {
                    if ($value === null || $value === '') return;
                    $digits = preg_replace('/\D/', '', $value);
                    if (strlen($digits) !== 13) {
                        $fail('O ISBN deve conter exatamente 13 dígitos (com ou sem traços).');
                    }
                    if (!preg_match('/^[0-9-]+$/', $value)) {
                        $fail('Use apenas números e traços no ISBN.');
                    }
                },
            ],
            'autores' => ['nullable','string','max:255'],
            'edicao' => ['nullable','string','max:50'],
            'ano' => ['nullable','integer','min:1900','max:'.date('Y')],
            'numero_paginas' => ['nullable','integer','min:1'],
            'quantidade_por_caixa' => ['nullable','integer','min:0'],
            'peso' => ['nullable','numeric','min:0'],
            'ano_escolar' => [
                                'nullable',
                                'string',
                                'max:255',
                                Rule::in(config('ano_escolar.opcoes')), 
                            ],
            'colecao_id' => ['nullable','exists:colecoes,id'],
            'descricao' => ['nullable','string'],
            'preco' => ['nullable','numeric','min:0'],
            'quantidade_estoque' => ['nullable','integer','min:0'],
            'imagem' => ['nullable','image','mimes:png,jpg,jpeg,webp','max:2048'],
        ];
    }


    public function index(Request $request, ProdutoService $service)
{
    $produtosComEstoqueBaixo = $service->estoqueBaixo();
    $estoqueParaPedidosEmPotencial = $service->estoqueParaPedidosEmPotencial();

    $q = trim((string) $request->get('q',''));

    $query = $service->indexQuery($request);

    $sort = $request->get('sort', 'titulo');
    $dir  = strtolower($request->get('dir', 'asc')) === 'desc' ? 'desc' : 'asc';

    $perPage = (int) $request->get('per_page', 15);
    $perPage = ($perPage > 0 && $perPage <= 100) ? $perPage : 15;

    $produtos = $query->paginate($perPage)->withQueryString();

    $produtosParaColecao = Produto::orderBy('titulo', 'asc')
        ->get(['id', 'titulo', 'isbn', 'autores']);

    $colecoesResumo = Colecao::withCount('produtos')
        ->orderBy('nome')
        ->get(['id','codigo','nome']);

    return view('admin.produtos.index', compact(
        'produtos', 'q', 'sort', 'dir',
        'produtosComEstoqueBaixo',
        'estoqueParaPedidosEmPotencial',
        'produtosParaColecao',
        'colecoesResumo'
    ));
}

    public function create()
    {
        $colecoes = Colecao::orderBy('nome')->get();
        return view('admin.produtos.create', compact('colecoes'));
    }

    public function store(Request $request, ProdutoService $service)
{
    $service->store($request);

    return redirect()->route('admin.produtos.index')
        ->with('success', 'Produto criado com sucesso.');
}

    public function edit(Produto $produto)
    {
        $colecoes = Colecao::orderBy('nome')->get();
        return view('admin.produtos.edit', compact('produto', 'colecoes'));
    }

    public function update(Request $request, Produto $produto, ProdutoService $service)
{
    $service->update($request, $produto);

    return redirect()->route('admin.produtos.index')
        ->with('success', 'Produto atualizado com sucesso.');
}


    public function destroy(Produto $produto, ProdutoService $service)
{
    $service->destroy($produto);

    return redirect()->route('admin.produtos.index')
        ->with('success', 'Produto removido com sucesso.');
}


    public function show(Request $request, Produto $produto)
    {
        if ($request->wantsJson()) {
            return response()->json($produto);
        }

        return view('admin.produtos.show', compact('produto'));
    }

    public function import(Request $request, ProdutoService $service)
{
    $result = $service->import($request);

    return back()->with($result['ok'] ? 'success' : 'error', $result['message']);
}


}
