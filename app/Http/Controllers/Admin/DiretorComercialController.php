<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DiretorComercial;
use Illuminate\Http\Request;

class DiretorComercialController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
        // opcional: $this->middleware(['role:admin']);
    }

    public function index(Request $request)
    {
        $q = trim((string)$request->get('q', ''));
        $diretores = DiretorComercial::query()
            ->when($q, function ($query) use ($q) {
                $query->where(function ($qq) use ($q) {
                    $qq->where('nome', 'like', "%{$q}%")
                       ->orWhere('email', 'like', "%{$q}%")
                       ->orWhere('cidade', 'like', "%{$q}%")
                       ->orWhere('estado', 'like', "%{$q}%");
                });
            })
            ->orderBy('nome')
            ->paginate(15)
            ->withQueryString();

        return view('admin.diretores.index', compact('diretores', 'q'));
    }

    public function create()
    {
        return view('admin.diretores.create');
    }

    public function store(Request $request)
    {
        $dados = $request->validate([
            'nome'              => ['required','string','max:255'],
            'email'             => ['required','email','max:255'],
            'telefone'          => ['nullable','string','max:50'],
            'percentual_vendas' => ['nullable','numeric','between:0,100'],
            'logradouro'        => ['nullable','string','max:255'],
            'numero'            => ['nullable','string','max:50'],
            'complemento'       => ['nullable','string','max:255'],
            'bairro'            => ['nullable','string','max:255'],
            'cidade'            => ['nullable','string','max:255'],
            'estado'            => ['nullable','string','max:2'],
            'cep'               => ['nullable','string','max:20'],
        ]);

        $diretor = DiretorComercial::create($dados);

        return redirect()
            ->route('admin.diretor-comercials.show', $diretor)
            ->with('success', 'Diretor Comercial criado com sucesso.');
    }

    public function show(DiretorComercial $diretor_comercial) // nome do parâmetro segue o inferido pelo Laravel
    {
        return view('admin.diretores.show', ['diretor' => $diretor_comercial]);
    }

    public function edit(DiretorComercial $diretor_comercial)
    {
        return view('admin.diretores.edit', ['diretor' => $diretor_comercial]);
    }

    public function update(Request $request, DiretorComercial $diretor_comercial)
    {
        $dados = $request->validate([
            'nome'              => ['required','string','max:255'],
            'email'             => ['required','email','max:255'],
            'telefone'          => ['nullable','string','max:50'],
            'percentual_vendas' => ['nullable','numeric','between:0,100'],
            'logradouro'        => ['nullable','string','max:255'],
            'numero'            => ['nullable','string','max:50'],
            'complemento'       => ['nullable','string','max:255'],
            'bairro'            => ['nullable','string','max:255'],
            'cidade'            => ['nullable','string','max:255'],
            'estado'            => ['nullable','string','max:2'],
            'cep'               => ['nullable','string','max:20'],
        ]);

        $diretor_comercial->update($dados);

        return redirect()
            ->route('admin.diretor-comercials.show', $diretor_comercial)
            ->with('success', 'Diretor Comercial atualizado com sucesso.');
    }

    public function destroy(DiretorComercial $diretor_comercial)
    {
        $diretor_comercial->delete();
        return redirect()
            ->route('admin.diretor-comercials.index')
            ->with('success', 'Diretor Comercial excluído.');
    }
}
