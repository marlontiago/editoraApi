<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Comissao;

class ComissaoController extends Controller
{
    
    public function index()
    {
        $comissoes = Comissao::all();
        return view('admin.comissoes.index', compact('comissoes'));
    }

    
    public function edit(Comissao $comissao)
    {
        return view('admin.comissoes.edit', compact('comissao'));
    }

    
    public function update(Request $request, Comissao $comissao)
    {
        $request->validate([
            'percentual' => 'required|numeric|min:0|max:100',
        ]);

        $comissao->update([
            'percentual' => $request->percentual,
        ]);

        return to_route('admin.comissoes.index')->with('success', 'Comissão atualizada com sucesso.');
    }

    
    
}
