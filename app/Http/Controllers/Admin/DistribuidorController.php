<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Distribuidor;
use App\Models\Gestor;
use App\Models\User;
use App\Models\Anexo;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema; 
use App\Services\DistribuidorService;

class DistribuidorController extends Controller
{
   public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $gestorId = (int) $request->query('gestor_id', 0);

        $query = Distribuidor::query()
            ->with(['user','gestor'])
            ->latest();

        if ($q !== '') {
            $qDigits = preg_replace('/\D+/', '', $q);

            $query->where(function ($w) use ($q, $qDigits) {
                $w->where('razao_social', 'ilike', "%{$q}%")
                ->orWhere('cnpj', 'ilike', "%{$q}%")
                ->orWhereHas('user', fn($u) => $u->where('email', 'like', "%{$q}%"));

                if ($qDigits !== '') {
                    $w->orWhereRaw(
                        "REPLACE(REPLACE(REPLACE(REPLACE(cnpj,'.',''),'-',''),'/',''),' ','') like ?",
                        ["%{$qDigits}%"]
                    );
                }
            });
        }

        if ($gestorId > 0) {
            $query->where('gestor_id', $gestorId);
        }

        $distribuidores = $query->paginate(20)->appends($request->query());

        $gestores = Gestor::orderBy('razao_social')->get(['id','razao_social']);

        return view('admin.distribuidores.index', compact('distribuidores', 'gestores', 'q', 'gestorId'));
    }


    public function create()
    {
        $gestores = Gestor::orderBy('razao_social')->get(['id','razao_social']);
        return view('admin.distribuidores.create', compact('gestores'));
    }

    public function store(Request $request, DistribuidorService $service)
    {
        $service->createFromRequest($request);

        return redirect()
            ->route('admin.distribuidores.index')
            ->with('success', 'Distribuidor criado com sucesso!');
    }


    public function show(Distribuidor $distribuidor)
    {
        $distribuidor->load([
            'user',
            'gestor',
            'cities',
            'anexos.cidade',
        ]);

        return view('admin.distribuidores.show', compact('distribuidor'));
    }

    public function edit(Distribuidor $distribuidor)
    {
        $distribuidor->load(['user','gestor','cities','anexos']);
        $gestores = Gestor::orderBy('razao_social')->get(['id','razao_social']);
        return view('admin.distribuidores.edit', compact('distribuidor','gestores'));
    }

    public function update(Request $request, Distribuidor $distribuidor, DistribuidorService $service)
    {
        $service->updateFromRequest($request, $distribuidor);

        return redirect()
            ->route('admin.distribuidores.index')
            ->with('success', 'Distribuidor atualizado com sucesso!');
    }

   public function destroy(Distribuidor $distribuidor, DistribuidorService $service)
    {
        $service->delete($distribuidor);

        return redirect()
            ->route('admin.distribuidores.index')
            ->with('success', 'Distribuidor removido com sucesso!');
    }

    public function destroyAnexo(Distribuidor $distribuidor, Anexo $anexo, DistribuidorService $service)
    {
        $service->deleteAnexo($distribuidor, $anexo);

        return back()->with('success', 'Anexo excluído com sucesso.');
    }


    public function ativarAnexo(Distribuidor $distribuidor, Anexo $anexo, DistribuidorService $service)
    {
        $service->ativarAnexo($distribuidor, $anexo);

        return back()->with('success', 'Contrato/aditivo ativado e percentual/vencimento aplicados.');
    }

    /**
     * Descobre a coluna de UF na tabela cities (uf, state, estado, etc).
     * Retorna null se nenhuma existir.
     */
    private function cityUfColumn(): ?string
    {
        foreach (['uf','state','estado','state_code','uf_code','sigla_uf','uf_sigla'] as $col) {
            if (Schema::hasColumn('cities', $col)) {
                return $col;
            }
        }
        return null;
    }

    // Retorna cidades pelas UFs (ex.: ?ufs=PR,SC)
    public function cidadesPorUfs(Request $request)
    {
        $ufs = collect(explode(',', (string)$request->query('ufs', '')))
            ->map(fn($u) => strtoupper(trim($u)))
            ->filter(fn($u) => preg_match('/^[A-Z]{2}$/', $u))
            ->unique()->values();

        if ($ufs->isEmpty()) return response()->json([]);

        $ufCol = $this->cityUfColumn();
        if (!$ufCol) return response()->json([]);

        $cidades = DB::table('cities')
            ->whereIn($ufCol, $ufs->all())
            ->select('id', 'name as nome', $ufCol.' as uf')
            ->orderBy($ufCol)->orderBy('nome')
            ->get();

        return response()->json(
            $cidades->map(fn($c) => ['id'=>$c->id, 'text'=> "{$c->nome} ({$c->uf})", 'uf'=>$c->uf])
        );
    }

        // Retorna cidades das UFs do gestor informado (ex.: ?gestor_id=123)
        public function cidadesPorGestor(Request $request)
        {
            $gestorId = (int) $request->query('gestor_id', 0);
            if (!$gestorId) return response()->json([]);

            $ufsGestor = DB::table('gestor_ufs')->where('gestor_id', $gestorId)->pluck('uf')->map(fn($u)=>strtoupper($u));
            if ($ufsGestor->isEmpty()) return response()->json([]);

            $ufCol = $this->cityUfColumn();
            if (!$ufCol) return response()->json([]);

            $cidades = DB::table('cities')
                ->whereIn($ufCol, $ufsGestor->all())
                ->select('id', 'name as nome', $ufCol.' as uf')
                ->orderBy($ufCol)->orderBy('nome')
                ->get();

            return response()->json(
                $cidades->map(fn($c) => ['id'=>$c->id, 'text'=> "{$c->nome} ({$c->uf})", 'uf'=>$c->uf])
            );
        }

        public function importar(Request $request, \App\Services\DistribuidorService $service)
        {
            $data = $request->validate([
                'arquivo' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:10240'],
                'atualizar_existentes' => ['nullable', 'boolean'],
            ]);

            $resultado = $service->importarDistribuidoresDaPlanilha(
                $request->file('arquivo'),
                (bool) ($request->boolean('atualizar_existentes', true))
            );

            // mensagem
            $msg = "Importação finalizada: {$resultado['criados']} criado(s), {$resultado['atualizados']} atualizado(s), {$resultado['pulados']} pulado(s).";

            // se tiver erros, volta com erros na sessão (pra UI do modal)
            if (!empty($resultado['erros'])) {
                return back()
                    ->with('success', $msg)
                    ->with('import_erros', $resultado['erros']);
            }

            return back()->with('success', $msg);
        }

}
