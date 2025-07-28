<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Produto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProdutoController extends Controller
{
    public function index()
    {
        $produtos = Produto::all();
        return view('admin.produtos.index', compact('produtos'));
    }

    public function create()
    {
        return view('admin.produtos.create');
    }

    public function store(Request $request)
    {
        $request->validate([
        'nome' => 'required|string|max:255',
        'descricao' => 'nullable|string',
        'preco' => 'required|numeric',
        'quantidade_estoque' => 'required|integer',
        'imagem' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
    ]);

    $data = $request->only(['nome', 'descricao', 'preco', 'quantidade_estoque']);

    if ($request->hasFile('imagem')) {
        $data['imagem'] = $request->file('imagem')->store('produtos', 'public');
    }

    Produto::create($data);

    return redirect()->route('admin.produtos.index')->with('success', 'Produto criado com sucesso.');
    }

    public function edit(Produto $produto)
    {
        return view('admin.produtos.edit', compact('produto'));
    }

    public function update(Request $request, Produto $produto)
    {
        $request->validate([
        'nome' => 'required|string|max:255',
        'descricao' => 'nullable|string',
        'preco' => 'required|numeric',
        'quantidade_estoque' => 'required|integer',
        'imagem' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
    ]);

    $data = $request->only(['nome', 'descricao', 'preco', 'quantidade_estoque']);

    if ($request->hasFile('imagem')) {
        if ($produto->imagem && Storage::disk('public')->exists($produto->imagem)) {
            Storage::disk('public')->delete($produto->imagem);
        }
        $data['imagem'] = $request->file('imagem')->store('produtos', 'public');
    }

    $produto->update($data);

    return redirect()->route('admin.produtos.index')->with('success', 'Produto atualizado com sucesso.');
    }

    public function destroy(Produto $produto)
    {
        $produto->delete();
        return redirect()->route('admin.produtos.index')->with('success', 'Produto removido com sucesso.');
    }
}
