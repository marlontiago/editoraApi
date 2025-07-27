<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Produto;

class ProdutoController extends Controller
{
    
    public function index()
    {
        $produtos = Produto::latest()->paginate(10);
        return view('produtos.index', compact('produtos'));
    }

    
    public function create()
    {
        return view('produtos.create');
    }

    
    public function store(Request $request)
    {
        $request->validate([
            'nome' => 'required|string|max:255',
            'descricao' => 'nullable|string',
            'preco' => 'required|numeric',
            'quantidade_estoque' => 'required|integer|min:0',
            'imagem' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $data = $request->all();

        if($request->hasFile('imagem')){
            $path = $request->file('imagem')->store('produtos', 'public');
            $data['imagem'] = $path;
        }

        Produto::create($data);

        return redirect()->route('admin.produtos.index')->with('success', 'Produto criado com sucesso.');
        
    }

    
    public function show(string $id)
    {
    }

    
    public function edit(Produto $produto)
    {

        return view('produtos.edit', compact('produto'));

    }

    
    public function update(Request $request, Produto $produto)
    {
        $request->validate([
            'nome' => 'required|string|max:255',
            'descricao' => 'nullable|string',
            'preco' => 'required|numeric',
            'quantidade_estoque' => 'required|integer|min:0',
        ]);

        $data = $request->all();

        if($request->hasFile('imagem')){
            $path = $request->file('imagem')->store('produtos', 'public');
            $data['imagem'] = $path;
        }

        $produto->update($data);

        return redirect()->route('admin.produtos.index')->with('success', 'Produto atualizado com sucesso.');
    }

    
    public function destroy(Produto $produto)
    {
        $produto->delete();

        return to_route('admin.produtos.index')->with('success', 'Produto excluído com sucesso.');
        
    }
}
