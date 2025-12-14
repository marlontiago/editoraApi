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

    public function store(Request $request)
    {
        // sanitiza contatos vazios (igual seu web)
        $rawContatos = $request->input('contatos', []);
        $contatosSan = collect($rawContatos)->filter(function ($c) {
            return trim($c['nome'] ?? '') !== ''
                || trim($c['email'] ?? '') !== ''
                || trim($c['telefone'] ?? '') !== ''
                || trim($c['whatsapp'] ?? '') !== ''
                || trim($c['cargo'] ?? '') !== ''
                || !empty($c['preferencial']);
        })->values()->all();

        [$telefones, $emails] = $this->normalizePhonesAndEmails(
            $request->input('telefones', []),
            $request->input('emails', [])
        );

        $request->merge([
            'contatos'  => $contatosSan,
            'telefones' => $telefones,
            'emails'    => $emails,
        ]);

        $data = $request->validate([
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

            'email'               => ['nullable','email','max:255','unique:users,email'],
            'password'            => ['nullable','string','min:8'],

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

            'contatos'                 => ['nullable','array'],
            'contatos.*.nome'          => ['required_with:contatos.*.tipo,contatos.*.email,contatos.*.telefone,contatos.*.whatsapp','string','max:255'],
            'contatos.*.email'         => ['nullable','email','max:255'],
            'contatos.*.telefone'      => ['nullable','string','max:30'],
            'contatos.*.whatsapp'      => ['nullable','string','max:30'],
            'contatos.*.cargo'         => ['nullable','string','max:100'],
            'contatos.*.tipo'          => ['nullable','in:principal,secundario,financeiro,comercial,outro'],
            'contatos.*.preferencial'  => ['nullable','boolean'],
            'contatos.*.observacoes'   => ['nullable','string','max:2000'],
        ]);

        $preferenciais = collect($data['contatos'] ?? [])->where('preferencial', true)->count();
        if ($preferenciais > 1) {
            throw ValidationException::withMessages([
                'contatos' => 'Selecione no máximo um contato como preferencial.'
            ]);
        }

        $temAssinado = false;
        if (!empty($data['contratos']) && is_array($data['contratos'])) {
            foreach ($data['contratos'] as $meta) {
                if (!empty($meta['assinado'])) { $temAssinado = true; break; }
            }
        }

        $gestor = DB::transaction(function () use ($data, $request, $temAssinado) {

            $userEmail = trim((string)($data['email'] ?? ''));
            $userPass  = (string)($data['password'] ?? '');

            if ($userEmail === '') $userEmail = 'gestor+'.Str::uuid().'@placeholder.local';
            if ($userPass === '')  $userPass  = Str::random(12);

            $user = User::create([
                'name'     => $data['razao_social'] ?? 'Gestor',
                'email'    => $userEmail,
                'password' => Hash::make($userPass),
            ]);

            if (method_exists($user, 'assignRole')) {
                $user->assignRole('gestor');
            }

            $gestor = Gestor::create([
                'user_id'             => $user->id,
                'razao_social'        => $data['razao_social'] ?? null,
                'cnpj'                => $data['cnpj'] ?? null,
                'representante_legal' => $data['representante_legal'] ?? null,
                'cpf'                 => $data['cpf'] ?? null,
                'rg'                  => $data['rg'] ?? null,

                'telefone'            => $data['telefone'] ?? null,
                'email'               => $data['email'] ?? null,

                'telefones'           => $data['telefones'] ?? null,
                'emails'              => $data['emails'] ?? null,

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

                'percentual_vendas'   => $data['percentual_vendas'] ?? 0,
                'vencimento_contrato' => null,
                'contrato_assinado'   => $temAssinado,
            ]);

            $this->syncUfs($gestor, $data['estados_uf'] ?? []);

            // anexos
            if (!empty($data['contratos']) && is_array($data['contratos'])) {
                $idAtivoEscolhido = null;

                foreach ($data['contratos'] as $idx => $meta) {
                    $file = $request->file("contratos.$idx.arquivo");
                    if (!$file) continue;

                    $path   = $file->store("gestores/{$gestor->id}", 'public');
                    $ativo  = !empty($meta['ativo']);

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

                if ($gestor->anexos()->where('ativo', true)->count() > 1) {
                    $gestor->anexos()->where('ativo', true)
                        ->where('id', '<>', $idAtivoEscolhido)
                        ->update(['ativo' => false]);
                }

                $ativo = $gestor->anexos()->where('ativo', true)->latest('id')->first();
                if ($ativo) {
                    if ($ativo->percentual_vendas !== null) {
                        $gestor->update(['percentual_vendas' => $ativo->percentual_vendas]);
                    }
                    if ($ativo->data_vencimento) {
                        $gestor->update(['vencimento_contrato' => $ativo->data_vencimento]);
                    }
                }
            }

            // contatos
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

            return $gestor->load('user','ufs','anexos','contatos');
        });

        return response()->json([
            'ok' => true,
            'message' => 'Gestor criado com sucesso!',
            'data' => $gestor,
        ], 201);
    }

    public function update(Request $request, Gestor $gestor)
{
    // sanitiza contatos vazios (igual store)
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

    $data = $request->validate([
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

        // ⚠️ no UPDATE precisa ignorar o user do próprio gestor
        'email'               => [
            'nullable',
            'email',
            'max:255',
            Rule::unique('users','email')->ignore($gestor->user_id),
        ],
        'password'            => ['nullable','string','min:8'],

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

        'contatos'                 => ['nullable','array'],
        // ⚠️ no seu API controller você não validou contato id no store; aqui vamos manter simples:
        'contatos.*.id'            => ['nullable','integer','exists:contatos,id'],
        'contatos.*.nome'          => ['required_with:contatos.*.tipo,contatos.*.email,contatos.*.telefone,contatos.*.whatsapp','string','max:255'],
        'contatos.*.email'         => ['nullable','email','max:255'],
        'contatos.*.telefone'      => ['nullable','string','max:30'],
        'contatos.*.whatsapp'      => ['nullable','string','max:30'],
        'contatos.*.cargo'         => ['nullable','string','max:100'],
        'contatos.*.tipo'          => ['nullable','in:principal,secundario,financeiro,comercial,outro'],
        'contatos.*.preferencial'  => ['nullable','boolean'],
        'contatos.*.observacoes'   => ['nullable','string','max:2000'],
    ]);

    // no máx 1 preferencial
    $preferenciais = collect($data['contatos'] ?? [])->where('preferencial', true)->count();
    if ($preferenciais > 1) {
        throw ValidationException::withMessages([
            'contatos' => 'Selecione no máximo um contato como preferencial.'
        ]);
    }

    DB::transaction(function () use ($data, $request, $gestor) {

        // 1) USER: atualiza email/senha (se enviados)
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

        // 2) GESTOR: atualiza campos
        $gestor->update([
            'razao_social'        => $data['razao_social']        ?? $gestor->razao_social,
            'cnpj'                => $data['cnpj']                ?? $gestor->cnpj,
            'representante_legal' => $data['representante_legal'] ?? $gestor->representante_legal,
            'cpf'                 => $data['cpf']                 ?? $gestor->cpf,
            'rg'                  => $data['rg']                  ?? $gestor->rg,

            'telefone'            => $data['telefone']            ?? $gestor->telefone,
            // mantém o campo email do gestor (se você usa)
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

        // 3) UFs (sync)
        $this->syncUfs($gestor, $data['estados_uf'] ?? []);

        // 4) ANEXOS (append)
        if (!empty($data['contratos']) && is_array($data['contratos'])) {
            $idAtivoEscolhido = null;

            foreach ($data['contratos'] as $idx => $meta) {
                $file = $request->file("contratos.$idx.arquivo");
                if (!$file) continue;

                $path   = $file->store("gestores/{$gestor->id}", 'public');
                $ativo  = !empty($meta['ativo']);

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

            // aplica percentual/vencimento do ativo
            $ativo = $gestor->anexos()->where('ativo', true)->latest('id')->first();
            if ($ativo) {
                $payload = [];
                if ($ativo->percentual_vendas !== null) {
                    $payload['percentual_vendas'] = $ativo->percentual_vendas;
                }
                if ($ativo->data_vencimento) {
                    $payload['vencimento_contrato'] = $ativo->data_vencimento;
                }
                if (!empty($payload)) {
                    $gestor->update($payload);
                }
            }
        }

        // 5) contrato_assinado (derivado)
        $temAssinadoAgora = $gestor->anexos()->where('assinado', true)->exists();
        if ($gestor->contrato_assinado !== $temAssinadoAgora) {
            $gestor->update(['contrato_assinado' => $temAssinadoAgora]);
        }

        // 6) CONTATOS (sync completo)
        $inputContatos = collect($data['contatos'] ?? [])->map(function ($c) {
            $c['telefone'] = isset($c['telefone']) ? preg_replace('/\D+/', '', (string)$c['telefone']) : null;
            $c['whatsapp'] = isset($c['whatsapp']) ? preg_replace('/\D+/', '', (string)$c['whatsapp']) : null;
            return $c;
        })->values()->all();

        $this->syncContatos($gestor, $inputContatos);
    });

    $gestor->load('user','ufs','anexos','contatos');

    return response()->json([
        'ok' => true,
        'message' => 'Gestor atualizado com sucesso!',
        'data' => $gestor,
    ]);
}


    public function destroy(Gestor $gestor)
    {
        DB::transaction(function () use ($gestor) {
            $gestor->ufs()->delete();
            $gestor->anexos()->delete();
            $gestor->delete();
        });

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

    public function cidadesPorUfs(Request $request)
    {
        $ufs = collect(explode(',', (string)$request->query('ufs', '')))
            ->map(fn($u) => strtoupper(trim($u)))
            ->filter(fn($u) => in_array($u, $this->UFs, true))
            ->unique()
            ->values()
            ->all();

        if (empty($ufs)) {
            return response()->json(['ok' => true, 'data' => []]);
        }

        $table = (new City)->getTable();
        
        $hasNome = Schema::hasColumn($table, 'nome') ? 'nome'
        : (Schema::hasColumn($table, 'name') ? 'name' : null);

        $hasUf   = Schema::hasColumn($table, 'uf') ? 'uf'
                : (Schema::hasColumn($table, 'estado_uf') ? 'estado_uf'
                : (Schema::hasColumn($table, 'state') ? 'state' : null));

        $hasId   = Schema::hasColumn($table, 'id') ? 'id' : null;
        if (!$hasNome || !$hasUf || !$hasId) {
            return response()->json(['ok' => true, 'data' => []]);
        }

        $cidades = City::query()
            ->whereIn($hasUf, $ufs)
            ->orderBy($hasUf)
            ->orderBy($hasNome)
            ->get([$hasId.' as id', $hasNome.' as nome', $hasUf.' as uf']);

        $payload = $cidades->map(fn($c) => [
            'id'   => $c->id,
            'text' => "{$c->nome} ({$c->uf})",
            'uf'   => $c->uf,
        ])->values();

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

    public function destroyAnexo(Gestor $gestor, Anexo $anexo)
    {
        $this->assertAnexoDoGestor($gestor, $anexo);

        // remove arquivo físico
        if ($anexo->arquivo && Storage::disk('public')->exists($anexo->arquivo)) {
            Storage::disk('public')->delete($anexo->arquivo);
        }

        $anexo->delete();

        // atualiza flags derivadas
        $temAssinadoAgora = $gestor->anexos()->where('assinado', true)->exists();
        if ($gestor->contrato_assinado !== $temAssinadoAgora) {
            $gestor->update(['contrato_assinado' => $temAssinadoAgora]);
        }

        // se deletou o ativo, tenta reaplicar do novo ativo (se existir)
        $this->aplicarDadosDoAnexoAtivoNoGestor($gestor);

        return response()->json([
            'ok' => true,
            'message' => 'Anexo excluído com sucesso.',
        ]);
    }

    public function ativarAnexo(Gestor $gestor, Anexo $anexo)
    {
        $this->assertAnexoDoGestor($gestor, $anexo);

        DB::transaction(function () use ($gestor, $anexo) {
            $gestor->anexos()->where('ativo', true)->update(['ativo' => false]);
            $anexo->update(['ativo' => true]);

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
        });

        return response()->json([
            'ok' => true,
            'message' => 'Contrato/aditivo ativado e percentual aplicado.',
        ]);
    }


    private function assertAnexoDoGestor(Gestor $gestor, Anexo $anexo): void
    {
        if ($anexo->anexavel_type !== Gestor::class || (int)$anexo->anexavel_id !== (int)$gestor->id) {
            abort(403, 'Anexo não pertence a este gestor.');
        }
    }

    private function aplicarDadosDoAnexoAtivoNoGestor(Gestor $gestor): void
    {
        $ativo = $gestor->anexos()->where('ativo', true)->latest('id')->first();
        if (!$ativo) return;

        $payload = [];
        if ($ativo->percentual_vendas !== null) {
            $payload['percentual_vendas'] = $ativo->percentual_vendas;
        }
        if ($ativo->data_vencimento) {
            $payload['vencimento_contrato'] = $ativo->data_vencimento;
        }
        if (!empty($payload)) {
            $gestor->update($payload);
        }
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
