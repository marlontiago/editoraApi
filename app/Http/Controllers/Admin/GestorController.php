<?php
// app/Http/Controllers/Admin/GestorController.php

namespace App\Http\Controllers\Admin;

use Illuminate\Validation\ValidationException;
use App\Http\Controllers\Controller;
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
use Illuminate\Validation\Rule;
use Illuminate\Database\QueryException;

class GestorController extends Controller
{
    /** Lista “oficial” de UFs */
    private array $UFs = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'];

    public function index()
    {
        $gestores = Gestor::with('user')->latest()->paginate(20);
        return view('admin.gestores.index', compact('gestores'));
    }

    public function create()
    {
        $ufs = $this->UFs;

        $ufOcupadas = GestorUf::query()
            ->join('gestores', 'gestores.id', '=', 'gestor_ufs.gestor_id')
            ->get(['gestor_ufs.uf', 'gestores.razao_social'])
            ->mapWithKeys(fn($r) => [$r->uf => $r->razao_social])
            ->all();

        return view('admin.gestores.create', compact('ufs','ufOcupadas'));
    }

    public function store(Request $request)
    {
        // 🔒 TRAVA AUTOFILL: Deriva 'email' a partir do primeiro item de emails[] (sem input name="email" na view)
        $primeiroEmail = trim((string) data_get($request->input('emails', []), 0, ''));
        $request->merge(['email' => $primeiroEmail !== '' ? $primeiroEmail : null]);

        // sanitiza contatos vazios (mantido)
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

        $data = $request->validate(
            [
                // Gestor
                'razao_social'        => ['nullable','string','max:255'],
                'cnpj'                => ['nullable','string','max:18'],
                'representante_legal' => ['nullable','string','max:255'],
                'cpf'                 => ['nullable','string','max:14'],
                'rg'                  => ['nullable','string','max:30'],

                // listas novas
                'telefones'           => ['nullable','array'],
                'telefones.*'         => ['nullable','string','max:30'],
                'emails'              => ['nullable','array'],
                'emails.*'            => ['nullable','email','max:255'],

                // antigos (mantidos)
                'telefone'            => ['nullable','string','max:20'],

                // UFs de atuação (MÚLTIPLAS)
                'estados_uf'          => ['nullable','array'],
                'estados_uf.*'        => ['in:AC,AL,AP,AM,BA,CE,DF,ES,GO,MA,MT,MS,MG,PA,PB,PR,PE,PI,RJ,RN,RS,RO,RR,SC,SP,SE,TO'],

                // e-mail/senha do USER (acesso)
                'email'               => ['nullable','email','max:255','unique:users,email'],
                'password'            => ['nullable','string','min:8'],

                // Endereço principal
                'endereco'            => ['nullable','string','max:255'],
                'numero'              => ['nullable','string','max:20'],
                'complemento'         => ['nullable','string','max:100'],
                'bairro'              => ['nullable','string','max:100'],
                'cidade'              => ['nullable','string','max:100'],
                'uf'                  => ['nullable','string','size:2'],
                'cep'                 => ['nullable','string','max:9'],

                // Endereço secundário (novos)
                'endereco2'           => ['nullable','string','max:255'],
                'numero2'             => ['nullable','string','max:20'],
                'complemento2'        => ['nullable','string','max:100'],
                'bairro2'             => ['nullable','string','max:100'],
                'cidade2'             => ['nullable','string','max:100'],
                'uf2'                 => ['nullable','string','size:2'],
                'cep2'                => ['nullable','string','max:9'],

                // Contratuais
                'percentual_vendas'   => ['nullable','numeric','min:0','max:100'],

                // Anexos (múltiplos)
                'contratos'                     => ['nullable','array'],
                'contratos.*.tipo'              => ['required_with:contratos.*.arquivo','in:contrato,aditivo,outro,contrato_cidade'],
                'contratos.*.cidade_id'         => ['required_if:contratos.*.tipo,contrato_cidade','integer','exists:cities,id'],
                'contratos.*.arquivo'           => ['nullable','file','mimes:pdf','max:5120'],
                'contratos.*.descricao'         => ['nullable','string','max:255'],
                'contratos.*.assinado'          => ['nullable','boolean'],
                'contratos.*.percentual_vendas' => ['nullable','numeric','min:0','max:100'],
                'contratos.*.ativo'             => ['nullable','boolean'],
                'contratos.*.data_assinatura'   => ['nullable','date'],
                'contratos.*.validade_meses'    => ['nullable','integer','min:1','max:120'],

                // Contatos
                'contatos'                 => ['nullable','array'],
                'contratos.*.cidade_id'    => [
                    'exclude_unless:contratos.*.tipo,contrato_cidade',
                    'required_if:contratos.*.tipo,contrato_cidade',
                    'integer',
                    'exists:cities,id',
                ],
                'contatos.*.nome'          => ['required_with:contatos.*.tipo,contatos.*.email,contatos.*.telefone,contatos.*.whatsapp','string','max:255'],
                'contatos.*.email'         => ['nullable','email','max:255'],
                'contatos.*.telefone'      => ['nullable','string','max:30'],
                'contatos.*.whatsapp'      => ['nullable','string','max:30'],
                'contatos.*.cargo'         => ['nullable','string','max:100'],
                'contatos.*.tipo'          => ['nullable','in:principal,secundario,financeiro,comercial,outro'],
                'contatos.*.preferencial'  => ['nullable','boolean'],
                'contatos.*.observacoes'   => ['nullable','string','max:2000'],
            ],
            [
                'email.unique' => 'Já existe um usuário cadastrado com este e-mail.',
            ]
        );

        // 1 preferencial no máx
        $preferenciais = collect($data['contatos'] ?? [])->where('preferencial', true)->count();
        if ($preferenciais > 1) {
            throw ValidationException::withMessages([
                'contatos' => 'Selecione no máximo um contato como preferencial.'
            ]);
        }

        // Deriva se há contrato assinado a partir dos anexos enviados
        $temAssinado = false;
        if (!empty($data['contratos']) && is_array($data['contratos'])) {
            foreach ($data['contratos'] as $meta) {
                if (!empty($meta['assinado'])) { $temAssinado = true; break; }
            }
        }

        try {
            $gestor = DB::transaction(function () use ($data, $request, $temAssinado) {
                // USER (placeholder se vazio)
                $userEmail = trim((string)($data['email'] ?? ''));
                $userPass  = (string)($data['password'] ?? '');
                if ($userEmail === '') $userEmail = 'gestor+'.Str::uuid().'@placeholder.local';
                if ($userPass === '')  $userPass  = Str::random(12);

                /** @var \App\Models\User $user */
                $user = User::create([
                    'name'     => $data['razao_social'],
                    'email'    => $userEmail,
                    'password' => Hash::make($userPass),
                ]);
                if (method_exists($user, 'assignRole')) {
                    $user->assignRole('gestor');
                }

                /** @var \App\Models\Gestor $gestor */
                $gestor = Gestor::create([
                    'user_id'             => $user->id,
                    'razao_social'        => $data['razao_social'],
                    'cnpj'                => $data['cnpj'],
                    'representante_legal' => $data['representante_legal'],
                    'cpf'                 => $data['cpf'],
                    'rg'                  => $data['rg'] ?? null,

                    // antigos (mantidos)
                    'telefone'            => $data['telefone'] ?? null,
                    'email'               => $data['email'] ?? null,

                    // novos
                    'telefones'           => $data['telefones'] ?? null,
                    'emails'              => $data['emails'] ?? null,

                    // endereço principal
                    'endereco'            => $data['endereco'] ?? null,
                    'numero'              => $data['numero'] ?? null,
                    'complemento'         => $data['complemento'] ?? null,
                    'bairro'              => $data['bairro'] ?? null,
                    'cidade'              => $data['cidade'] ?? null,
                    'uf'                  => $data['uf'] ?? null,
                    'cep'                 => $data['cep'] ?? null,

                    // endereço secundário
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

                // UFs de atuação (múltiplas)
                $this->syncUfs($gestor, $data['estados_uf'] ?? []);

                // ANEXOS
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
                        if ($ativo->percentual_vendas !== null) {
                            $gestor->update(['percentual_vendas' => $ativo->percentual_vendas]);
                        }
                        if ($ativo->data_vencimento) {
                            $gestor->update(['vencimento_contrato' => $ativo->data_vencimento]);
                        }
                    }
                }

                // CONTATOS (createMany)
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

                return $gestor;
            });

            return redirect()
                ->route('admin.gestores.index')
                ->with('success', 'Gestor criado com sucesso!');

        } catch (QueryException $e) {
            // 23505 = unique_violation no Postgres
            $code = (string) ($e->getCode() ?? '');
            if ($code === '23505') {
                throw ValidationException::withMessages([
                    'email' => 'Já existe um usuário cadastrado com este e-mail.',
                ]);
            }
            throw $e;
        }
    }

