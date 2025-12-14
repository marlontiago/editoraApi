<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DiretorComercial;
use App\Services\DiretorComercialService;
use Illuminate\Http\Request;

class DiretorComercialController extends Controller
{
    public function __construct(private DiretorComercialService $service)
    {
        $this->middleware(['auth']);
    }

    public function index(Request $request)
    {
        $diretores = $this->service->paginateIndex(10);

        return view('admin.diretores.index', compact('diretores'));
    }

    public function create()
    {
        return view('admin.diretores.create');
    }

    public function store(Request $request)
    {
        $dados = $this->service->validate($request);

        $diretor = $this->service->create($dados);

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
        $dados = $this->service->validate($request);

        $diretor = $this->service->update($diretor_comercial, $dados);

        return redirect()
            ->route('admin.diretor-comercials.show', $diretor)
            ->with('success', 'Diretor Comercial atualizado com sucesso.');
    }

    public function destroy(DiretorComercial $diretor_comercial)
    {
        $this->service->delete($diretor_comercial);

        return redirect()
            ->route('admin.diretor-comercials.index')
            ->with('success', 'Diretor Comercial excluído.');
    }
}
