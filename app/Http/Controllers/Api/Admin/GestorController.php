<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use App\Models\Gestor;
use App\Models\User;
use App\Models\Anexo;
use App\Models\Distribuidor;
use App\Models\GestorUf;
use App\Models\City;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class GestorController extends Controller
{
    /** Lista “oficial” de UFs */
    private array $UFs = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'];

    public function index()
    {
        $gestores = Gestor::with('user')->latest()->paginate(20);

        return response()->json([
            'ok' => true,
            'data' => $gestores,
        ]);
    }

    public function show(Gestor $gestor)
    {
        $gestor->load('anexos','ufs','contatos','user');

        return response()->json([
            'ok' => true,
            'data' => $gestor,
        ]);
    }

    public function store(Request $request, \App\Services\GestorService $service)
{
    $gestor = $service->storeFromRequest($request);

    return response()->json([
        'ok' => true,
        'message' => 'Gestor criado com sucesso!',
        'data' => $gestor,
    ], 201);
}


    public function update(Request $request, Gestor $gestor, \App\Services\GestorService $service)
{
    $gestor = $service->updateFromRequest($request, $gestor);

    return response()->json([
        'ok' => true,
        'message' => 'Gestor atualizado com sucesso!',
        'data' => $gestor,
    ]);
}

    public function destroy(Gestor $gestor, \App\Services\GestorService $service)
{
    $service->destroyGestor($gestor);

    return response()->json([
        'ok' => true,
        'message' => 'Gestor removido com sucesso!',
    ]);
}


    public function ufs(Gestor $gestor)
    {
        $ufs = Cache::remember("gestor:{$gestor->id}:ufs", 600, function() use ($gestor) {
            return $gestor->ufs()->pluck('uf')->map(fn($u)=>strtoupper($u))->values()->all();
        });

        return response()->json([
            'ok' => true,
            'data' => $ufs,
        ]);
    }

    public function cidadesPorUfs(Request $request, \App\Services\GestorService $service)
{
    $ufs = explode(',', (string)$request->query('ufs', ''));
    $payload = $service->cidadesPorUfs($ufs);

    return response()->json(['ok' => true, 'data' => $payload]);
}

    private function normalizePhonesAndEmails($telefones, $emails): array
    {
        $tels = is_array($telefones) ? $telefones : [];
        $tels = array_values(array_filter(array_map(fn($t)=>trim((string)$t), $tels), fn($t)=>$t!==''));

        $mails = is_array($emails) ? $emails : [];
        $mails = array_values(array_filter(array_map(fn($e)=>trim((string)$e), $mails), fn($e)=>$e!==''));

        return [$tels, $mails];
    }

    private function syncUfs(Gestor $gestor, array $ufsInput): void
    {
        $novas = collect($ufsInput)
            ->map(fn($u)=>strtoupper(trim((string)$u)))
            ->filter(fn($u)=>in_array($u, $this->UFs, true))
            ->unique()
            ->values();

        $atuais = $gestor->ufs()->get()->pluck('uf','id');

        $manterIds = [];

        foreach ($atuais as $id => $ufAtual) {
            if ($novas->contains($ufAtual)) {
                $manterIds[] = $id;
                $novas = $novas->reject(fn($u) => $u === $ufAtual)->values();
            }
        }

        if (!empty($manterIds)) {
            $gestor->ufs()->whereNotIn('id', $manterIds)->delete();
        } else {
            $gestor->ufs()->delete();
        }

        if ($novas->isNotEmpty()) {
            $gestor->ufs()->createMany($novas->map(fn($u)=>['uf'=>$u])->all());
        }
    }

    protected function syncContatos($dono, array $inputContatos): void
    {
        $existentes = $dono->contatos()->get()->keyBy('id');
        $idsMantidos = [];

        foreach ($inputContatos as $c) {
            $payload = [
                'nome'         => $c['nome'] ?? '',
                'email'        => $c['email'] ?? null,
                'telefone'     => $c['telefone'] ?? null,
                'whatsapp'     => $c['whatsapp'] ?? null,
                'cargo'        => $c['cargo'] ?? null,
                'tipo'         => $c['tipo'] ?? 'outro',
                'preferencial' => !empty($c['preferencial']),
                'observacoes'  => $c['observacoes'] ?? null,
            ];

            if (!empty($c['id']) && $existentes->has($c['id'])) {
                $existentes[$c['id']]->update($payload);
                $idsMantidos[] = (int) $c['id'];
            } else {
                if (trim($payload['nome']) !== '') {
                    $novo = $dono->contatos()->create($payload);
                    $idsMantidos[] = $novo->id;
                }
            }
        }

        if (!empty($idsMantidos)) {
            $dono->contatos()->whereNotIn('id', $idsMantidos)->delete();
        } else {
            $dono->contatos()->delete();
        }
    }

    public function destroyAnexo(Gestor $gestor, Anexo $anexo, \App\Services\GestorService $service)
    {
        $service->destroyAnexo($gestor, $anexo);

        return response()->json([
            'ok' => true,
            'message' => 'Anexo excluído com sucesso.',
        ]);
    }

    public function ativarAnexo(Gestor $gestor, Anexo $anexo, \App\Services\GestorService $service)
    {
        $service->ativarAnexo($gestor, $anexo);

        return response()->json([
            'ok' => true,
            'message' => 'Contrato/aditivo ativado e percentual aplicado.',
        ]);
    }

    public function vincularDistribuidores(Request $request)
    {
        $gestores = Gestor::orderBy('razao_social')->get(['id','razao_social']);

        $busca = trim((string) $request->input('busca'));
        $gestorFiltro = (int) $request->input('gestor', 0);

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

        $distribuidores = $query->paginate(30);

        return response()->json([
            'ok' => true,
            'filters' => [
                'busca' => $busca,
                'gestor' => $gestorFiltro ?: null,
            ],
            'gestores' => $gestores,
            'distribuidores' => $distribuidores,
        ]);
    }
    
    public function storeVinculo(Request $request)
    {
        $vinculos = (array) $request->input('vinculos', []);

        $idsDistribuidores = collect(array_keys($vinculos))
            ->map(fn($id) => (int) $id)
            ->filter()
            ->values()
            ->all();

        if (empty($idsDistribuidores)) {
            return response()->json([
                'ok' => true,
                'message' => 'Nenhuma alteração enviada.',
                'alterados' => 0,
            ]);
        }

        $existem = Distribuidor::whereIn('id', $idsDistribuidores)->count();
        if ($existem !== count($idsDistribuidores)) {
            return response()->json([
                'ok' => false,
                'message' => 'Há distribuidores inválidos.',
            ], 422);
        }

        $idsGestores = collect($vinculos)
            ->map(fn($v) => $v === '' ? null : (int) $v)
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (!empty($idsGestores)) {
            $validos = Gestor::whereIn('id', $idsGestores)->count();
            if ($validos !== count($idsGestores)) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Há gestor inválido.',
                ], 422);
            }
        }

        $alterados = 0;
        $lote = Distribuidor::whereIn('id', $idsDistribuidores)->get(['id','gestor_id']);

        foreach ($lote as $dist) {
            $novoBruto = $vinculos[$dist->id] ?? '';
            $novoId = ($novoBruto === '' ? null : (int) $novoBruto);

            if ($dist->gestor_id !== $novoId) {
                $dist->gestor_id = $novoId;
                $dist->save();
                $alterados++;
            }
        }

        return response()->json([
            'ok' => true,
            'message' => $alterados ? "{$alterados} vínculo(s) atualizado(s)!" : 'Nada para atualizar.',
            'alterados' => $alterados,
        ]);
    }



}
