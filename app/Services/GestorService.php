<?php

namespace App\Services;

use App\Models\Anexo;
use App\Models\City;
use App\Models\Distribuidor;
use App\Models\Gestor;
use App\Models\GestorUf;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;

class GestorService
{
    /** Lista “oficial” de UFs */
    public array $UFs = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'];

    /** -------------------------
     *  STORE / UPDATE
     *  ------------------------- */

    public function storeFromRequest(Request $request): Gestor
    {
        
        $request = $this->sanitizeAndMergeInputs($request);

        $data = $this->validateStore($request);

        $this->assertOnePreferencial($data);

        $temAssinado = $this->deriveTemAssinadoFromContratos($data);

        return DB::transaction(function () use ($data, $request, $temAssinado) {

            // USER (placeholder se vazio)
            $userEmail = trim((string)($data['email'] ?? ''));
            $userPass  = (string)($data['password'] ?? '');

            if ($userEmail === '') $userEmail = 'gestor+'.Str::uuid().'@placeholder.local';
            if ($userPass === '')  $userPass  = Str::random(12);

            /** @var User $user */
            $user = User::create([
                'name'     => $data['razao_social'] ?? 'Gestor',
                'email'    => $userEmail,
                'password' => Hash::make($userPass),
            ]);

            if (method_exists($user, 'assignRole')) {
                $user->assignRole('gestor');
            }

            /** @var Gestor $gestor */
            $gestor = Gestor::create([
                'user_id'             => $user->id,
                'razao_social'        => $data['razao_social'] ?? null,
                'cnpj'                => $data['cnpj'] ?? null,
                'representante_legal' => $data['representante_legal'] ?? null,
                'cpf'                 => $data['cpf'] ?? null,
                'rg'                  => $data['rg'] ?? null,

                // antigos
                'telefone'            => $data['telefone'] ?? null,
                'email'               => $data['email'] ?? null,

                // novos
                'telefones'           => $data['telefones'] ?? null,
                'emails'              => $data['emails'] ?? null,

                // endereços
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

                // contratuais
                'percentual_vendas'   => $data['percentual_vendas'] ?? 0,
                'vencimento_contrato' => null, // será definido pelo anexo ativo
                'contrato_assinado'   => $temAssinado,
            ]);

            // UFs
            $this->syncUfs($gestor, $data['estados_uf'] ?? []);

            // ANEXOS (append)
            $this->appendContratos($request, $gestor, $data);

            // CONTATOS
            $this->createContatosOnStore($gestor, $data);

            return $gestor->load('user','ufs','anexos','contatos');
        });
    }

