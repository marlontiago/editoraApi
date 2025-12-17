<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Anexo;
use App\Models\Distribuidor;
use App\Models\Gestor;
use App\Services\GestorService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;


class GestorController extends Controller
{
    public function index()
    {
        $gestores = Gestor::with('user')->latest()->paginate(20);
        return view('admin.gestores.index', compact('gestores'));
    }

    public function create(GestorService $service)
    {
        $ufs = $service->UFs;
        $ufOcupadas = $service->ufsOcupadas();

        return view('admin.gestores.create', compact('ufs','ufOcupadas'));
    }

    public function store(Request $request, GestorService $service)
    {
        $service->storeFromRequest($request);

        return redirect()
            ->route('admin.gestores.index')
            ->with('success', 'Gestor criado com sucesso!');
    }

    public function show(Gestor $gestor)
    {
        $gestor->load('anexos','ufs');
        return view('admin.gestores.show', compact('gestor'));
    }

    public function edit(Gestor $gestor, GestorService $service)
    {
        $gestor->load('anexos','contatos','ufs');
        $ufs = $service->UFs;
        $ufOcupadas = $service->ufsOcupadas($gestor->id);

        return view('admin.gestores.edit', compact('gestor','ufs','ufOcupadas'));
    }

    public function update(Request $request, Gestor $gestor, GestorService $service)
    {
        $service->updateFromRequest($request, $gestor);

        return redirect()
            ->route('admin.gestores.index')
            ->with('success', 'Gestor atualizado com sucesso!');
    }

    public function destroy(Gestor $gestor, GestorService $service)
    {
        $service->destroyGestor($gestor);

        return redirect()
            ->route('admin.gestores.index')
            ->with('success', 'Gestor removido com sucesso!');
    }

    public function destroyAnexo(Gestor $gestor, Anexo $anexo, GestorService $service)
    {
        $service->destroyAnexo($gestor, $anexo);
        return back()->with('success', 'Anexo excluído com sucesso.');
    }

    public function ativarAnexo(Gestor $gestor, Anexo $anexo, GestorService $service)
    {
        $service->ativarAnexo($gestor, $anexo);
        return back()->with('success', 'Contrato/aditivo ativado e percentual aplicado.');
    }

    // --- sua tela de vincular distribuidores (mantida) ---
    public function vincularDistribuidores(Request $request)
    {
        $gestores = Gestor::orderBy('razao_social')->get(['id','razao_social']);

        $busca = trim((string) $request->input('busca'));
        $gestorFiltro = $request->integer('gestor');

        $query = Distribuidor::query()
            ->with(['gestor:id,razao_social'])
            ->orderBy('razao_social');

        if ($busca !== '') {
            $driver = DB::connection()->getDriverName();
            if ($driver === 'pgsql') {
                $query->where(function($q) use ($busca) {
                    $q->where('razao_social', 'ILIKE', "%{$busca}%")
                      ->orWhere('cnpj', 'ILIKE', "%{$busca}%")
                      ->orWhere('representante_legal', 'ILIKE', "%{$busca}%");
                });
            } else {
                $needle = '%'.mb_strtolower($busca).'%';
                $query->where(function($q) use ($needle) {
                    $q->whereRaw('LOWER(razao_social) like ?', [$needle])
                      ->orWhereRaw('LOWER(cnpj) like ?', [$needle])
                      ->orWhereRaw('LOWER(representante_legal) like ?', [$needle]);
                });
            }
        }

        if ($gestorFiltro) {
            $query->where('gestor_id', $gestorFiltro);
        }

        $distribuidores = $query->paginate(30)->withQueryString();

        return view('admin.gestores.vincular', compact('gestores','distribuidores','busca','gestorFiltro'));
    }

    public function storeVinculo(Request $request, GestorService $service)
    {
        $alterados = $service->storeVinculo((array) $request->input('vinculos', []));

        return back()->with(
            $alterados ? 'success' : 'info',
            $alterados ? "{$alterados} vínculo(s) atualizado(s)!" : 'Nada para atualizar.'
        );
    }

    public function ufs(Gestor $gestor)
    {
        $ufs = Cache::remember("gestor:{$gestor->id}:ufs", 600, function() use ($gestor) {
            return $gestor->ufs()->pluck('uf')->map(fn($u)=>strtoupper($u))->values()->all();
        });

        return response()->json($ufs);
    }

    public function cidadesPorUfs(Request $request, GestorService $service)
    {
        $ufs = explode(',', (string)$request->query('ufs', ''));
        $payload = $service->cidadesPorUfs($ufs);

        return response()->json($payload->values());
    }


    public function importar(Request $request, GestorService $service)
    {
        $request->validate([
            'arquivo' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:10240'],
            'atualizar_existentes' => ['nullable', 'boolean'],
        ]);

        /** @var UploadedFile $file */
        $file = $request->file('arquivo');

        $atualizar = (bool) $request->boolean('atualizar_existentes', true);

        $resumo = $service->importarGestoresDaPlanilha($file, $atualizar);

        $msg = "Importação concluída: {$resumo['criados']} criado(s), {$resumo['atualizados']} atualizado(s), {$resumo['pulados']} pulado(s).";
        if (!empty($resumo['erros'])) {
            $msg .= " (Com ".count($resumo['erros'])." erro(s) — veja detalhes abaixo.)";
        }

        return back()
            ->with('success', $msg)
            ->with('import_erros', $resumo['erros'] ?? []);
}

}