    public function show(Gestor $gestor)
    {
        $gestor->load('anexos','ufs');
        return view('admin.gestores.show', compact('gestor'));
    }

    public function edit(Gestor $gestor)
    {
        $gestor->load('anexos','contatos','ufs');
        $ufs = $this->UFs;

        // ocupadas, exceto as do próprio gestor
        $ufOcupadas = GestorUf::query()
            ->join('gestores', 'gestores.id', '=', 'gestor_ufs.gestor_id')
            ->where('gestor_ufs.gestor_id', '<>', $gestor->id)
            ->get(['gestor_ufs.uf', 'gestores.razao_social'])
            ->mapWithKeys(fn($r) => [$r->uf => $r->razao_social])
            ->all();

        return view('admin.gestores.edit', compact('gestor','ufs','ufOcupadas'));
    }

    public function update(Request $request, Gestor $gestor)
    {
        // 🔒 (Opcional) Se não veio 'email' explícito, usa o primeiro de emails[]
        if (!$request->filled('email')) {
            $primeiroEmail = trim((string) data_get($request->input('emails', []), 0, ''));
            if ($primeiroEmail !== '') {
                $request->merge(['email' => $primeiroEmail]);
            }
        }

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

        $data = $request->validate(
            [
                'razao_social'        => ['nullable','string','max:255'],
                'cnpj'                => ['nullable','string','max:18'],
                'representante_legal' => ['nullable','string','max:255'],
                'cpf'                 => ['nullable','string','max:14'],
                'rg'                  => ['nullable','string','max:30'],

                // listas novas
                'telefones'           => ['nullable','array'],
                'telefones.*'         => ['nullable','string','max:30'],
                'emails'              => ['nullable','array'],
                'emails.*'            => ['nullable','email','max:255'],

                // antigos (mantidos)
                'telefone'            => ['nullable','string','max:20'],

                // UFs de atuação múltiplas
                'estados_uf'          => ['nullable','array'],
                'estados_uf.*'        => ['in:AC,AL,AP,AM,BA,CE,DF,ES,GO,MA,MT,MS,MG,PA,PB,PR,PE,PI,RJ,RN,RS,RO,RR,SC,SP,SE,TO'],

                // e-mail/senha USER (acesso)
                'email'               => [
                    'nullable','email','max:255',
                    Rule::unique('users','email')->ignore($gestor->user_id),
                ],
                'password'            => ['nullable','string','min:8'],

                // Endereço principal
                'endereco'            => ['nullable','string','max:255'],
                'numero'              => ['nullable','string','max:20'],
                'complemento'         => ['nullable','string','max:100'],
                'bairro'              => ['nullable','string','max:100'],
                'cidade'              => ['nullable','string','max:100'],
                'uf'                  => ['nullable','string','size:2'],
                'cep'                 => ['nullable','string','max:9'],

                // Endereço secundário
                'endereco2'           => ['nullable','string','max:255'],
                'numero2'             => ['nullable','string','max:20'],
                'complemento2'        => ['nullable','string','max:100'],
                'bairro2'             => ['nullable','string','max:100'],
                'cidade2'             => ['nullable','string','max:100'],
                'uf2'                 => ['nullable','string','size:2'],
                'cep2'                => ['nullable','string','max:9'],

                // Contratuais
                'percentual_vendas'   => ['nullable','numeric','min:0','max:100'],

                // Anexos novos (append)
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

                // Contatos
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
            ],
            [
                'email.unique' => 'Este e-mail já está sendo usado por outro usuário.',
            ]
        );

        // 1 preferencial no máx
        $preferenciais = collect($data['contatos'] ?? [])->where('preferencial', true)->count();
        if ($preferenciais > 1) {
            throw ValidationException::withMessages([
                'contatos' => 'Selecione no máximo um contato como preferencial.'
            ]);
        }

        try {
            DB::transaction(function () use ($data, $request, $gestor) {
                // USER
                $user = $gestor->user;
                if (!empty($data['email']))    $user->email    = $data['email'];
                if (!empty($data['password'])) $user->password = Hash::make($data['password']);
                if (!empty($data['email']) || !empty($data['password'])) $user->save();

                // GESTOR
                $gestor->update([
                    'razao_social'        => $data['razao_social'],
                    'cnpj'                => $data['cnpj'],
                    'representante_legal' => $data['representante_legal'],
                    'cpf'                 => $data['cpf'],
                    'rg'                  => $data['rg'] ?? null,

                    // antigos (mantidos)
                    'telefone'            => $data['telefone'] ?? $gestor->telefone,
                    'email'               => $data['email'] ?? $gestor->email,

                    // novos
                    'telefones'           => $data['telefones'] ?? null,
                    'emails'              => $data['emails'] ?? null,

                    // endereço principal
                    'endereco'            => $data['endereco'] ?? null,
                    'numero'              => $data['numero'] ?? null,
                    'complemento'         => $data['complemento'] ?? null,
                    'bairro'              => $data['bairro'] ?? null,
                    'cidade'              => $data['cidade'] ?? null,
                    'uf'                  => $data['uf'] ?? null,
                    'cep'                 => $data['cep'] ?? null,

                    // endereço secundário
                    'endereco2'           => $data['endereco2'] ?? null,
                    'numero2'             => $data['numero2'] ?? null,
                    'complemento2'        => $data['complemento2'] ?? null,
                    'bairro2'             => $data['bairro2'] ?? null,
                    'cidade2'             => $data['cidade2'] ?? null,
                    'uf2'                 => $data['uf2'] ?? null,
                    'cep2'                => $data['cep2'] ?? null,

                    'percentual_vendas'   => $data['percentual_vendas'] ?? ($gestor->percentual_vendas ?? 0),
                ]);

                // UFs (sync)
                $this->syncUfs($gestor, $data['estados_uf'] ?? []);

                // ANEXOS (append)
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
                }

                // contrato_assinado (derivado)
                $temAssinadoAgora = $gestor->anexos()->where('assinado', true)->exists();
                if ($gestor->contrato_assinado !== $temAssinadoAgora) {
                    $gestor->update(['contrato_assinado' => $temAssinadoAgora]);
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

                // CONTATOS (sync completo)
                $inputContatos = collect($data['contatos'] ?? [])->map(function ($c) {
                    $c['telefone'] = isset($c['telefone']) ? preg_replace('/\D+/', '', (string)$c['telefone']) : null;
                    $c['whatsapp'] = isset($c['whatsapp']) ? preg_replace('/\D+/', '', (string)$c['whatsapp']) : null;
                    return $c;
                })->values()->all();

                $this->syncContatos($gestor, $inputContatos);
            });

            return redirect()
                ->route('admin.gestores.index')
                ->with('success', 'Gestor atualizado com sucesso!');

        } catch (QueryException $e) {
            $code = (string) ($e->getCode() ?? '');
            if ($code === '23505') {
                throw ValidationException::withMessages([
                    'email' => 'Este e-mail já está sendo usado por outro usuário.',
                ]);
            }
            throw $e;
        }
    }