    public function updateFromRequest(Request $request, Gestor $gestor): Gestor
    {
        
        $request = $this->sanitizeAndMergeInputs($request);

        $data = $this->validateUpdate($request, $gestor);

        $this->assertOnePreferencial($data);


        DB::transaction(function () use ($data, $request, $gestor) {

            // USER
            $user = $gestor->user;
            if ($user) {
                if (!empty($data['email'])) {
                    $user->email = $data['email'];
                }
                if (!empty($data['password'])) {
                    $user->password = Hash::make($data['password']);
                }
                if (!empty($data['email']) || !empty($data['password'])) {
                    $user->save();
                }
            }

            // GESTOR
            $gestor->update([
                'razao_social'        => $data['razao_social']        ?? $gestor->razao_social,
                'cnpj'                => $data['cnpj']                ?? $gestor->cnpj,
                'representante_legal' => $data['representante_legal'] ?? $gestor->representante_legal,
                'cpf'                 => $data['cpf']                 ?? $gestor->cpf,
                'rg'                  => $data['rg']                  ?? $gestor->rg,

                'telefone'            => $data['telefone']            ?? $gestor->telefone,
                'email'               => $data['email']               ?? $gestor->email,

                'telefones'           => $data['telefones']           ?? $gestor->telefones,
                'emails'              => $data['emails']              ?? $gestor->emails,

                'endereco'            => $data['endereco']            ?? $gestor->endereco,
                'numero'              => $data['numero']              ?? $gestor->numero,
                'complemento'         => $data['complemento']         ?? $gestor->complemento,
                'bairro'              => $data['bairro']              ?? $gestor->bairro,
                'cidade'              => $data['cidade']              ?? $gestor->cidade,
                'uf'                  => $data['uf']                  ?? $gestor->uf,
                'cep'                 => $data['cep']                 ?? $gestor->cep,

                'endereco2'           => $data['endereco2']           ?? $gestor->endereco2,
                'numero2'             => $data['numero2']             ?? $gestor->numero2,
                'complemento2'        => $data['complemento2']        ?? $gestor->complemento2,
                'bairro2'             => $data['bairro2']             ?? $gestor->bairro2,
                'cidade2'             => $data['cidade2']             ?? $gestor->cidade2,
                'uf2'                 => $data['uf2']                 ?? $gestor->uf2,
                'cep2'                => $data['cep2']                ?? $gestor->cep2,

                'percentual_vendas'   => $data['percentual_vendas']   ?? $gestor->percentual_vendas,
            ]);

            // UFs
            $this->syncUfs($gestor, $data['estados_uf'] ?? []);

            // ANEXOS (append)
            $this->appendContratos($request, $gestor, $data);

            // contrato_assinado (derivado)
            $temAssinadoAgora = $gestor->anexos()->where('assinado', true)->exists();
            if ($gestor->contrato_assinado !== $temAssinadoAgora) {
                $gestor->update(['contrato_assinado' => $temAssinadoAgora]);
            }

            // aplica do ativo
            $this->applyActiveAnexoToGestor($gestor);

            // CONTATOS (sync completo)
            $inputContatos = collect($data['contatos'] ?? [])->map(function ($c) {
                $c['telefone'] = isset($c['telefone']) ? preg_replace('/\D+/', '', (string)$c['telefone']) : null;
                $c['whatsapp'] = isset($c['whatsapp']) ? preg_replace('/\D+/', '', (string)$c['whatsapp']) : null;
                return $c;
            })->values()->all();

            $this->syncContatos($gestor, $inputContatos);
        });

        return $gestor->fresh()->load('user','ufs','anexos','contatos');
    }

    /** -------------------------
     *  ANEXOS (DELETE / ATIVAR)
     *  ------------------------- */

    public function destroyAnexo(Gestor $gestor, Anexo $anexo): void
    {
        $this->assertAnexoDoGestor($gestor, $anexo);

        if ($anexo->arquivo && Storage::disk('public')->exists($anexo->arquivo)) {
            Storage::disk('public')->delete($anexo->arquivo);
        }

        $anexo->delete();

        // atualiza flags derivadas
        $temAssinadoAgora = $gestor->anexos()->where('assinado', true)->exists();
        if ($gestor->contrato_assinado !== $temAssinadoAgora) {
            $gestor->update(['contrato_assinado' => $temAssinadoAgora]);
        }

        $this->applyActiveAnexoToGestor($gestor);
    }

    public function ativarAnexo(Gestor $gestor, Anexo $anexo): void
    {
        $this->assertAnexoDoGestor($gestor, $anexo);

        DB::transaction(function () use ($gestor, $anexo) {
            $gestor->anexos()->where('ativo', true)->update(['ativo' => false]);
            $anexo->update(['ativo' => true]);

            $this->applyAnexoDataToGestor($gestor, $anexo);
        });
    }

    /** -------------------------
     *  DESTROY GESTOR
     *  ------------------------- */

    public function destroyGestor(Gestor $gestor): void
    {
        DB::transaction(function () use ($gestor) {
            $gestor->ufs()->delete();
            $gestor->anexos()->delete();
            $gestor->contatos()->delete();

            // opcional: deletar user também (se fizer sentido no seu domínio)
            // if ($gestor->user) { $gestor->user->delete(); }

            $gestor->delete();
        });
    }

    /** -------------------------
     *  VINCULAR DISTRIBUIDORES (miolo opcional)
     *  ------------------------- */

