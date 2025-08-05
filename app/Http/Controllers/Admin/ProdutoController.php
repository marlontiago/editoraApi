<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProdutoRequest;
use App\Http\Requests\UpdateProdutoRequest;
use App\Http\Resources\ProdutoResource;
use App\Models\Produto;
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
        $produtos = Produto::all();

        if($request->wantsJson())
        {
            return ProdutoResource::collection($produtos);
        }

        return view('admin.produtos.index', compact('produtos'));
    }

    public function create()
    {
        return view('admin.produtos.create');
    }

    public function store(StoreProdutoRequest $request)
    {
        $dados = $request->validated();
        $dados['imagem'] = $request->file('imagem');

        $produto = $this->produtoService->criar($dados);

        if($request->wantsJson())
        {
            return new ProdutoResource($produto);
        }

        return redirect()->route('admin.produtos.index')
            ->with('success', 'Produto criado com sucesso.');
    }

    public function edit(Produto $produto)
    {
        return view('admin.produtos.edit', compact('produto'));
    }

    public function update(UpdateProdutoRequest $request, Produto $produto)
    {

        $dados = $request->validated();
        $dados['imagem'] = $request->file('imagem');
        $produto = $this->produtoService->atualizar($produto, $dados);

        if($request->wantsJson())
        {
            return new ProdutoResource($produto);
        }

        return redirect()->route('admin.produtos.index')
            ->with('success', 'Produto atualizado com sucesso.');
    }

    public function destroy(Request $request, Produto $produto)
    {
        $this->produtoService->deletar($produto);

        if($request->wantsJson())
        {
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
