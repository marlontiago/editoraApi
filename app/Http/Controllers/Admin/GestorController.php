<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Validation\ValidationException;
use App\Http\Controllers\Controller;
use App\Models\Gestor;
use App\Models\User;
use App\Models\Anexo;
use App\Models\Distribuidor;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class GestorController extends Controller
{
    public function index()
    {
        $gestores = Gestor::with('user')->latest()->paginate(20);
        return view('admin.gestores.index', compact('gestores'));
    }

    public function create()
    {
        return view('admin.gestores.create');
    }

    public function store(Request $request)
    {
        // Remove linhas vazias de contatos
        $rawContatos = $request->input('contatos', []);
        $contatosSan = collect($rawContatos)->filter(function ($c) {
            return trim($c['nome'] ?? '') !== ''
                || trim($c['email'] ?? '') !== ''
                || trim($c['telefone'] ?? '') !== ''
                || trim($c['whatsapp'] ?? '') !== ''
                || trim($c['cargo'] ?? '') !== ''
                || !empty($c['preferencial']);
        })->values()->all();
        $request->merge(['contatos' => $contatosSan]);

        $data = $request->validate([
            // Gestor
            'razao_social'        => ['required','string','max:255'],
            'cnpj'                => ['required','string','max:18'],
            'representante_legal' => ['required','string','max:255'],
            'cpf'                 => ['required','string','max:14'],
            'rg'                  => ['nullable','string','max:30'],
            'telefone'            => ['nullable','string','max:20'],
            'estado_uf'           => ['nullable','string','size:2'],

            // e-mail/senha OPCIONAIS
            'email'               => ['nullable','email','max:255'],
            'password'            => ['nullable','string','min:8'],

            // Endereço
            'endereco'            => ['nullable','string','max:255'],
            'numero'              => ['nullable','string','max:20'],
            'complemento'         => ['nullable','string','max:100'],
            'bairro'              => ['nullable','string','max:100'],
            'cidade'              => ['nullable','string','max:100'],
            'uf'                  => ['nullable','string','size:2'],
            'cep'                 => ['nullable','string','max:9'],

            // Contratuais
            'percentual_vendas'   => ['nullable','numeric','min:0','max:100'],

            // Anexos (múltiplos)
            'contratos'                       => ['nullable','array'],
            'contratos.*.tipo'                => ['required_with:contratos.*.arquivo','in:contrato,aditivo,outro'],
            'contratos.*.arquivo'             => ['nullable','file','mimes:pdf','max:5120'],
            'contratos.*.descricao'           => ['nullable','string','max:255'],
            'contratos.*.assinado'            => ['nullable','boolean'],
            'contratos.*.percentual_vendas'   => ['nullable','numeric','min:0','max:100'],
            'contratos.*.ativo'               => ['nullable','boolean'],
            'contratos.*.data_assinatura'     => ['nullable','date'],
            'contratos.*.validade_meses'      => ['nullable','integer','min:1','max:120'],

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
        ]);

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
                'estado_uf'           => $data['estado_uf'] ?? null,
                'razao_social'        => $data['razao_social'],
                'cnpj'                => $data['cnpj'],
                'representante_legal' => $data['representante_legal'],
                'cpf'                 => $data['cpf'],
                'rg'                  => $data['rg'] ?? null,
                'telefone'            => $data['telefone'] ?? null,
                'email'               => $data['email'] ?? null,
                'endereco'            => $data['endereco'] ?? null,
                'numero'              => $data['numero'] ?? null,
                'complemento'         => $data['complemento'] ?? null,
                'bairro'              => $data['bairro'] ?? null,
                'cidade'              => $data['cidade'] ?? null,
                'uf'                  => $data['uf'] ?? null,
                'cep'                 => $data['cep'] ?? null,
                'percentual_vendas'   => $data['percentual_vendas'] ?? 0,
                'vencimento_contrato' => null, // será definido pelo anexo ativo
                'contrato_assinado'   => $temAssinado,
            ]);

            // ANEXOS
            if (!empty($data['contratos']) && is_array($data['contratos'])) {
                $idAtivoEscolhido = null;

                foreach ($data['contratos'] as $idx => $meta) {
                    $file = $request->file("contratos.$idx.arquivo");
                    if (!$file) continue;

                    $path   = $file->store("gestores/{$gestor->id}", 'public');
                    $ativo  = !empty($meta['ativo']);

                    // Datas do anexo
                    $inicio = !empty($meta['data_assinatura']) ? Carbon::parse($meta['data_assinatura']) : null;
                    $meses  = !empty($meta['validade_meses']) ? (int)$meta['validade_meses'] : null;
                    $dataVenc = ($inicio && $meses) ? (clone $inicio)->addMonthsNoOverflow($meses) : null;

                    $anexo = $gestor->anexos()->create([
                        'tipo'              => $meta['tipo'] ?? 'contrato',
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

                // Garante no máx 1 ativo
                if ($gestor->anexos()->where('ativo', true)->count() > 1) {
                    $gestor->anexos()->where('ativo', true)
                        ->where('id', '<>', $idAtivoEscolhido)
                        ->update(['ativo' => false]);
                }

                // Aplica percentual e vencimento do anexo ATIVO
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
    }

    public function show(Gestor $gestor)
    {
        $gestor->load('anexos');
        return view('admin.gestores.show', compact('gestor'));
    }

    public function edit(Gestor $gestor)
    {
        $gestor->load('anexos', 'contatos');
        return view('admin.gestores.edit', compact('gestor'));
    }

    public function update(Request $request, Gestor $gestor)
    {
        // Remove linhas vazias de contatos
        $rawContatos = $request->input('contatos', []);
        $contatosSan = collect($rawContatos)->filter(function ($c) {
            return trim($c['nome'] ?? '') !== ''
                || trim($c['email'] ?? '') !== ''
                || trim($c['telefone'] ?? '') !== ''
                || trim($c['whatsapp'] ?? '') !== ''
                || trim($c['cargo'] ?? '') !== ''
                || !empty($c['preferencial']);
        })->values()->all();
        $request->merge(['contatos' => $contatosSan]);

        $data = $request->validate([
            'razao_social'        => ['required','string','max:255'],
            'cnpj'                => ['required','string','max:18'],
            'representante_legal' => ['required','string','max:255'],
            'cpf'                 => ['required','string','max:14'],
            'rg'                  => ['nullable','string','max:30'],
            'telefone'            => ['nullable','string','max:20'],
            'estado_uf'           => ['nullable','string','size:2'],

            // e-mail/senha OPCIONAIS
            'email'               => ['nullable','email','max:255'],
            'password'            => ['nullable','string','min:8'],

            // Endereço
            'endereco'            => ['nullable','string','max:255'],
            'numero'              => ['nullable','string','max:20'],
            'complemento'         => ['nullable','string','max:100'],
            'bairro'              => ['nullable','string','max:100'],
            'cidade'              => ['nullable','string','max:100'],
            'uf'                  => ['nullable','string','size:2'],
            'cep'                 => ['nullable','string','max:9'],

            // Contratuais
            'percentual_vendas'   => ['nullable','numeric','min:0','max:100'],

            // Anexos novos (append)
            'contratos'                       => ['nullable','array'],
            'contratos.*.tipo'                => ['required_with:contratos.*.arquivo','in:contrato,aditivo,outro'],
            'contratos.*.arquivo'             => ['nullable','file','mimes:pdf','max:5120'],
            'contratos.*.descricao'           => ['nullable','string','max:255'],
            'contratos.*.assinado'            => ['nullable','boolean'],
            'contratos.*.percentual_vendas'   => ['nullable','numeric','min:0','max:100'],
            'contratos.*.ativo'               => ['nullable','boolean'],
            'contratos.*.data_assinatura'     => ['nullable','date'],
            'contratos.*.validade_meses'      => ['nullable','integer','min:1','max:120'],

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
        ]);

        // 1 preferencial no máx
        $preferenciais = collect($data['contatos'] ?? [])->where('preferencial', true)->count();
        if ($preferenciais > 1) {
            throw ValidationException::withMessages([
                'contatos' => 'Selecione no máximo um contato como preferencial.'
            ]);
        }

        DB::transaction(function () use ($data, $request, $gestor) {
            // USER
            $user = $gestor->user;
            if (!empty($data['email']))    $user->email    = $data['email'];
            if (!empty($data['password'])) $user->password = Hash::make($data['password']);
            if (!empty($data['email']) || !empty($data['password'])) $user->save();

            // GESTOR (atualiza dados básicos; vencimento será definido após checar anexo ativo)
            $gestor->update([
                'estado_uf'           => $data['estado_uf'] ?? null,
                'razao_social'        => $data['razao_social'],
                'cnpj'                => $data['cnpj'],
                'representante_legal' => $data['representante_legal'],
                'cpf'                 => $data['cpf'],
                'rg'                  => $data['rg'] ?? null,
                'telefone'            => $data['telefone'] ?? null,
                'email'               => $data['email'] ?? $gestor->email,
                'endereco'            => $data['endereco'] ?? null,
                'numero'              => $data['numero'] ?? null,
                'complemento'         => $data['complemento'] ?? null,
                'bairro'              => $data['bairro'] ?? null,
                'cidade'              => $data['cidade'] ?? null,
                'uf'                  => $data['uf'] ?? null,
                'cep'                 => $data['cep'] ?? null,
                'percentual_vendas'   => $data['percentual_vendas'] ?? ($gestor->percentual_vendas ?? 0),
            ]);

            // ANEXOS (append)
            if (!empty($data['contratos']) && is_array($data['contratos'])) {
                $idAtivoEscolhido = null;

                foreach ($data['contratos'] as $idx => $meta) {
                    $file = $request->file("contratos.$idx.arquivo");
                    if (!$file) continue;

                    $path   = $file->store("gestores/{$gestor->id}", 'public');
                    $ativo  = !empty($meta['ativo']);

                    // Datas do anexo
                    $inicio = !empty($meta['data_assinatura']) ? Carbon::parse($meta['data_assinatura']) : null;
                    $meses  = !empty($meta['validade_meses']) ? (int)$meta['validade_meses'] : null;
                    $dataVenc = ($inicio && $meses) ? (clone $inicio)->addMonthsNoOverflow($meses) : null;

                    $anexo = $gestor->anexos()->create([
                        'tipo'              => $meta['tipo'] ?? 'contrato',
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

                // Garante no máx 1 ativo
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

            // Ajusta percentual e vencimento pelo anexo ativo, se houver
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
    }

    public function destroy(Gestor $gestor)
    {
        DB::transaction(function () use ($gestor) {
            $gestor->anexos()->delete();
            $gestor->delete();
        });

        return redirect()
            ->route('admin.gestores.index')
            ->with('success', 'Gestor removido com sucesso!');
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
        if ($anexo->anexavel_id !== $gestor->id || $anexo->anexavel_type !== Gestor::class) {
            abort(403, 'Acesso negado.');
        }

        if ($anexo->arquivo && Storage::disk('public')->exists($anexo->arquivo)) {
            Storage::disk('public')->delete($anexo->arquivo);
        }

        $anexo->delete();

        return back()->with('success', 'Anexo excluído com sucesso.');
    }

    /**
     * Marcar um anexo do gestor como ATIVO e aplicar seu percentual.
     */
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
            // Postgres: ILIKE; (se MySQL, troque por LIKE)
            $query->where(function($q) use ($busca) {
                $q->where('razao_social', 'ILIKE', "%{$busca}%")
                  ->orWhere('cnpj', 'ILIKE', "%{$busca}%")
                  ->orWhere('representante_legal', 'ILIKE', "%{$busca}%");
            });
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

        // valida distribuidores
        $existem = Distribuidor::whereIn('id', $idsDistribuidores)->count();
        if ($existem !== count($idsDistribuidores)) {
            return back()->with('error', 'Há distribuidores inválidos.')->withInput();
        }

        // valida gestores (quando enviados)
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

        // aplica diferenças
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
}