    public function storeVinculo(array $vinculos): int
    {
        $idsDistribuidores = collect(array_keys($vinculos))
            ->map(fn($id) => (int) $id)->filter()->values()->all();

        if (empty($idsDistribuidores)) return 0;

        $existem = Distribuidor::whereIn('id', $idsDistribuidores)->count();
        if ($existem !== count($idsDistribuidores)) {
            throw ValidationException::withMessages([
                'vinculos' => 'Há distribuidores inválidos.',
            ]);
        }

        $idsGestores = collect($vinculos)
            ->map(fn($v) => $v === '' ? null : (int) $v)
            ->filter()->unique()->values()->all();

        if (!empty($idsGestores)) {
            $validos = Gestor::whereIn('id', $idsGestores)->count();
            if ($validos !== count($idsGestores)) {
                throw ValidationException::withMessages([
                    'vinculos' => 'Há gestor inválido.',
                ]);
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

        return $alterados;
    }

    /** -------------------------
     *  HELPERS (INPUTS / VALIDATION)
     *  ------------------------- */

    private function sanitizeAndMergeInputs(Request $request): Request
    {
        // sanitiza contatos vazios
        $rawContatos = $request->input('contatos', []);
        $contatosSan = collect($rawContatos)->filter(function ($c) {
            return trim($c['nome'] ?? '') !== ''
                || trim($c['email'] ?? '') !== ''
                || trim($c['telefone'] ?? '') !== ''
                || trim($c['whatsapp'] ?? '') !== ''
                || trim($c['cargo'] ?? '') !== ''
                || !empty($c['preferencial']);
        })->values()->all();

        // normaliza listas telefone/email
        [$telefones, $emails] = $this->normalizePhonesAndEmails(
            $request->input('telefones', []),
            $request->input('emails', [])
        );

        $request->merge([
            'contatos'  => $contatosSan,
            'telefones' => $telefones,
            'emails'    => $emails,
        ]);

        return $request;
    }

    private function validateStore(Request $request): array
    {
        return $request->validate($this->rulesBase() + [
            'email'    => ['nullable','email','max:255','unique:users,email'],
            'password' => ['nullable','string','min:8'],
        ]);
    }

    private function validateUpdate(Request $request, Gestor $gestor): array
    {
        return $request->validate($this->rulesBase() + [
            'email'    => ['nullable','email','max:255', Rule::unique('users','email')->ignore($gestor->user_id)],
            'password' => ['nullable','string','min:8'],
        ]);
    }

    private function rulesBase(): array
    {
        return [
            'razao_social'        => ['nullable','string','max:255'],
            'cnpj'                => ['nullable','string','max:18'],
            'representante_legal' => ['nullable','string','max:255'],
            'cpf'                 => ['nullable','string','max:14'],
            'rg'                  => ['nullable','string','max:30'],

            'telefones'           => ['nullable','array'],
            'telefones.*'         => ['nullable','string','max:30'],
            'emails'              => ['nullable','array'],
            'emails.*'            => ['nullable','email','max:255'],

            'telefone'            => ['nullable','string','max:20'],

            'estados_uf'          => ['nullable','array'],
            'estados_uf.*'        => ['in:AC,AL,AP,AM,BA,CE,DF,ES,GO,MA,MT,MS,MG,PA,PB,PR,PE,PI,RJ,RN,RS,RO,RR,SC,SP,SE,TO'],

            // endereços
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

            'percentual_vendas'   => ['nullable','numeric','min:0','max:100'],

            // anexos
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

            // contatos
            'contatos'                 => ['nullable','array'],
            'contatos.*.id'            => ['nullable','integer','exists:contatos,id'],
            'contatos.*.nome'          => ['required_with:contatos.*.tipo,contatos.*.email,contatos.*.telefone,contatos.*.whatsapp','string','max:255'],
            'contatos.*.email'         => ['nullable','email','max:255'],
            'contatos.*.telefone'      => ['nullable','string','max:30'],
            'contatos.*.whatsapp'      => ['nullable','string','max:30'],
            'contatos.*.cargo'         => ['nullable','string','max:100'],
            'contatos.*.tipo'          => ['nullable','in:principal,secundario,financeiro,comercial,outro'],
            'contatos.*.preferencial'  => ['nullable','boolean'],
            'contatos.*.observacoes'   => ['nullable','string','max:2000'],
        ];
    }

    private function assertOnePreferencial(array $data): void
    {
        $preferenciais = collect($data['contatos'] ?? [])->where('preferencial', true)->count();
        if ($preferenciais > 1) {
            throw ValidationException::withMessages([
                'contatos' => 'Selecione no máximo um contato como preferencial.'
            ]);
        }
    }

    private function deriveTemAssinadoFromContratos(array $data): bool
    {
        if (empty($data['contratos']) || !is_array($data['contratos'])) return false;
        foreach ($data['contratos'] as $meta) {
            if (!empty($meta['assinado'])) return true;
        }
        return false;
    }

    /** -------------------------
     *  UFs / CONTATOS
     *  ------------------------- */

    public function normalizePhonesAndEmails($telefones, $emails): array
    {
        $tels = is_array($telefones) ? $telefones : [];
        $tels = array_values(array_filter(array_map(fn($t)=>trim((string)$t), $tels), fn($t)=>$t!==''));

        $mails = is_array($emails) ? $emails : [];
        $mails = array_values(array_filter(array_map(fn($e)=>trim((string)$e), $mails), fn($e)=>$e!==''));

        return [$tels, $mails];
    }

    public function syncUfs(Gestor $gestor, array $ufsInput): void
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

        Cache::forget("gestor:{$gestor->id}:ufs");
    }

    public function syncContatos($dono, array $inputContatos): void
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

    /** -------------------------
     *  ANEXOS HELPERS
     *  ------------------------- */

    private function assertAnexoDoGestor(Gestor $gestor, Anexo $anexo): void
    {
        if ($anexo->anexavel_type !== Gestor::class || (int)$anexo->anexavel_id !== (int)$gestor->id) {
            abort(403, 'Anexo não pertence a este gestor.');
        }
    }

    private function applyActiveAnexoToGestor(Gestor $gestor): void
    {
        $ativo = $gestor->anexos()->where('ativo', true)->latest('id')->first();
        if (!$ativo) return;

        $this->applyAnexoDataToGestor($gestor, $ativo);
    }

    private function applyAnexoDataToGestor(Gestor $gestor, Anexo $anexo): void
    {
        $payload = [];
        if ($anexo->percentual_vendas !== null) {
            $payload['percentual_vendas'] = $anexo->percentual_vendas;
        }
        if ($anexo->data_vencimento) {
            $payload['vencimento_contrato'] = $anexo->data_vencimento;
        }
        if (!empty($payload)) {
            $gestor->update($payload);
        }
    }

    private function appendContratos(Request $request, Gestor $gestor, array $data): void
    {
        if (empty($data['contratos']) || !is_array($data['contratos'])) return;

        $idAtivoEscolhido = null;

        foreach ($data['contratos'] as $idx => $meta) {
            $file = $request->file("contratos.$idx.arquivo");
            if (!$file) continue;

            $path  = $file->store("gestores/{$gestor->id}", 'public');
            $ativo = !empty($meta['ativo']);

            $inicio = !empty($meta['data_assinatura']) ? Carbon::parse($meta['data_assinatura']) : null;
            $meses  = !empty($meta['validade_meses']) ? (int)$meta['validade_meses'] : null;
            $dataVenc = ($inicio && $meses) ? (clone $inicio)->addMonthsNoOverflow($meses) : null;

            $anexo = $gestor->anexos()->create([
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

        // no máx 1 ativo
        if ($gestor->anexos()->where('ativo', true)->count() > 1) {
            $gestor->anexos()->where('ativo', true)
                ->where('id', '<>', $idAtivoEscolhido)
                ->update(['ativo' => false]);
        }

        // aplica do ativo (se existir)
        $this->applyActiveAnexoToGestor($gestor);
    }

    private function createContatosOnStore(Gestor $gestor, array $data): void
    {
        $contatos = collect($data['contatos'] ?? [])
            ->map(function ($c) {
                $tel = isset($c['telefone']) ? preg_replace('/\D+/', '', (string)$c['telefone']) : null;
                $zap = isset($c['whatsapp']) ? preg_replace('/\D+/', '', (string)$c['whatsapp']) : null;

                return [
                    'nome'         => $c['nome'] ?? '',
                    'email'        => $c['email'] ?? null,
                    'telefone'     => $tel,
                    'whatsapp'     => $zap,
                    'cargo'        => $c['cargo'] ?? null,
                    'tipo'         => $c['tipo'] ?? 'outro',
                    'preferencial' => !empty($c['preferencial']),
                    'observacoes'  => $c['observacoes'] ?? null,
                ];
            })
            ->filter(fn ($c) => trim($c['nome']) !== '');

        if ($contatos->isNotEmpty()) {
            $gestor->contatos()->createMany($contatos->all());
        }
    }

    /** -------------------------
     *  EXTRA: UFs ocupadas (pra tela web create/edit)
     *  ------------------------- */

    public function ufsOcupadas(?int $ignorarGestorId = null): array
    {
        $q = GestorUf::query()
            ->join('gestores', 'gestores.id', '=', 'gestor_ufs.gestor_id');

        if ($ignorarGestorId) {
            $q->where('gestor_ufs.gestor_id', '<>', $ignorarGestorId);
        }

        return $q->get(['gestor_ufs.uf', 'gestores.razao_social'])
            ->mapWithKeys(fn($r) => [strtoupper($r->uf) => $r->razao_social])
            ->all();
    }

    /** EXTRA: cidades por UFs (mesma lógica robusta que você já usa) */
    public function cidadesPorUfs(array $ufs): Collection
    {
        $ufs = collect($ufs)
            ->map(fn($u)=>strtoupper(trim((string)$u)))
            ->filter(fn($u)=>in_array($u, $this->UFs, true))
            ->unique()->values()->all();

        if (empty($ufs)) return collect();

        $table = (new City)->getTable();

        $hasNome = Schema::hasColumn($table, 'nome') ? 'nome' : (Schema::hasColumn($table, 'name') ? 'name' : null);
        $hasUf   = Schema::hasColumn($table, 'uf') ? 'uf'
                : (Schema::hasColumn($table, 'estado_uf') ? 'estado_uf'
                : (Schema::hasColumn($table, 'state') ? 'state' : null));
        $hasId   = Schema::hasColumn($table, 'id') ? 'id' : null;

        if (!$hasNome || !$hasUf || !$hasId) return collect();

        $cidades = City::query()
            ->whereIn($hasUf, $ufs)
            ->orderBy($hasUf)
            ->orderBy($hasNome)
            ->get([$hasId.' as id', $hasNome.' as nome', $hasUf.' as uf']);

        return $cidades->map(fn($c) => [
            'id'   => $c->id,
            'text' => "{$c->nome} ({$c->uf})",
            'uf'   => $c->uf,
        ])->values();
    }

    public function importarGestoresDaPlanilha(UploadedFile $file, bool $atualizarExistentes = true): array
{
    $ext = strtolower($file->getClientOriginalExtension());

    // Carregar planilha (XLSX/XLS ou CSV)
    if (in_array($ext, ['csv', 'txt'], true)) {
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();

        // Auto-detect de delimitador: tenta ; depois ,
        $sample = file_get_contents($file->getPathname());
        $firstLine = $sample ? strtok($sample, "\r\n") : '';

        $countSemicolon = substr_count((string) $firstLine, ';');
        $countComma     = substr_count((string) $firstLine, ',');

        $delimiter = ($countSemicolon >= $countComma) ? ';' : ',';

        $reader->setDelimiter($delimiter);
        $reader->setEnclosure('"');
        $reader->setEscapeCharacter('\\');

        // CSVs brasileiros às vezes vêm em ISO-8859-1/Windows-1252
        // Se vier estranho, troque pra 'Windows-1252'
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

    for ($row = 2; $row <= $highestRow; $row++) {
        try {
            $razao = trim((string) $sheet->getCell([1, $row])->getValue());
            $cnpj  = trim((string) $sheet->getCell([2, $row])->getValue());

            if ($razao === '' && $cnpj === '') {
                $pulados++;
                continue;
            }

            $representante = trim((string) $sheet->getCell([3, $row])->getValue());
            $cpf           = trim((string) $sheet->getCell([4, $row])->getValue());
            $rg            = trim((string) $sheet->getCell([5, $row])->getValue());

            $telefonesRaw  = (string) $sheet->getCell([6, $row])->getValue();
            $emailsRaw     = (string) $sheet->getCell([7, $row])->getValue();
            $senha         = (string) $sheet->getCell([8, $row])->getValue();

            $ufsRaw        = (string) $sheet->getCell([9, $row])->getValue();
            $percentRaw    = $sheet->getCell([10, $row])->getValue();

            // Endereço 1
            $endereco    = trim((string) $sheet->getCell([11, $row])->getValue());
            $numero      = trim((string) $sheet->getCell([12, $row])->getValue());
            $complemento = trim((string) $sheet->getCell([13, $row])->getValue());
            $bairro      = trim((string) $sheet->getCell([14, $row])->getValue());
            $cidade      = trim((string) $sheet->getCell([15, $row])->getValue());
            $uf          = strtoupper(trim((string) $sheet->getCell([16, $row])->getValue()));

            $cep = (string) $sheet->getCell([17, $row])->getValue();
            $cep = preg_replace('/\D+/', '', $cep);
            $cep = $cep ? substr($cep, 0, 8) : null;

            // Endereço 2
            $endereco2    = trim((string) $sheet->getCell([18, $row])->getValue());
            $numero2      = trim((string) $sheet->getCell([19, $row])->getValue());
            $complemento2 = trim((string) $sheet->getCell([20, $row])->getValue());
            $bairro2      = trim((string) $sheet->getCell([21, $row])->getValue());
            $cidade2      = trim((string) $sheet->getCell([22, $row])->getValue());
            $uf2          = strtoupper(trim((string) $sheet->getCell([23, $row])->getValue()));

            $cep2 = (string) $sheet->getCell([24, $row])->getValue();
            $cep2 = preg_replace('/\D+/', '', $cep2);
            $cep2 = $cep2 ? substr($cep2, 0, 8) : null;

            // Normalizações
            $cnpjDigits = preg_replace('/\D+/', '', $cnpj);
            $cpfDigits  = preg_replace('/\D+/', '', $cpf);

            $telefones = $this->splitLista($telefonesRaw);
            $emails    = $this->splitLista($emailsRaw);

            $ufs = collect($this->splitLista($ufsRaw))
                ->map(fn($u) => strtoupper(trim($u)))
                ->filter(fn($u) => in_array($u, $this->UFs, true))
                ->unique()
                ->values()
                ->all();

            $percentual = is_numeric($percentRaw) ? (float) $percentRaw : null;
            if ($percentual !== null) {
                $percentual = max(0, min(100, $percentual));
            }

            // Procura existente por CNPJ (se tiver CNPJ)
            $gestorExistente = null;
            if ($cnpjDigits) {
                $gestorExistente = Gestor::query()
                    ->whereRaw("REGEXP_REPLACE(cnpj, '[^0-9]', '') = ?", [$cnpjDigits])
                    ->first();
            }

            $payload = [
                'razao_social'        => $razao ?: null,
                'cnpj'                => $cnpj ?: null,
                'representante_legal' => $representante ?: null,
                'cpf'                 => $cpf ?: null,
                'rg'                  => $rg ?: null,

                'telefones'           => $telefones ?: [],
                'emails'              => $emails ?: [],
                'email'               => $emails[0] ?? null,
                'password'            => $senha ?: null,

                'estados_uf'          => $ufs ?: [],
                'percentual_vendas'   => $percentual,

                'endereco'            => $endereco ?: null,
                'numero'              => $numero ?: null,
                'complemento'         => $complemento ?: null,
                'bairro'              => $bairro ?: null,
                'cidade'              => $cidade ?: null,
                'uf'                  => $uf ?: null,
                'cep'                 => $cep,

                'endereco2'           => $endereco2 ?: null,
                'numero2'             => $numero2 ?: null,
                'complemento2'        => $complemento2 ?: null,
                'bairro2'             => $bairro2 ?: null,
                'cidade2'             => $cidade2 ?: null,
                'uf2'                 => $uf2 ?: null,
                'cep2'                => $cep2,

                'contratos'           => [],
                'contatos'            => [],
            ];

            if ($gestorExistente) {
                if (!$atualizarExistentes) {
                    $pulados++;
                    continue;
                }

                $fakeRequest = Request::create('/fake', 'POST', $payload);
                $this->updateFromRequest($fakeRequest, $gestorExistente);
                $atualizados++;
            } else {
                $fakeRequest = Request::create('/fake', 'POST', $payload);
                $this->storeFromRequest($fakeRequest);
                $criados++;
            }
        } catch (\Throwable $e) {
            $erros[] = [
                'linha' => $row,
                'gestor' => $sheet->getCell([1, $row])->getValue(),
                'cnpj' => $sheet->getCell([2, $row])->getValue(),
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


/**
 * Divide listas do Excel: separa por vírgula, ponto-e-vírgula ou quebra de linha.
 */
private function splitLista(?string $raw): array
{
    $raw = trim((string) $raw);
    if ($raw === '') return [];

    $parts = preg_split('/[;,\\n\\r]+/', $raw) ?: [];
    $parts = array_values(array_filter(array_map(fn($v) => trim((string)$v), $parts), fn($v) => $v !== ''));

    return $parts;
}


}