    public function destroy(Gestor $gestor)
    {
        DB::transaction(function () use ($gestor) {
            $gestor->ufs()->delete();
            $gestor->anexos()->delete();
            $gestor->delete();
        });

        return redirect()
            ->route('admin.gestores.index')
            ->with('success', 'Gestor removido com sucesso!');
    }

    public function destroyAnexo(Gestor $gestor, Anexo $anexo)
    {
        if ($anexo->anexavel_id !== $gestor->id || $anexo->anexavel_type !== Gestor::class) {
            abort(403, 'Acesso negado.');
        }

        if ($anexo->arquivo && Storage::disk('public')->exists($anexo->arquivo)) {
            Storage::disk('public')->delete($anexo->arquivo);
        }

        $anexo->delete();

        return back()->with('success', 'Anexo excluído com sucesso.');
    }

    public function ativarAnexo(Gestor $gestor, Anexo $anexo)
    {
        if ($anexo->anexavel_type !== Gestor::class || $anexo->anexavel_id !== $gestor->id) {
            abort(403, 'Anexo não pertence a este gestor.');
        }

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

        return back()->with('success', 'Contrato/aditivo ativado e percentual aplicado.');
    }

    public function vincularDistribuidores(Request $request)
    {
        $gestores = Gestor::orderBy('razao_social')->get(['id','razao_social']);

        $busca = trim((string) $request->input('busca'));
        $gestorFiltro = $request->integer('gestor');

        $query = Distribuidor::query()
            ->with(['gestor:id,razao_social'])
            ->orderBy('razao_social');

        if ($busca !== '') {
            $driver = DB::connection()->getDriverName(); // 'pgsql', 'mysql', etc.
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

    public function storeVinculo(Request $request)
    {
        $vinculos = (array) $request->input('vinculos', []);

        $idsDistribuidores = collect(array_keys($vinculos))
            ->map(fn($id) => (int) $id)
            ->filter()
            ->values()
            ->all();

        if (empty($idsDistribuidores)) {
            return back()->with('info', 'Nenhuma alteração enviada.');
        }

        $existem = Distribuidor::whereIn('id', $idsDistribuidores)->count();
        if ($existem !== count($idsDistribuidores)) {
            return back()->with('error', 'Há distribuidores inválidos.')->withInput();
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
                return back()->with('error', 'Há gestor inválido.')->withInput();
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

        return back()->with(
            $alterados ? 'success' : 'info',
            $alterados ? "{$alterados} vínculo(s) atualizado(s)!" : 'Nada para atualizar.'
        );
    }

    /** JSON: retorna as UFs do gestor (para filtrar selects no front) */
    public function ufs(Gestor $gestor)
    {
        $ufs = Cache::remember("gestor:{$gestor->id}:ufs", 600, function() use ($gestor) {
            return $gestor->ufs()->pluck('uf')->map(fn($u)=>strtoupper($u))->values()->all();
        });

        return response()->json($ufs);
    }

    /** (Opcional) manter se você usa essa rota em algum lugar */
    public function cidadesPorGestor(Gestor $gestor)
    {
        // ajuste conforme seu domínio; aqui devolve apenas as UFs (placeholder)
        return response()->json($gestor->ufs()->pluck('uf')->values());
    }

    /** NOVO ROBUSTO: cidades por UFs (tenta detectar colunas reais da sua tabela) */
    public function cidadesPorUfs(Request $request)
    {
        $ufs = collect(explode(',', (string)$request->query('ufs', '')))
            ->map(fn($u) => strtoupper(trim($u)))
            ->filter(fn($u) => in_array($u, $this->UFs, true))
            ->unique()
            ->values()
            ->all();

        if (empty($ufs)) {
            return response()->json([]);
        }

        // Detecta nomes de colunas comuns
        $table = (new City)->getTable(); // normalmente "cities"
        $hasNome   = Schema::hasColumn($table, 'nome')   ? 'nome'   : (Schema::hasColumn($table, 'name') ? 'name' : null);
        $hasUf     = Schema::hasColumn($table, 'uf')     ? 'uf'     : (Schema::hasColumn($table, 'estado_uf') ? 'estado_uf' : (Schema::hasColumn($table, 'state') ? 'state' : null));
        $hasId     = Schema::hasColumn($table, 'id')     ? 'id'     : null;

        if (!$hasNome || !$hasUf || !$hasId) {
            // Se sua tabela tiver nomes muito diferentes, ajuste aqui.
            return response()->json([], 200);
        }

        $cidades = City::query()
            ->whereIn($hasUf, $ufs)
            ->orderBy($hasUf)
            ->orderBy($hasNome)
            ->get([$hasId.' as id', $hasNome.' as nome', $hasUf.' as uf']);

        return response()->json($cidades->map(fn($c) => [
            'id'   => $c->id,
            'text' => "{$c->nome} ({$c->uf})",
            'uf'   => $c->uf,
        ]));
    }

    /** Normaliza arrays de telefones e emails (remove vazios e reindexa) */
    private function normalizePhonesAndEmails($telefones, $emails): array
    {
        $tels = is_array($telefones) ? $telefones : [];
        $tels = array_values(array_filter(array_map(fn($t)=>trim((string)$t), $tels), fn($t)=>$t!==''));

        $mails = is_array($emails) ? $emails : [];
        $mails = array_values(array_filter(array_map(fn($e)=>trim((string)$e), $mails), fn($e)=>$e!==''));

        return [$tels, $mails];
    }

    /** Sincroniza as UFs de atuação do gestor */
    private function syncUfs(Gestor $gestor, array $ufsInput): void
    {
        $novas = collect($ufsInput)
            ->map(fn($u)=>strtoupper(trim((string)$u)))
            ->filter(fn($u)=>in_array($u, $this->UFs, true))
            ->unique()
            ->values();

        // coleção atual: id => uf
        $atuais = $gestor->ufs()->get()->pluck('uf','id');

        // ids a manter
        $manterIds = [];

        foreach ($atuais as $id => $ufAtual) {
            if ($novas->contains($ufAtual)) {
                $manterIds[] = $id;
                // remove da lista de novas (já existe)
                $novas = $novas->reject(fn($u) => $u === $ufAtual)->values();
            }
        }

        // apaga as que saíram
        if (!empty($manterIds)) {
            $gestor->ufs()->whereNotIn('id', $manterIds)->delete();
        } else {
            $gestor->ufs()->delete();
        }

        // cria as remanescentes
        if ($novas->isNotEmpty()) {
            $gestor->ufs()->createMany($novas->map(fn($u)=>['uf'=>$u])->all());
        }
         Cache::forget("gestor:{$gestor->id}:ufs");
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
}
