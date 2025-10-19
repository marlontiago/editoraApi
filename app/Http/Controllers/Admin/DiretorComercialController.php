<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DiretorComercial;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DiretorComercialController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
    }

    public function index(Request $request)
    {
        $diretores = DiretorComercial::paginate(10);
        return view('admin.diretores.index', compact('diretores'));
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

        $diretor = null;

        DB::transaction(function () use (&$diretor, $dados) {
            $user = User::create([
                'name'     => $dados['nome'],
                'email'    => $dados['email'],
                'password' => bcrypt(Str::random(12)),
            ]);

            $dados['user_id'] = $user->id;

            $diretor = DiretorComercial::create($dados);
        });

        return redirect()
            ->route('admin.diretor-comercials.show', $diretor)
            ->with('success', 'Diretor Comercial criado com sucesso.');
    }

    public function show(DiretorComercial $diretor_comercial)
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

        DB::transaction(function () use ($diretor_comercial, $dados) {
            if ($diretor_comercial->user) {
                $diretor_comercial->user->update([
                    'name'  => $dados['nome'],
                    'email' => $dados['email'],
                ]);
            }

            $diretor_comercial->update($dados);
        });

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
