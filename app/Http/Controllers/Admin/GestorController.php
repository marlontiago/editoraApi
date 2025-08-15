<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreGestorRequest;
use App\Http\Requests\Admin\UpdateGestorRequest;
use App\Http\Requests\Admin\VincularDistribuidoresRequest;
use App\Http\Resources\CidadesDisponiveisResource;
use App\Models\Gestor;
use App\Models\City;
use App\Models\Distribuidor;
use App\Services\GestorService;
use App\Http\Resources\GestorResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GestorController extends Controller
{
    public function __construct(private GestorService $service)
    {
        
    }
    public function index(Request $request)
    {
        $gestores = Gestor::with(['user', 'distribuidores.user', 'cities'])->paginate(10);

        Log::info($gestores);

        if ($request->wantsJson()) {
            return GestorResource::collection($gestores)
                ->additional([
                    'meta' => [
                        'current_page' => $gestores->currentPage(),
                        'per_page'     => $gestores->perPage(),
                        'total'        => $gestores->total(),
                        'last_page'    => $gestores->lastPage(),
                    ],
                ]);
        }

        return view('admin.gestores.index', compact('gestores'));
    }

    public function create(Request $request)
    {
        if ($request->wantsJson()) {
        return response()->json(['data' => []]);
        }

        return view('admin.gestores.create');
    }

    public function store(StoreGestorRequest $request)
    {
        $gestor = $this->service->create($request->validated());

        if ($request->wantsJson()) {
            return (new GestorResource($gestor))
                ->additional(['message' => 'Gestor criado com sucesso!'])
                ->response()
                ->setStatusCode(201);
        }

        return redirect()
            ->route('admin.gestores.index')
            ->with('success', 'Gestor criado com sucesso!');
    }

    public function edit(Request $request, Gestor $gestor)
    {
        $gestor->load('user');

        if ($request->wantsJson()) {
            return response()->json(['data' => ['gestor' => new GestorResource($gestor)]]);
        }

        return view('admin.gestores.edit', compact('gestor'));
    }

    public function update(UpdateGestorRequest $request, Gestor $gestor)
    {
        $this->service->update($gestor, $request->validated());

        if ($request->wantsJson()) {
            return (new GestorResource($gestor))
                ->additional(['message' => 'Gestor atualizado com sucesso!'])
                ->response()
                ->setStatusCode(200);
        }

        return redirect()
            ->route('admin.gestores.index')
            ->with('success', 'Gestor atualizado com sucesso!');
    }

    public function destroy(Request $request, Gestor $gestor)
    {
        $this->service->delete($gestor);

         if ($request->wantsJson()) {
            return response()->json(['message' => 'Gestor removido com sucesso!'], 200);
        }

        return redirect()
            ->route('admin.gestores.index')
            ->with('success', 'Gestor removido com sucesso!');
    }

    public function vincularDistribuidores(Request $request)
    {
        $gestores = Gestor::with('user')->orderBy('razao_social')->get();
        $distribuidores = Distribuidor::with('user')->orderBy('razao_social')->get();

        if ($request->wantsJson()) {
            return response()->json([
                'data' => [
                    'gestores'      => $gestores->map(fn($g) => [
                        'id' => $g->id,
                        'razao_social' => $g->razao_social,
                        'user' => ['id' => $g->user?->id, 'name' => $g->user?->name, 'email' => $g->user?->email],
                    ]),
                    'distribuidores' => $distribuidores->map(fn($d) => [
                        'id' => $d->id,
                        'razao_social' => $d->razao_social,
                        'user' => ['id' => $d->user?->id, 'name' => $d->user?->name, 'email' => $d->user?->email],
                    ]),
                ]
            ]);
        }

        return view('admin.gestores.vincular', compact('gestores', 'distribuidores'));
    }

    public function storeVinculo(VincularDistribuidoresRequest $request)
    {
        $this->service->vincularDistribuidores(
            gestorId: (int)$request->gestor_id,
            distribuidorIds: $request->validated()['distribuidores'] ?? []
        );

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Distribuidores vinculados com sucesso.'], 200);
        }

        return redirect()
            ->route('admin.gestores.vincular')
            ->with('success', 'Distribuidores vinculados com sucesso.');
    }

    public function cidadesPorGestor(Request $request, Gestor $gestor)
    {
        $data = $this->service->cidadesPorGestor($gestor);

    if ($request->wantsJson()) {
        return CidadesDisponiveisResource::collection($data);
    }

    // View para usar no painel, se quiser
    return view('admin.gestores.cidades', [
        'gestor'  => $gestor,
        'cidades' => $data,
    ]);
    }
}
