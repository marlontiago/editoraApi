<?php

namespace App\Services;

use App\Models\Anexo;
use App\Models\Distribuidor;
use App\Models\Gestor;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DistribuidorService
{
    public function index(Request $request)
    {
        $perPage = min(100, max(1, (int) $request->query('per_page', 20)));

        return Distribuidor::with(['user'])
            ->latest()
            ->paginate($perPage);
    }

    public function show(Distribuidor $distribuidor): Distribuidor
    {
        return $distribuidor->load(['user', 'gestor', 'cities', 'anexos.cidade']);
    }

    public function createFromRequest(Request $request): Distribuidor
    {
        [$emailsReq, $telefonesReq] = $this->normalizeLists($request);

        if (!$request->filled('email') && $emailsReq->isNotEmpty()) {
            $request->merge(['email' => $emailsReq->first()]);
        }

        $data = $this->validateStore($request);

        $cityIds = $this->extractCityIds($data);
        $this->validateCitiesAgainstGestorUfs((int)$data['gestor_id'], $cityIds);
        $this->validateCitiesNotOccupied($cityIds, null);

        $temAssinado = $this->deriveContratoAssinadoFromMeta($data);

        $distribuidor = DB::transaction(function () use ($data, $request, $cityIds, $emailsReq, $telefonesReq, $temAssinado) {
            // USER
            $userEmail = trim((string)($data['email'] ?? ''));
            $userPass  = (string)($data['password'] ?? '');

            if ($userEmail === '') $userEmail = 'distribuidor+'.Str::uuid().'@placeholder.local';
            if ($userPass  === '') $userPass  = Str::random(12);

            /** @var \App\Models\User $user */
            $user = User::create([
                'name'     => $data['razao_social'],
                'email'    => $userEmail,
                'password' => Hash::make($userPass),
            ]);

            if (method_exists($user, 'assignRole')) {
                $user->assignRole('distribuidor');
            }

            // DISTRIBUIDOR
            $distribuidor = Distribuidor::create([
                'user_id'             => $user->id,
                'gestor_id'           => $data['gestor_id'],

                'razao_social'        => $data['razao_social'],
                'cnpj'                => $data['cnpj'] ?? null,
                'representante_legal' => $data['representante_legal'] ?? null,
                'cpf'                 => $data['cpf'] ?? null,
                'rg'                  => $data['rg'] ?? null,

                'emails'              => $emailsReq->isNotEmpty() ? $emailsReq->all() : null,
                'telefones'           => $telefonesReq->isNotEmpty() ? $telefonesReq->all() : null,

                'endereco'            => $data['endereco'] ?? null,
                'numero'              => $data['numero'] ?? null,
                'complemento'         => $data['complemento'] ?? null,
                'bairro'              => $data['bairro'] ?? null,
                'cidade'              => $data['cidade'] ?? null,
                'uf'                  => $data['uf'] ?? null,
                'cep'                 => $data['cep'] ?? null,

                'endereco2'           => $data['endereco2'] ?? null,
                'numero2'             => $data['numero2'] ?? null,
                'complemento2'        => $data['complemento2'] ?? null,
                'bairro2'             => $data['bairro2'] ?? null,
                'cidade2'             => $data['cidade2'] ?? null,
                'uf2'                 => $data['uf2'] ?? null,
                'cep2'                => $data['cep2'] ?? null,

                'percentual_vendas'   => $data['percentual_vendas'] ?? null,
                'vencimento_contrato' => null,
                'contrato_assinado'   => $temAssinado,
            ]);

            // Cities
            if ($cityIds->isNotEmpty()) {
                $distribuidor->cities()->attach($cityIds->all());
            }

            // anexos + aplicar ativo (percentual/vencimento)
            $this->appendContratosFromRequest($request, $distribuidor, $data);

            // contrato_assinado (garante coerência)
            $this->syncContratoAssinadoFromDb($distribuidor);

            // aplica ativo (se existir)
            $this->applyActiveContractToDistribuidor($distribuidor);

            return $distribuidor;
        });

        return $distribuidor->fresh()->load(['user','gestor','cities','anexos.cidade']);
    }

    public function updateFromRequest(Request $request, Distribuidor $distribuidor): Distribuidor
    {
        [$emailsReq, $telefonesReq] = $this->normalizeLists($request);

        if (!$request->filled('email') && $emailsReq->isNotEmpty()) {
            $request->merge(['email' => $emailsReq->first()]);
        }

        $data = $this->validateUpdate($request, $distribuidor);

        $cityIds = $this->extractCityIds($data);
        $this->validateCitiesAgainstGestorUfs((int)$data['gestor_id'], $cityIds);
        $this->validateCitiesNotOccupied($cityIds, $distribuidor->id);

        DB::transaction(function () use ($data, $request, $distribuidor, $cityIds, $emailsReq, $telefonesReq) {
            // USER
            $user = $distribuidor->user;
            if (!empty($data['email'])) $user->email = $data['email'];
            if (!empty($data['password'])) $user->password = Hash::make($data['password']);
            if (!empty($data['email']) || !empty($data['password'])) $user->save();

            // DISTRIBUIDOR
            $distribuidor->update([
                'gestor_id'           => $data['gestor_id'],

                'razao_social'        => $data['razao_social'] ?? $distribuidor->razao_social,
                'cnpj'                => $data['cnpj'] ?? null,
                'representante_legal' => $data['representante_legal'] ?? null,
                'cpf'                 => $data['cpf'] ?? null,
                'rg'                  => $data['rg'] ?? null,

                'emails'              => $emailsReq->isNotEmpty() ? $emailsReq->all() : null,
                'telefones'           => $telefonesReq->isNotEmpty() ? $telefonesReq->all() : null,

                'endereco'            => $data['endereco'] ?? null,
                'numero'              => $data['numero'] ?? null,
                'complemento'         => $data['complemento'] ?? null,
                'bairro'              => $data['bairro'] ?? null,
                'cidade'              => $data['cidade'] ?? null,
                'uf'                  => $data['uf'] ?? null,
                'cep'                 => $data['cep'] ?? null,

                'endereco2'           => $data['endereco2'] ?? null,
                'numero2'             => $data['numero2'] ?? null,
                'complemento2'        => $data['complemento2'] ?? null,
                'bairro2'             => $data['bairro2'] ?? null,
                'cidade2'             => $data['cidade2'] ?? null,
                'uf2'                 => $data['uf2'] ?? null,
                'cep2'                => $data['cep2'] ?? null,

                // aqui eu mantenho sua regra: no update é required
                'percentual_vendas'   => $data['percentual_vendas'],
            ]);

            // Cities: sincroniza
            $distribuidor->cities()->sync($cityIds->all());

            // anexos novos (append)
            $this->appendContratosFromRequest($request, $distribuidor, $data);

            // contrato_assinado
            $this->syncContratoAssinadoFromDb($distribuidor);

            // aplica ativo
            $this->applyActiveContractToDistribuidor($distribuidor);
        });

        return $distribuidor->fresh()->load(['user','gestor','cities','anexos.cidade']);
    }

    public function delete(Distribuidor $distribuidor): void
    {
        DB::transaction(function () use ($distribuidor) {
            $distribuidor->anexos()->delete();
            $distribuidor->cities()->detach();
            $distribuidor->delete();
        });
    }

    public function deleteAnexo(Distribuidor $distribuidor, Anexo $anexo): void
    {
        if ($anexo->anexavel_id !== $distribuidor->id || $anexo->anexavel_type !== Distribuidor::class) {
            abort(403, 'Acesso negado.');
        }

        if ($anexo->arquivo && Storage::disk('public')->exists($anexo->arquivo)) {
            Storage::disk('public')->delete($anexo->arquivo);
        }

        $anexo->delete();

        // mantém coerência
        $this->syncContratoAssinadoFromDb($distribuidor);

        // se apagou o ativo, tenta aplicar o novo ativo (se houver)
        $this->applyActiveContractToDistribuidor($distribuidor);
    }

    public function ativarAnexo(Distribuidor $distribuidor, Anexo $anexo): Distribuidor
    {
        if ($anexo->anexavel_type !== Distribuidor::class || $anexo->anexavel_id !== $distribuidor->id) {
            abort(403, 'Anexo não pertence a este distribuidor.');
        }

        DB::transaction(function () use ($distribuidor, $anexo) {
            $distribuidor->anexos()->where('ativo', true)->update(['ativo' => false]);
            $anexo->update(['ativo' => true]);

            $this->applyActiveContractToDistribuidor($distribuidor);
        });

        return $distribuidor->fresh()->load(['anexos.cidade']);
    }

    public function cidadesPorUfs(Request $request)
    {
        $ufs = collect(explode(',', (string)$request->query('ufs', '')))
            ->map(fn($u) => strtoupper(trim($u)))
            ->filter(fn($u) => preg_match('/^[A-Z]{2}$/', $u))
            ->unique()->values();

        if ($ufs->isEmpty()) return collect([]);

        $ufCol = $this->cityUfColumn();
        if (!$ufCol) return collect([]);

        return DB::table('cities')
            ->whereIn($ufCol, $ufs->all())
            ->select('id', 'name as nome', $ufCol.' as uf')
            ->orderBy($ufCol)->orderBy('nome')
            ->get()
            ->map(fn($c) => ['id'=>$c->id, 'text'=> "{$c->nome} ({$c->uf})", 'uf'=>$c->uf]);
    }

    public function cidadesPorGestor(Request $request)
    {
        $gestorId = (int) $request->query('gestor_id', 0);
        if (!$gestorId) return collect([]);

        $ufsGestor = DB::table('gestor_ufs')
            ->where('gestor_id', $gestorId)
            ->pluck('uf')
            ->map(fn($u)=>strtoupper($u));

        if ($ufsGestor->isEmpty()) return collect([]);

        $ufCol = $this->cityUfColumn();
        if (!$ufCol) return collect([]);

        return DB::table('cities')
            ->whereIn($ufCol, $ufsGestor->all())
            ->select('id', 'name as nome', $ufCol.' as uf')
            ->orderBy($ufCol)->orderBy('nome')
            ->get()
            ->map(fn($c) => ['id'=>$c->id, 'text'=> "{$c->nome} ({$c->uf})", 'uf'=>$c->uf]);
    }

    public function porGestor(Gestor $gestor)
    {
        return Distribuidor::query()
            ->where('gestor_id', $gestor->id)
            ->orderBy('razao_social')
            ->get(['id','razao_social']);
    }

    // =========================
    // Helpers (privados)
    // =========================

    private function normalizeLists(Request $request): array
    {
        $emailsReq = collect($request->input('emails', []))
            ->map(fn($e) => trim((string)$e))
            ->filter(fn($e) => $e !== '')
            ->values();

        $telefonesReq = collect($request->input('telefones', []))
            ->map(fn($t) => preg_replace('/\D+/', '', (string)$t))
            ->filter(fn($t) => $t !== '')
            ->values();

        return [$emailsReq, $telefonesReq];
    }

    private function validateStore(Request $request): array
    {
        return $request->validate([
            'gestor_id'           => ['required','exists:gestores,id'],

            'email'               => ['nullable','email','max:255','unique:users,email'],
            'password'            => ['nullable','string','min:8'],

            'razao_social'        => ['required','string','max:255'],
            'cnpj'                => ['nullable','string','max:18'],
            'representante_legal' => ['nullable','string','max:255'],
            'cpf'                 => ['nullable','string','max:14'],
            'rg'                  => ['nullable','string','max:30'],

            'emails'              => ['nullable','array'],
            'emails.*'            => ['nullable','email','max:255'],
            'telefones'           => ['nullable','array'],
            'telefones.*'         => ['nullable','string','max:30'],

            'endereco'            => ['nullable','string','max:255'],
            'numero'              => ['nullable','string','max:20'],
            'complemento'         => ['nullable','string','max:100'],
            'bairro'              => ['nullable','string','max:100'],
            'cidade'              => ['nullable','string','max:100'],
            'uf'                  => ['nullable','string','size:2'],
            'cep'                 => ['nullable','string','max:9'],

            'endereco2'           => ['nullable','string','max:255'],
            'numero2'             => ['nullable','string','max:20'],
            'complemento2'        => ['nullable','string','max:100'],
            'bairro2'             => ['nullable','string','max:100'],
            'cidade2'             => ['nullable','string','max:100'],
            'uf2'                 => ['nullable','string','size:2'],
            'cep2'                => ['nullable','string','max:9'],

            'uf_cidades'          => ['nullable','string','size:2'],
            'cities'              => ['nullable','array'],
            'cities.*'            => ['integer','exists:cities,id'],

            'percentual_vendas'   => ['nullable','numeric','min:0','max:100'],

            'contratos'                     => ['nullable','array'],
            'contratos.*.tipo'              => ['required_with:contratos.*.arquivo','in:contrato,aditivo,outro,contrato_cidade'],
            'contratos.*.cidade_id'         => [
                'exclude_unless:contratos.*.tipo,contrato_cidade',
                'required_if:contratos.*.tipo,contrato_cidade',
                'integer',
                'exists:cities,id',
            ],
            'contratos.*.arquivo'           => ['nullable','file','mimes:pdf','max:5120'],
            'contratos.*.descricao'         => ['nullable','string','max:255'],
            'contratos.*.assinado'          => ['nullable','boolean'],
            'contratos.*.percentual_vendas' => ['nullable','numeric','min:0','max:100'],
            'contratos.*.ativo'             => ['nullable','boolean'],
            'contratos.*.data_assinatura'   => ['nullable','date'],
            'contratos.*.validade_meses'    => ['nullable','integer','min:1','max:120'],
        ]);
    }

    private function validateUpdate(Request $request, Distribuidor $distribuidor): array
    {
        return $request->validate([
            'gestor_id'           => ['required','exists:gestores,id'],

            'email'               => ['nullable','email','max:255','unique:users,email,'.$distribuidor->user_id],
            'password'            => ['nullable','string','min:8'],

            'razao_social'        => ['nullable','string','max:255'],
            'cnpj'                => ['nullable','string','max:18'],
            'representante_legal' => ['nullable','string','max:255'],
            'cpf'                 => ['nullable','string','max:14'],
            'rg'                  => ['nullable','string','max:30'],

            'emails'              => ['nullable','array'],
            'emails.*'            => ['nullable','email','max:255'],
            'telefones'           => ['nullable','array'],
            'telefones.*'         => ['nullable','string','max:30'],

            'endereco'            => ['nullable','string','max:255'],
            'numero'              => ['nullable','string','max:20'],
            'complemento'         => ['nullable','string','max:100'],
            'bairro'              => ['nullable','string','max:100'],
            'cidade'              => ['nullable','string','max:100'],
            'uf'                  => ['nullable','string','size:2'],
            'cep'                 => ['nullable','string','max:9'],

            'endereco2'           => ['nullable','string','max:255'],
            'numero2'             => ['nullable','string','max:20'],
            'complemento2'        => ['nullable','string','max:100'],
            'bairro2'             => ['nullable','string','max:100'],
            'cidade2'             => ['nullable','string','max:100'],
            'uf2'                 => ['nullable','string','size:2'],
            'cep2'                => ['nullable','string','max:9'],

            'uf_cidades'          => ['nullable','string','size:2'],
            'cities'              => ['nullable','array'],
            'cities.*'            => ['integer','exists:cities,id'],

            'percentual_vendas'   => ['required','numeric','min:0','max:100'],

            'contratos'                     => ['nullable','array'],
            'contratos.*.tipo'              => ['required_with:contratos.*.arquivo','in:contrato,aditivo,outro,contrato_cidade'],
            'contratos.*.cidade_id'         => [
                'exclude_unless:contratos.*.tipo,contrato_cidade',
                'required_if:contratos.*.tipo,contrato_cidade',
                'integer',
                'exists:cities,id',
            ],
            'contratos.*.arquivo'           => ['nullable','file','mimes:pdf','max:5120'],
            'contratos.*.descricao'         => ['nullable','string','max:255'],
            'contratos.*.assinado'          => ['nullable','boolean'],
            'contratos.*.percentual_vendas' => ['nullable','numeric','min:0','max:100'],
            'contratos.*.ativo'             => ['nullable','boolean'],
            'contratos.*.data_assinatura'   => ['nullable','date'],
            'contratos.*.validade_meses'    => ['nullable','integer','min:1','max:120'],
        ]);
    }

    private function extractCityIds(array $data): Collection
    {
        return collect($data['cities'] ?? [])
            ->map(fn($i)=>(int)$i)
            ->unique()
            ->values();
    }

    private function deriveContratoAssinadoFromMeta(array $data): bool
    {
        if (empty($data['contratos']) || !is_array($data['contratos'])) return false;

        foreach ($data['contratos'] as $meta) {
            if (!empty($meta['assinado'])) return true;
        }
        return false;
    }

    private function validateCitiesAgainstGestorUfs(int $gestorId, Collection $cityIds): void
    {
        if ($cityIds->isEmpty()) return;

        $gestorUfs = DB::table('gestor_ufs')
            ->where('gestor_id', $gestorId)
            ->pluck('uf')
            ->map(fn($u)=>strtoupper($u))
            ->all();

        $ufCol = $this->cityUfColumn();
        if (!$ufCol) return; // sem coluna UF = não dá pra validar

        $cidades = DB::table('cities')
            ->whereIn('id', $cityIds->all())
            ->select('id','name', $ufCol.' as uf')
            ->get();

        $fora = $cidades->filter(fn($c) => !in_array(strtoupper((string)$c->uf), $gestorUfs, true));
        if ($fora->isNotEmpty()) {
            $lista = $fora->map(fn($c)=>"{$c->name} (".($c->uf ?? '?').")")->implode(', ');
            throw ValidationException::withMessages([
                'cities' => ["As cidades selecionadas devem estar nas UFs do gestor. Fora do escopo: {$lista}."]
            ]);
        }
    }

    private function validateCitiesNotOccupied(Collection $cityIds, ?int $ignoreDistribuidorId): void
    {
        if ($cityIds->isEmpty()) return;

        $q = DB::table('city_distribuidor')
            ->join('distribuidores','distribuidores.id','=','city_distribuidor.distribuidor_id')
            ->join('cities','cities.id','=','city_distribuidor.city_id')
            ->whereIn('city_distribuidor.city_id', $cityIds->all())
            ->select('cities.id','cities.name','distribuidores.razao_social as distribuidor');

        if ($ignoreDistribuidorId) {
            $q->where('city_distribuidor.distribuidor_id','<>',$ignoreDistribuidorId);
        }

        $ocupadas = $q->get();

        if ($ocupadas->isNotEmpty()) {
            $msgs = $ocupadas->map(fn($o) => "{$o->name} (ocupada por {$o->distribuidor})")->implode(', ');
            throw ValidationException::withMessages([
                'cities' => ["Algumas cidades já estão ocupadas: {$msgs}."]
            ]);
        }
    }

    private function appendContratosFromRequest(Request $request, Distribuidor $distribuidor, array $data): void
    {
        if (empty($data['contratos']) || !is_array($data['contratos'])) return;

        $idAtivoEscolhido = null;

        foreach ($data['contratos'] as $idx => $meta) {
            $file = $request->file("contratos.$idx.arquivo");
            if (!$file) continue;

            $path  = $file->store("distribuidores/{$distribuidor->id}", 'public');
            $ativo = !empty($meta['ativo']);

            $inicio = !empty($meta['data_assinatura']) ? Carbon::parse($meta['data_assinatura']) : null;
            $meses  = !empty($meta['validade_meses']) ? (int)$meta['validade_meses'] : null;
            $dataVenc = ($inicio && $meses) ? (clone $inicio)->addMonthsNoOverflow($meses) : null;

            $anexo = $distribuidor->anexos()->create([
                'tipo'              => $meta['tipo'] ?? 'contrato',
                'cidade_id'         => ($meta['tipo'] ?? null) === 'contrato_cidade'
                    ? (!empty($meta['cidade_id']) ? (int)$meta['cidade_id'] : null)
                    : null,
                'arquivo'           => $path,
                'descricao'         => $meta['descricao'] ?? null,
                'assinado'          => !empty($meta['assinado']),
                'percentual_vendas' => isset($meta['percentual_vendas']) ? (float)$meta['percentual_vendas'] : null,
                'ativo'             => $ativo,
                'data_assinatura'   => $inicio,
                'data_vencimento'   => $dataVenc,
            ]);

            if ($ativo) $idAtivoEscolhido = $anexo->id;
        }

        // garante no máximo 1 ativo
        if ($distribuidor->anexos()->where('ativo', true)->count() > 1) {
            $distribuidor->anexos()
                ->where('ativo', true)
                ->where('id', '<>', $idAtivoEscolhido)
                ->update(['ativo' => false]);
        }
    }

    private function syncContratoAssinadoFromDb(Distribuidor $distribuidor): void
    {
        $temAssinadoAgora = $distribuidor->anexos()->where('assinado', true)->exists();

        if ($distribuidor->contrato_assinado !== $temAssinadoAgora) {
            $distribuidor->update(['contrato_assinado' => $temAssinadoAgora]);
        }
    }

    private function applyActiveContractToDistribuidor(Distribuidor $distribuidor): void
    {
        $ativo = $distribuidor->anexos()->where('ativo', true)->latest('id')->first();
        if (!$ativo) return;

        $payload = [];

        if ($ativo->percentual_vendas !== null) {
            $payload['percentual_vendas'] = $ativo->percentual_vendas;
        }
        if ($ativo->data_vencimento) {
            $payload['vencimento_contrato'] = $ativo->data_vencimento;
        }

        if (!empty($payload)) {
            $distribuidor->update($payload);
        }
    }

    /**
     * Descobre a coluna de UF na tabela cities (uf, state, estado, etc).
     */
    public function cityUfColumn(): ?string
    {
        foreach (['uf','state','estado','state_code','uf_code','sigla_uf','uf_sigla'] as $col) {
            if (Schema::hasColumn('cities', $col)) return $col;
        }
        return null;
    }
}
