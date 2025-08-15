<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProdutoRequest;
use App\Http\Requests\UpdateProdutoRequest;
use App\Http\Resources\ProdutoResource;
use App\Models\Produto;
use App\Models\Colecao;
use App\Services\ProdutoService;
use Illuminate\Http\Request;

class ProdutoController extends Controller
{
    protected $produtoService;

    public function __construct(ProdutoService $produtoService)
    {
        $this->produtoService = $produtoService;
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $query = Produto::query()
            ->with('colecao')
            ->when($q !== '', function ($qb) use ($q) {
                $qb->where(function ($qbuilder) use ($q) {
                    $qbuilder
                        ->where('nome', 'ilike', "%{$q}%")
                        ->orWhere('titulo', 'ilike', "%{$q}%")
                        ->orWhere('autores', 'ilike', "%{$q}%")
                        ->orWhereHas('colecao', fn($cq) => $cq->where('nome', 'ilike', "%{$q}%"));
                });
            });

        // Ordenação opcional (?sort=nome|preco|quantidade_estoque|ano&dir=asc|desc)
        $sort = $request->get('sort', 'nome');
        $dir  = strtolower($request->get('dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $allowedSort = ['nome', 'preco', 'quantidade_estoque', 'ano'];
        if (! in_array($sort, $allowedSort, true)) {
            $sort = 'nome';
        }
        $query->orderBy($sort, $dir);

        // Paginação
        $perPage = (int) $request->get('per_page', 15);
        $perPage = ($perPage > 0 && $perPage <= 100) ? $perPage : 15;

        if ($request->wantsJson()) {
            // Retorna JSON paginado (Resource Collection inclui meta/links)
            $produtos = $query->paginate($perPage);
            return ProdutoResource::collection($produtos);
        }

        // View com paginação e preservando parâmetros na navegação
        $produtos = $query->paginate($perPage)->withQueryString();

        return view('admin.produtos.index', compact('produtos', 'q', 'sort', 'dir'));
    }

    public function create()
    {
        $colecoes = Colecao::orderBy('nome')->get();
        return view('admin.produtos.create', compact('colecoes'));
    }

    public function store(StoreProdutoRequest $request)
    {
        $dados = $request->validated();
        $dados['imagem'] = $request->file('imagem');

        $produto = $this->produtoService->criar($dados);

        if ($request->wantsJson()) {
            return new ProdutoResource($produto);
        }

        return redirect()->route('admin.produtos.index')
            ->with('success', 'Produto criado com sucesso.');
    }

    public function edit(Produto $produto)
    {
        $colecoes = Colecao::orderBy('nome')->get();
        return view('admin.produtos.edit', compact('produto', 'colecoes'));
    }

    public function update(UpdateProdutoRequest $request, Produto $produto)
    {
        $dados = $request->validated();
        $dados['imagem'] = $request->file('imagem');

        $produto = $this->produtoService->atualizar($produto, $dados);

        if ($request->wantsJson()) {
            return new ProdutoResource($produto);
        }

        return redirect()->route('admin.produtos.index')
            ->with('success', 'Produto atualizado com sucesso.');
    }

    public function destroy(Request $request, Produto $produto)
    {
        $this->produtoService->deletar($produto);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Produto removido com sucesso.']);
        }

        return redirect()->route('admin.produtos.index')
            ->with('success', 'Produto removido com sucesso.');
    }

    public function show(Request $request, Produto $produto)
    {
        if ($request->wantsJson()) {
            return new ProdutoResource($produto);
        }

        return view('admin.produtos.show', compact('produto'));
    }
}
