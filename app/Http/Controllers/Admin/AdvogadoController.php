<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Advogado;
use Illuminate\Http\Request;


class AdvogadoController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
    }

    public function index(Request $request)
    {
        $advogados = Advogado::paginate(10);

        return view('admin.advogados.index', compact('advogados'));
    }

    public function create()
    {
        return view('admin.advogados.create');
    }

    public function store(Request $request)
    {
        $dados = $request->validate([
            'nome'              => ['required','string','max:255'],
            'email'             => ['required','email','max:255'],
            'telefone'          => ['nullable','string','max:50'],
            'percentual_vendas' => ['nullable','numeric','between:0,100'],
            'oab'               => ['required','string','max:50'],
            'logradouro'        => ['nullable','string','max:255'],
            'numero'            => ['nullable','string','max:50'],
            'complemento'       => ['nullable','string','max:255'],
            'bairro'            => ['nullable','string','max:255'],
            'cidade'            => ['nullable','string','max:255'],
            'estado'            => ['nullable','string','max:2'],
            'cep'               => ['nullable','string','max:20'],
            'uf'                => ['nullable','string','max:2'],
        ]);

        $advogado = Advogado::create($dados);

        return redirect()
            ->route('admin.advogados.show', $advogado)
            ->with('success', 'Advogado criado com sucesso.');
    }

    public function show(Advogado $advogado)
    {
        return view('admin.advogados.show', compact('advogado'));
    }

    public function edit(Advogado $advogado)
    {
        return view('admin.advogados.edit', compact('advogado'));
    }

    public function update(Request $request, Advogado $advogado)
    {
        $dados = $request->validate([
            'nome'              => ['required','string','max:255'],
            'email'             => ['required','email','max:255'],
            'telefone'          => ['nullable','string','max:50'],
            'percentual_vendas' => ['nullable','numeric','between:0,100'],
            'oab'               => ['required','string','max:50'],
            'logradouro'        => ['nullable','string','max:255'],
            'numero'            => ['nullable','string','max:50'],
            'complemento'       => ['nullable','string','max:255'],
            'bairro'            => ['nullable','string','max:255'],
            'cidade'            => ['nullable','string','max:255'],
            'estado'            => ['nullable','string','max:2'],
            'cep'               => ['nullable','string','max:20'],
            'uf'               => ['nullable','string','max:2'],
        ]);

        $advogado->update($dados);

        return redirect()
            ->route('admin.advogados.show', $advogado)
            ->with('success', 'Advogado atualizado com sucesso.');
    }

    public function destroy(Advogado $advogado)
    {
        $advogado->delete();
        return redirect()
            ->route('admin.advogados.index')
            ->with('success', 'Advogado excluído.');
    }
}
