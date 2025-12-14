<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Advogado;
use App\Services\AdvogadoService;
use Illuminate\Http\Request;

class AdvogadoController extends Controller
{
    public function __construct(private AdvogadoService $service)
    {
        $this->middleware(['auth']);
    }

    public function index(Request $request)
    {
        $advogados = $this->service->paginateIndex(10);

        return view('admin.advogados.index', compact('advogados'));
    }

    public function create()
    {
        return view('admin.advogados.create');
    }

    public function store(Request $request)
    {
        $dados = $this->service->validate($request);

        $advogado = $this->service->create($dados);

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
        $dados = $this->service->validate($request);

        $advogado = $this->service->update($advogado, $dados);

        return redirect()
            ->route('admin.advogados.show', $advogado)
            ->with('success', 'Advogado atualizado com sucesso.');
    }

    public function destroy(Advogado $advogado)
    {
        $this->service->delete($advogado);

        return redirect()
            ->route('admin.advogados.index')
            ->with('success', 'Advogado excluído.');
    }
}
