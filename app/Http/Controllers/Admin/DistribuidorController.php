<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDistribuidorRequest;
use App\Http\Requests\Admin\UpdateDistribuidorRequest;
use App\Http\Resources\DistribuidorResource;
use App\Models\Distribuidor;
use App\Models\Gestor;
use App\Services\DistribuidorService;
use Illuminate\Http\Request;

class DistribuidorController extends Controller
{
    public function __construct(private DistribuidorService $service) {}

    public function index(Request $request)
    {
        $distribuidores = Distribuidor::with(['user','cities','gestor.user'])->paginate(10);

        if ($request->wantsJson()) {
            return DistribuidorResource::collection($distribuidores)->additional([
                'meta' => [
                    'current_page' => $distribuidores->currentPage(),
                    'per_page'     => $distribuidores->perPage(),
                    'total'        => $distribuidores->total(),
                    'last_page'    => $distribuidores->lastPage(),
                ],
            ]);
        }

        return view('admin.distribuidores.index', compact('distribuidores'));
    }

    public function create(Request $request)
    {
        $gestores = Gestor::orderBy('razao_social')->get(['id','razao_social','estado_uf']);

        if ($request->wantsJson()) {
            return response()->json(['data' => ['gestores' => $gestores]]);
        }

        return view('admin.distribuidores.create', compact('gestores'));
    }

    public function store(StoreDistribuidorRequest $request)
    {
        $distribuidor = $this->service->criar($request->validated());

        if ($request->wantsJson()) {
            return (new DistribuidorResource($distribuidor))
                ->additional(['message' => 'Distribuidor cadastrado com sucesso.'])
                ->response()->setStatusCode(201);
        }

        return redirect()->route('admin.distribuidores.index')
            ->with('success', 'Distribuidor cadastrado com sucesso.');
    }

    public function edit(Request $request, Distribuidor $distribuidor)
    {
        $distribuidor->load(['user','cities','gestor.user']);
        $gestores = Gestor::orderBy('razao_social')->get(['id','razao_social','estado_uf']);

        if ($request->wantsJson()) {
            return response()->json([
                'data' => [
                    'distribuidor'   => new DistribuidorResource($distribuidor),
                    'gestores'       => $gestores,
                    'selectedCities' => $distribuidor->cities->pluck('id'),
                ]
            ]);
        }

        $selectedCities = $distribuidor->cities->pluck('id')->toArray();
        return view('admin.distribuidores.edit', compact('distribuidor','gestores','selectedCities'));
    }

    public function update(UpdateDistribuidorRequest $request, Distribuidor $distribuidor)
    {
        $distribuidor = $this->service->atualizar($distribuidor, $request->validated());

        if ($request->wantsJson()) {
            return (new DistribuidorResource($distribuidor))
                ->additional(['message' => 'Distribuidor atualizado com sucesso.'])
                ->response()->setStatusCode(200);
        }

        return redirect()->route('admin.distribuidores.index')
            ->with('success', 'Distribuidor atualizado com sucesso.');
    }

    public function destroy(Request $request, Distribuidor $distribuidor)
    {
        $this->service->excluir($distribuidor);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Distribuidor removido com sucesso.'], 200);
        }

        return redirect()->route('admin.distribuidores.index')
            ->with('success', 'Distribuidor removido com sucesso.');
    }

    /**
     * Endpoint para popular <select> com distribuidores por gestor (sempre JSON).
     */
    public function porGestor(\App\Models\Gestor $gestor)
    {
        return response()->json($this->service->opcoesPorGestor($gestor));
    }
}
