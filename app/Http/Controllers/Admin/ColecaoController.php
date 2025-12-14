<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Colecao;
use App\Services\ColecaoService;
use Illuminate\Http\Request;

class ColecaoController extends Controller
{
    public function __construct(private ColecaoService $service)
    {
        $this->middleware(['auth']);
    }

    public function quickCreate(Request $request)
    {
        $data = $this->service->validateQuickCreate($request);

        $this->service->quickCreate($data);

        return back()->with('success', 'Coleção criada e produtos vinculados com sucesso!');
    }

    public function destroy(Colecao $colecao)
    {
        $this->service->delete($colecao);

        return back()->with('success', 'Coleção excluída. Produtos vinculados foram mantidos sem coleção.');
    }
}
