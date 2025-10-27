<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Produto;
use App\Models\Colecao;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProdutoController extends Controller
{
    /** Helper: normaliza números pt-BR "1.234,56" -> "1234.56" */
    private function toFloat($v)
    {
        if ($v === null) return null;
        $v = trim((string) $v);
        if ($v === '') return null; // importante

        // se já for um número (ex: 12.34) retorna direto (evita remoção indevida)
        if (is_numeric($v)) {
            return $v;
        }

        // Formato pt-BR: contém vírgula -> remover pontos de milhar e trocar vírgula por ponto
        if (strpos($v, ',') !== false) {
            $v = str_replace(['.', ' '], '', $v); // remove pontos e espaços (milhares)
            $v = str_replace(',', '.', $v);       // vírgula -> ponto
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
            'ano_escolar' => ['nullable','in:Ens Inf,Fund 1,Fund 2,EM'],
            'colecao_id' => ['nullable','exists:colecoes,id'],
            'descricao' => ['nullable','string'],
            'preco' => ['nullable','numeric','min:0'],
            'quantidade_estoque' => ['nullable','integer','min:0'],
            'imagem' => ['nullable','image','mimes:png,jpg,jpeg,webp','max:2048'],
        ];
    }


    public function index(Request $request)
    {
        $produtosComEstoqueBaixo = Produto::where(function ($q) {
                $q->whereNull('quantidade_estoque')
                ->orWhere('quantidade_estoque', '<=', 100);
            })
            // se quiser ordenar mostrando NULL (0) primeiro:
            ->orderByRaw('COALESCE(quantidade_estoque, 0) ASC')
            ->get();

        $estoqueParaPedidosEmPotencial = DB::table('pedido_produto as pp')
        ->join('pedidos as pe', 'pe.id', '=', 'pp.pedido_id')
        ->join('produtos as pr', 'pr.id', '=', 'pp.produto_id')
        ->where('pe.status', 'em_andamento')
        ->groupBy('pp.produto_id', 'pr.titulo', 'pr.quantidade_estoque')
        ->havingRaw('SUM(pp.quantidade) > COALESCE(pr.quantidade_estoque, 0)')
        ->get([
            'pp.produto_id',
            'pr.titulo',
            DB::raw('SUM(pp.quantidade) as qtd_em_pedidos'),
            DB::raw('COALESCE(pr.quantidade_estoque, 0) as quantidade_estoque'),
            DB::raw('SUM(pp.quantidade) - COALESCE(pr.quantidade_estoque, 0) AS excedente'),
        ]);

        $q = trim((string) $request->get('q',''));

        $query = Produto::query()
            ->with('colecao')
            ->when($q !== '', function ($qb) use ($q) {
                $qb->where(function ($qbuilder) use ($q) {
                    $qbuilder
                        ->where('titulo', 'ilike', "%{$q}%")
                        ->orWhere('autores', 'ilike', "%{$q}%")
                        ->orWhereHas('colecao', fn($cq) => $cq->where('nome', 'ilike', "%{$q}%"));
                });
            });

        // Ordenação (?sort=titulo|preco|quantidade_estoque|ano&dir=asc|desc)
        $sort = $request->get('sort', 'titulo');
        $dir  = strtolower($request->get('dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $allowedSort = ['titulo','preco','quantidade_estoque','ano'];
        if (!in_array($sort, $allowedSort, true)) {
            $sort = 'titulo';
        }
        $query->orderBy($sort, $dir);

        // Paginação
        $perPage = (int) $request->get('per_page', 15);
        $perPage = ($perPage > 0 && $perPage <= 100) ? $perPage : 15;

        if ($request->wantsJson()) {
            return $query->paginate($perPage);
        }

        $produtos = $query->paginate($perPage)->withQueryString();

        return view('admin.produtos.index', compact(
            'produtos', 'q', 'sort', 'dir', 'produtosComEstoqueBaixo', 'estoqueParaPedidosEmPotencial'
        ));
    }

    public function create()
    {
        $colecoes = Colecao::orderBy('nome')->get();
        return view('admin.produtos.create', compact('colecoes'));
    }

    public function store(Request $request)
    {
        $this->normalize($request);
        $dados = $request->validate($this->rules());

        // Upload da imagem (se houver)
        if ($request->hasFile('imagem') && $request->file('imagem')->isValid()) {
            $dados['imagem'] = $request->file('imagem')->store('produtos', 'public');
        }

        $produto = Produto::create($dados);

        if ($request->wantsJson()) {
            return response()->json($produto, 201);
        }

        return redirect()->route('admin.produtos.index')
            ->with('success', 'Produto criado com sucesso.');
    }

    public function edit(Produto $produto)
    {
        $colecoes = Colecao::orderBy('nome')->get();
        return view('admin.produtos.edit', compact('produto', 'colecoes'));
    }

    public function update(Request $request, Produto $produto)
    {
        $this->normalize($request);
        $dados = $request->validate($this->rules(isUpdate: true));

        // Manter imagem atual caso não seja enviada
        if ($request->hasFile('imagem') && $request->file('imagem')->isValid()) {
            // apaga a antiga se existir
            if ($produto->imagem && Storage::disk('public')->exists($produto->imagem)) {
                Storage::disk('public')->delete($produto->imagem);
            }
            $dados['imagem'] = $request->file('imagem')->store('produtos', 'public');
        } else {
            unset($dados['imagem']);
        }

        $produto->update($dados);

        if ($request->wantsJson()) {
            return response()->json($produto);
        }

        return redirect()->route('admin.produtos.index')
            ->with('success', 'Produto atualizado com sucesso.');
    }

    public function destroy(Request $request, Produto $produto)
    {
        if ($produto->imagem && Storage::disk('public')->exists($produto->imagem)) {
            Storage::disk('public')->delete($produto->imagem);
        }

        $produto->delete();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Produto removido com sucesso.']);
        }

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
}
