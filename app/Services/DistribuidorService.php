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
use PhpOffice\PhpSpreadsheet\IOFactory;

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

        $gestorId = !empty($data['gestor_id']) ? (int) $data['gestor_id'] : null;

        $cityIds = $this->extractCityIds($data);

        if (!$gestorId && $cityIds->isNotEmpty()) {
            throw ValidationException::withMessages([
                'cities' => ['Selecione um gestor antes de selecionar cidades.']
            ]);
        }

        if ($gestorId) {
            $this->validateCitiesAgainstGestorUfs($gestorId, $cityIds);
        }

        $this->validateCitiesNotOccupied($cityIds, null);

        $temAssinado = $this->deriveContratoAssinadoFromMeta($data);

        // ✅ Base do cadastro (não perde)
        $basePercent = 0.0;
        if (array_key_exists('percentual_vendas', $data) && $data['percentual_vendas'] !== null && $data['percentual_vendas'] !== '') {
            $basePercent = (float) $data['percentual_vendas'];
        }

        $distribuidor = DB::transaction(function () use ($data, $request, $cityIds, $emailsReq, $telefonesReq, $temAssinado, $gestorId, $basePercent) {

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
                'gestor_id'           => $gestorId,

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

                // ✅ base e vigente
                'percentual_vendas_base' => $basePercent,
                'percentual_vendas'      => $basePercent,

                'vencimento_contrato' => null,
                'contrato_assinado'   => $temAssinado,
            ]);

            if ($cityIds->isNotEmpty()) {
                $distribuidor->cities()->attach($cityIds->all());
            }

            $this->appendContratosFromRequest($request, $distribuidor, $data);

            $this->syncContratoAssinadoFromDb($distribuidor);

            // ✅ aplica ativo vigente (ou volta pro base)
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

        $gestorId = array_key_exists('gestor_id', $data) && $data['gestor_id'] !== null && $data['gestor_id'] !== ''
            ? (int) $data['gestor_id']
            : null;

        $cityIds = $this->extractCityIds($data);

        if (!$gestorId && $cityIds->isNotEmpty()) {
            throw ValidationException::withMessages([
                'cities' => ['Selecione um gestor antes de selecionar cidades.']
            ]);
        }

        if ($gestorId) {
            $this->validateCitiesAgainstGestorUfs($gestorId, $cityIds);
        }

        $this->validateCitiesNotOccupied($cityIds, $distribuidor->id);

        // ✅ atualiza base SOMENTE se veio preenchido
        $basePercent = $distribuidor->percentual_vendas_base ?? 0.0;
        if (array_key_exists('percentual_vendas', $data) && $data['percentual_vendas'] !== null && $data['percentual_vendas'] !== '') {
            $basePercent = (float) $data['percentual_vendas'];
        }

        DB::transaction(function () use ($data, $request, $distribuidor, $cityIds, $emailsReq, $telefonesReq, $gestorId, $basePercent) {

            // USER
            $user = $distribuidor->user;
            if ($user) {
                if (!empty($data['email'])) $user->email = $data['email'];
                if (!empty($data['password'])) $user->password = Hash::make($data['password']);
                if (!empty($data['email']) || !empty($data['password'])) $user->save();
            }

            // DISTRIBUIDOR
            $distribuidor->update([
                'gestor_id'           => $gestorId,

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

                // ✅ base do cadastro
                'percentual_vendas_base' => $basePercent,
            ]);

            $distribuidor->cities()->sync($cityIds->all());

            $this->appendContratosFromRequest($request, $distribuidor, $data);

            $this->syncContratoAssinadoFromDb($distribuidor);

            // ✅ recalcula vigente: aplica contrato ativo vigente; senão volta pro base
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

        $this->syncContratoAssinadoFromDb($distribuidor);

        // ✅ se removeu contrato/ativo/vencido, volta pro base automaticamente
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
            'gestor_id'           => ['nullable','exists:gestores,id'],

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

            // formulário continua mandando "percentual_vendas" como o base
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
            'gestor_id'           => ['nullable','exists:gestores,id'],

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
        if (!$ufCol) return;

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
            $ativo = filter_var($meta['ativo'] ?? false, FILTER_VALIDATE_BOOLEAN);


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

    /**
     * ✅ Regra:
     * - Se existir contrato ATIVO e VIGENTE com percentual -> aplica em percentual_vendas (vigente)
     * - Senão -> volta percentual_vendas para percentual_vendas_base
     * - vencimento_contrato acompanha o contrato ativo vigente (senão null)
     */
    private function applyActiveContractToDistribuidor(Distribuidor $distribuidor): void
    {
        $ativo = $distribuidor->anexos()->where('ativo', true)->latest('id')->first();

        $payload = [
            'percentual_vendas' => (float) ($distribuidor->percentual_vendas_base ?? 0),
            'vencimento_contrato' => null,
        ];

        if ($ativo) {
            $ref = Carbon::now();

            $inicio = null;
            if (!empty($ativo->data_assinatura)) {
                try { $inicio = Carbon::parse($ativo->data_assinatura)->startOfDay(); } catch (\Throwable $e) {}
            }
            if (!$inicio && !empty($ativo->created_at)) {
                try { $inicio = Carbon::parse($ativo->created_at)->startOfDay(); } catch (\Throwable $e) {}
            }

            $fim = null;
            if (!empty($ativo->data_vencimento)) {
                try { $fim = Carbon::parse($ativo->data_vencimento)->endOfDay(); } catch (\Throwable $e) {}
            }

            $vigente = true;

            if ($inicio && $ref->lt($inicio)) $vigente = false;
            if ($fim && $ref->gt($fim)) $vigente = false;

            if ($vigente) {
                if ($ativo->percentual_vendas !== null) {
                    $payload['percentual_vendas'] = (float) $ativo->percentual_vendas;
                }
                if (!empty($ativo->data_vencimento)) {
                    $payload['vencimento_contrato'] = $ativo->data_vencimento;
                }
            } else {
                // se o ativo está vencido, não aplica nada dele (volta pro base)
                $payload['vencimento_contrato'] = null;
            }
        }

        $distribuidor->update($payload);
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

    public function importarDistribuidoresDaPlanilha(\Illuminate\Http\UploadedFile $file, bool $atualizarExistentes = true): array
    {
        $ext = strtolower($file->getClientOriginalExtension());

        if (in_array($ext, ['csv', 'txt'], true)) {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();

            $sample = @file_get_contents($file->getPathname());
            $firstLine = $sample ? strtok($sample, "\r\n") : '';

            $countSemicolon = substr_count((string)$firstLine, ';');
            $countComma     = substr_count((string)$firstLine, ',');

            $delimiter = ($countSemicolon >= $countComma) ? ';' : ',';

            $reader->setDelimiter($delimiter);
            $reader->setEnclosure('"');
            $reader->setEscapeCharacter('\\');
            $reader->setInputEncoding('UTF-8');

            $spreadsheet = $reader->load($file->getPathname());
        } else {
            $spreadsheet = IOFactory::load($file->getPathname());
        }

        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();

        $criados = 0;
        $atualizados = 0;
        $pulados = 0;
        $erros = [];

        $driver = DB::connection()->getDriverName();

        for ($row = 2; $row <= $highestRow; $row++) {
            try {
                $gestorNome = trim((string) $sheet->getCell([1, $row])->getValue());
                $razao      = trim((string) $sheet->getCell([6, $row])->getValue());
                $represent  = trim((string) $sheet->getCell([7, $row])->getValue());
                $cnpj       = trim((string) $sheet->getCell([8, $row])->getValue());
                $cpf        = trim((string) $sheet->getCell([9, $row])->getValue());
                $rg         = trim((string) $sheet->getCell([10, $row])->getValue());
                $telRaw     = (string) $sheet->getCell([11, $row])->getValue();
                $emailRaw   = (string) $sheet->getCell([12, $row])->getValue();
                $senha      = (string) $sheet->getCell([13, $row])->getValue();

                $endereco   = trim((string) $sheet->getCell([14, $row])->getValue());
                $numero     = trim((string) $sheet->getCell([15, $row])->getValue());
                $compl      = trim((string) $sheet->getCell([16, $row])->getValue());
                $bairro     = trim((string) $sheet->getCell([17, $row])->getValue());
                $cidade     = trim((string) $sheet->getCell([18, $row])->getValue());
                $uf         = strtoupper(trim((string) $sheet->getCell([19, $row])->getValue()));
                $cep        = (string) $sheet->getCell([20, $row])->getValue();

                $percentRaw = $sheet->getCell([30, $row])->getValue() ?? null;
                $percentual = is_numeric($percentRaw) ? (float) $percentRaw : null;
                if ($percentual !== null) $percentual = max(0, min(100, $percentual));

                $endereco2  = trim((string) $sheet->getCell([21, $row])->getValue());
                $numero2    = trim((string) $sheet->getCell([22, $row])->getValue());
                $compl2     = trim((string) $sheet->getCell([23, $row])->getValue());
                $bairro2    = trim((string) $sheet->getCell([24, $row])->getValue());
                $cidade2    = trim((string) $sheet->getCell([25, $row])->getValue());
                $uf2        = strtoupper(trim((string) $sheet->getCell([26, $row])->getValue()));
                $cep2       = (string) $sheet->getCell([27, $row])->getValue();

                if ($razao === '' && trim($cnpj) === '') {
                    $pulados++;
                    continue;
                }

                $cnpjDigits = preg_replace('/\D+/', '', (string)$cnpj);
                $cpfDigits  = preg_replace('/\D+/', '', (string)$cpf);

                $cep  = preg_replace('/\D+/', '', (string)$cep);
                $cep  = $cep ? substr($cep, 0, 8) : null;
                $cep2 = preg_replace('/\D+/', '', (string)$cep2);
                $cep2 = $cep2 ? substr($cep2, 0, 8) : null;

                $telefones = $this->splitLista($telRaw);
                $telefones = collect($telefones)
                    ->map(fn($t) => preg_replace('/\D+/', '', (string)$t))
                    ->filter(fn($t) => $t !== '' && strlen($t) <= 30)
                    ->values()
                    ->all();

                $emails = $this->splitLista($emailRaw);
                $emails = collect($emails)
                    ->map(fn($e) => trim(mb_strtolower((string)$e)))
                    ->map(function ($e) {
                        $e = str_replace([' ', "\t", "\r", "\n"], '', $e);
                        $e = preg_replace('/^email:/i', '', $e);
                        return $e;
                    })
                    ->filter(fn($e) => $e !== '' && filter_var($e, FILTER_VALIDATE_EMAIL))
                    ->unique()
                    ->values()
                    ->all();

                $emailLogin = $emails[0] ?? null;

                if ($cpfDigits !== '' && strlen($cpfDigits) > 11) {
                    $cpfDigits = substr($cpfDigits, 0, 11);
                }

                $gestorId = null;
                if ($gestorNome !== '') {
                    $q = Gestor::query();
                    if ($driver === 'pgsql') $q->where('razao_social', 'ILIKE', $gestorNome);
                    else $q->whereRaw('LOWER(razao_social) = ?', [mb_strtolower($gestorNome)]);
                    $gestorId = optional($q->first(['id']))->id;
                }

                $distribuidorExistente = null;
                if ($cnpjDigits) {
                    $q = Distribuidor::query();

                    if ($driver === 'pgsql') {
                        $q->whereRaw("REGEXP_REPLACE(cnpj, '[^0-9]', '', 'g') = ?", [$cnpjDigits]);
                    } else {
                        $q->whereRaw("REPLACE(REPLACE(REPLACE(REPLACE(cnpj,'.',''),'-',''),'/',''),' ','') = ?", [$cnpjDigits]);
                    }

                    $distribuidorExistente = $q->first();
                }

                $payload = [
                    'gestor_id'           => $gestorId,

                    'razao_social'        => $razao ?: null,
                    'cnpj'                => $cnpj ?: null,
                    'representante_legal' => $represent ?: null,
                    'cpf'                 => $cpfDigits ? $cpfDigits : null,
                    'rg'                  => $rg ?: null,

                    'emails'              => $emails,
                    'telefones'           => $telefones,

                    'email'               => $emailLogin,
                    'password'            => $senha ?: null,

                    'endereco'            => $endereco ?: null,
                    'numero'              => $numero ?: null,
                    'complemento'         => $compl ?: null,
                    'bairro'              => $bairro ?: null,
                    'cidade'              => $cidade ?: null,
                    'uf'                  => $uf ?: null,
                    'cep'                 => $cep,

                    'endereco2'           => $endereco2 ?: null,
                    'numero2'             => $numero2 ?: null,
                    'complemento2'        => $compl2 ?: null,
                    'bairro2'             => $bairro2 ?: null,
                    'cidade2'             => $cidade2 ?: null,
                    'uf2'                 => $uf2 ?: null,
                    'cep2'                => $cep2,

                    // formulário manda percentual_vendas como base
                    'percentual_vendas'   => $percentual,

                    'cities'              => [],
                    'contratos'           => [],
                ];

                if ($distribuidorExistente) {
                    if (!$atualizarExistentes) {
                        $pulados++;
                        continue;
                    }

                    $antes = $distribuidorExistente->only([
                        'gestor_id','razao_social','cnpj','representante_legal','cpf','rg',
                        'emails','telefones',
                        'endereco','numero','complemento','bairro','cidade','uf','cep',
                        'endereco2','numero2','complemento2','bairro2','cidade2','uf2','cep2',
                        'percentual_vendas_base','percentual_vendas',
                    ]);

                    $antesUser = $distribuidorExistente->user
                        ? $distribuidorExistente->user->only(['email'])
                        : ['email' => null];

                    $fakeRequest = Request::create('/fake', 'POST', $payload);
                    $this->updateFromRequest($fakeRequest, $distribuidorExistente);

                    $distribuidorExistente->refresh()->load('user');

                    $depois = $distribuidorExistente->only([
                        'gestor_id','razao_social','cnpj','representante_legal','cpf','rg',
                        'emails','telefones',
                        'endereco','numero','complemento','bairro','cidade','uf','cep',
                        'endereco2','numero2','complemento2','bairro2','cidade2','uf2','cep2',
                        'percentual_vendas_base','percentual_vendas',
                    ]);

                    $depoisUser = $distribuidorExistente->user
                        ? $distribuidorExistente->user->only(['email'])
                        : ['email' => null];

                    $changed = false;
                    if ($antes != $depois) $changed = true;
                    if (($antesUser['email'] ?? null) !== ($depoisUser['email'] ?? null)) $changed = true;

                    if ($changed) $atualizados++;
                    else $pulados++;

                } else {
                    $fakeRequest = Request::create('/fake', 'POST', $payload);
                    $this->createFromRequest($fakeRequest);
                    $criados++;
                }

            } catch (\Throwable $e) {
                $erros[] = [
                    'linha' => $row,
                    'distribuidor' => (string) $sheet->getCell([6, $row])->getValue(),
                    'cnpj' => (string) $sheet->getCell([8, $row])->getValue(),
                    'erro' => $e->getMessage(),
                ];
            }
        }

        return [
            'criados' => $criados,
            'atualizados' => $atualizados,
            'pulados' => $pulados,
            'erros' => $erros,
        ];
    }

    private function splitLista(?string $raw): array
    {
        $raw = trim((string) $raw);
        if ($raw === '') return [];

        $parts = preg_split('/[;,\\n\\r]+/', $raw) ?: [];
        $parts = array_values(array_filter(array_map(fn($v) => trim((string)$v), $parts), fn($v) => $v !== ''));

        return $parts;
    }
}
