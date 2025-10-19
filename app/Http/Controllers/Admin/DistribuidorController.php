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
use Illuminate\Support\Facades\Schema; // <<<<<< importante

class DistribuidorController extends Controller
{
    public function index()
    {
        $distribuidores = Distribuidor::with(['user','gestor'])->latest()->paginate(20);
        return view('admin.distribuidores.index', compact('distribuidores'));
    }

    public function create()
    {
        $gestores = Gestor::orderBy('razao_social')->get(['id','razao_social']);
        return view('admin.distribuidores.create', compact('gestores'));
    }

    public function store(Request $request)
    {
        // normaliza listas
        $emailsReq    = collect($request->input('emails', []))
                            ->map(fn($e) => trim((string)$e))
                            ->filter(fn($e) => $e !== '')
                            ->values();
        $telefonesReq = collect($request->input('telefones', []))
                            ->map(fn($t) => preg_replace('/\D+/', '', (string)$t))
                            ->filter(fn($t) => $t !== '')
                            ->values();

        if (!$request->filled('email') && $emailsReq->isNotEmpty()) {
            $request->merge(['email' => $emailsReq->first()]);
        }

        $data = $request->validate([
            // vínculo
            'gestor_id'           => ['required','exists:gestores,id'],

            // credenciais (opcionais) - email do USER (login)
            'email'               => ['nullable','email','max:255','unique:users,email'],
            'password'            => ['nullable','string','min:8'],

            // dados cadastrais
            'razao_social'        => ['required','string','max:255'],
            'cnpj'                => ['nullable','string','max:18'],
            'representante_legal' => ['nullable','string','max:255'],
            'cpf'                 => ['nullable','string','max:14'],
            'rg'                  => ['nullable','string','max:30'],

            // listas
            'emails'              => ['nullable','array'],
            'emails.*'            => ['nullable','email','max:255'],
            'telefones'           => ['nullable','array'],
            'telefones.*'         => ['nullable','string','max:30'],

            // endereço principal
            'endereco'            => ['nullable','string','max:255'],
            'numero'              => ['nullable','string','max:20'],
            'complemento'         => ['nullable','string','max:100'],
            'bairro'              => ['nullable','string','max:100'],
            'cidade'              => ['nullable','string','max:100'],
            'uf'                  => ['nullable','string','size:2'],
            'cep'                 => ['nullable','string','max:9'],

            // endereço secundário
            'endereco2'           => ['nullable','string','max:255'],
            'numero2'             => ['nullable','string','max:20'],
            'complemento2'        => ['nullable','string','max:100'],
            'bairro2'             => ['nullable','string','max:100'],
            'cidade2'             => ['nullable','string','max:100'],
            'uf2'                 => ['nullable','string','size:2'],
            'cep2'                => ['nullable','string','max:9'],

            // cidades de atuação
            'uf_cidades'          => ['nullable','string','size:2'],
            'cities'              => ['nullable','array'],
            'cities.*'            => ['integer','exists:cities,id'],

            // comerciais
            'percentual_vendas'   => ['nullable','numeric','min:0','max:100'],

            // ANEXOS
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

        // >>>>> Validação extra: TODAS as cidades devem estar nas UFs do gestor escolhido
        $gestorUfs = DB::table('gestor_ufs')
            ->where('gestor_id', $data['gestor_id'])
            ->pluck('uf')
            ->map(fn($u)=>strtoupper($u))
            ->all();

        $cityIds = collect($data['cities'] ?? [])->map(fn($i)=>(int)$i)->unique()->values();

        if ($cityIds->isNotEmpty()) {
            $ufCol = $this->cityUfColumn(); // <<< detecção automática da coluna de UF
            $qCidades = DB::table('cities')
                ->whereIn('id', $cityIds)
                ->select('id','name');

            if ($ufCol) {
                $qCidades->addSelect($ufCol.' as uf');
            } else {
                // Se nenhuma coluna de UF existir, não dá para validar contra gestorUfs.
                $qCidades->addSelect(DB::raw('NULL as uf'));
            }

            $cidades = $qCidades->get();

            if ($ufCol) {
                $fora = $cidades->filter(fn($c) => !in_array(strtoupper((string)$c->uf), $gestorUfs, true));
                if ($fora->isNotEmpty()) {
                    $lista = $fora->map(fn($c)=>"{$c->name} (".($c->uf ?? '?').")")->implode(', ');
                    throw ValidationException::withMessages([
                        'cities' => ["As cidades selecionadas devem estar nas UFs do gestor. Fora do escopo: {$lista}."]
                    ]);
                }
            }

            $ocupadas = DB::table('city_distribuidor')
                ->join('distribuidores','distribuidores.id','=','city_distribuidor.distribuidor_id')
                ->join('cities','cities.id','=','city_distribuidor.city_id')
                ->whereIn('city_distribuidor.city_id', $cityIds)
                ->select('cities.id','cities.name','distribuidores.razao_social as distribuidor')
                ->get();

            if ($ocupadas->isNotEmpty()) {
                $msgs = $ocupadas->map(fn($o) => "{$o->name} (ocupada por {$o->distribuidor})")->implode(', ');
                throw ValidationException::withMessages([
                    'cities' => ["Algumas cidades já estão ocupadas: {$msgs}."]
                ]);
            }
        }

        // Derivar contrato_assinado a partir dos anexos enviados
        $temAssinado = false;
        if (!empty($data['contratos']) && is_array($data['contratos'])) {
            foreach ($data['contratos'] as $meta) {
                if (!empty($meta['assinado'])) { $temAssinado = true; break; }
            }
        }

        $distribuidor = DB::transaction(function () use ($data, $request, $cityIds, $emailsReq, $telefonesReq, $temAssinado) {
            // e-mail/senha opcionais (placeholder se vazio)
            $userEmail = trim((string)($data['email'] ?? ''));
            $userPass  = (string)($data['password'] ?? '');

            if ($userEmail === '') $userEmail = 'distribuidor+'.Str::uuid().'@placeholder.local';
            if ($userPass  === '') $userPass  = Str::random(12);

            // USER
            /** @var \App\Models\User $user */
            $user = User::create([
                'name'     => $data['razao_social'],
                'email'    => $userEmail,
                'password' => Hash::make($userPass),
            ]);
            if (method_exists($user, 'assignRole')) {
                $user->assignRole('distribuidor');
            }

            $distribuidor = Distribuidor::create([
                'user_id'             => $user->id,
                'gestor_id'           => $data['gestor_id'],

                'razao_social'        => $data['razao_social'],
                'cnpj'                => $data['cnpj'],
                'representante_legal' => $data['representante_legal'],
                'cpf'                 => $data['cpf'],
                'rg'                  => $data['rg'] ?? null,

                'emails'              => $emailsReq->isNotEmpty() ? $emailsReq->all() : null,
                'telefones'           => $telefonesReq->isNotEmpty() ? $telefonesReq->all() : null,

                // Endereço principal
                'endereco'            => $data['endereco'] ?? null,
                'numero'              => $data['numero'] ?? null,
                'complemento'         => $data['complemento'] ?? null,
                'bairro'              => $data['bairro'] ?? null,
                'cidade'              => $data['cidade'] ?? null,
                'uf'                  => $data['uf'] ?? null,
                'cep'                 => $data['cep'] ?? null,

                // Endereço secundário
                'endereco2'           => $data['endereco2'] ?? null,
                'numero2'             => $data['numero2'] ?? null,
                'complemento2'        => $data['complemento2'] ?? null,
                'bairro2'             => $data['bairro2'] ?? null,
                'cidade2'             => $data['cidade2'] ?? null,
                'uf2'                 => $data['uf2'] ?? null,
                'cep2'                => $data['cep2'] ?? null,

                'percentual_vendas'   => $data['percentual_vendas'],
                'vencimento_contrato' => null, // definido por anexo ativo
                'contrato_assinado'   => $temAssinado,
            ]);

            // Cidades
            if ($cityIds->isNotEmpty()) {
                $distribuidor->cities()->attach($cityIds->all());
            }

            // Anexos (com percentual/ativo/datas)
            if (!empty($data['contratos']) && is_array($data['contratos'])) {
                $idAtivoEscolhido = null;

                foreach ($data['contratos'] as $idx => $meta) {
                    $file = $request->file("contratos.$idx.arquivo");
                    if (!$file) continue;

                    $path   = $file->store("distribuidores/{$distribuidor->id}", 'public');
                    $ativo  = !empty($meta['ativo']);

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

                // no máx 1 ativo
                if ($distribuidor->anexos()->where('ativo', true)->count() > 1) {
                    $distribuidor->anexos()->where('ativo', true)
                        ->where('id', '<>', $idAtivoEscolhido)
                        ->update(['ativo' => false]);
                }

                // aplica percentual/vencimento do ativo
                $ativo = $distribuidor->anexos()->where('ativo', true)->latest('id')->first();
                if ($ativo) {
                    $payload = [];
                    if ($ativo->percentual_vendas !== null) {
                        $payload['percentual_vendas'] = $ativo->percentual_vendas;
                    }
                    if ($ativo->data_vencimento) {
                        $payload['vencimento_contrato'] = $ativo->data_vencimento;
                    }
                    if (!empty($payload)) $distribuidor->update($payload);
                }
            }

            return $distribuidor;
        });

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

    public function update(Request $request, Distribuidor $distribuidor)
    {
        // normaliza listas
        $emailsReq    = collect($request->input('emails', []))
                            ->map(fn($e) => trim((string)$e))
                            ->filter(fn($e) => $e !== '')
                            ->values();
        $telefonesReq = collect($request->input('telefones', []))
                            ->map(fn($t) => preg_replace('/\D+/', '', (string)$t))
                            ->filter(fn($t) => $t !== '')
                            ->values();

        if (!$request->filled('email') && $emailsReq->isNotEmpty()) {
            $request->merge(['email' => $emailsReq->first()]);
        }

        $data = $request->validate([
            'gestor_id'           => ['required','exists:gestores,id'],

            // USER (login)
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

            // endereço principal
            'endereco'            => ['nullable','string','max:255'],
            'numero'              => ['nullable','string','max:20'],
            'complemento'         => ['nullable','string','max:100'],
            'bairro'              => ['nullable','string','max:100'],
            'cidade'              => ['nullable','string','max:100'],
            'uf'                  => ['nullable','string','size:2'],
            'cep'                 => ['nullable','string','max:9'],

            // endereço secundário
            'endereco2'           => ['nullable','string','max:255'],
            'numero2'             => ['nullable','string','max:20'],
            'complemento2'        => ['nullable','string','max:100'],
            'bairro2'             => ['nullable','string','max:100'],
            'cidade2'             => ['nullable','string','max:100'],
            'uf2'                 => ['nullable','string','size:2'],
            'cep2'                => ['nullable','string','max:9'],

            // cidades
            'uf_cidades'          => ['nullable','string','size:2'],
            'cities'              => ['nullable','array'],
            'cities.*'            => ['integer','exists:cities,id'],

            'percentual_vendas'   => ['required','numeric','min:0','max:100'],

            // anexos novos (append)
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

        // >>>>> Validação extra: cidades x UFs do gestor
        $gestorUfs = DB::table('gestor_ufs')
            ->where('gestor_id', $data['gestor_id'])
            ->pluck('uf')
            ->map(fn($u)=>strtoupper($u))
            ->all();

        $cityIds = collect($data['cities'] ?? [])->map(fn($i)=>(int)$i)->unique()->values();

        if ($cityIds->isNotEmpty()) {
            $ufCol = $this->cityUfColumn();
            $qCidades = DB::table('cities')
                ->whereIn('id', $cityIds)
                ->select('id','name');

            if ($ufCol) {
                $qCidades->addSelect($ufCol.' as uf');
            } else {
                $qCidades->addSelect(DB::raw('NULL as uf'));
            }

            $cidades = $qCidades->get();

            if ($ufCol) {
                $fora = $cidades->filter(fn($c) => !in_array(strtoupper((string)$c->uf), $gestorUfs, true));
                if ($fora->isNotEmpty()) {
                    $lista = $fora->map(fn($c)=>"{$c->name} (".($c->uf ?? '?').")")->implode(', ');
                    throw ValidationException::withMessages([
                        'cities' => ["As cidades selecionadas devem estar nas UFs do gestor. Fora do escopo: {$lista}."]
                    ]);
                }
            }

            // ocupação (excluindo o próprio distribuidor)
            $ocupadas = DB::table('city_distribuidor')
                ->join('distribuidores','distribuidores.id','=','city_distribuidor.distribuidor_id')
                ->join('cities','cities.id','=','city_distribuidor.city_id')
                ->whereIn('city_distribuidor.city_id', $cityIds)
                ->where('city_distribuidor.distribuidor_id','<>',$distribuidor->id)
                ->select('cities.id','cities.name','distribuidores.razao_social as distribuidor')
                ->get();

            if ($ocupadas->isNotEmpty()) {
                $msgs = $ocupadas->map(fn($o) => "{$o->name} (ocupada por {$o->distribuidor})")->implode(', ');
                throw ValidationException::withMessages([
                    'cities' => ["Algumas cidades já estão ocupadas: {$msgs}."]
                ]);
            }
        }

        DB::transaction(function () use ($data, $request, $distribuidor, $cityIds, $emailsReq, $telefonesReq) {
            // USER
            $user = $distribuidor->user;
            if (!empty($data['email']))    $user->email    = $data['email'];
            if (!empty($data['password'])) $user->password = Hash::make($data['password']);
            if (!empty($data['email']) || !empty($data['password'])) $user->save();

            // DISTRIBUIDOR
            $distribuidor->update([
                'gestor_id'           => $data['gestor_id'],

                'razao_social'        => $data['razao_social'],
                'cnpj'                => $data['cnpj'],
                'representante_legal' => $data['representante_legal'],
                'cpf'                 => $data['cpf'],
                'rg'                  => $data['rg'] ?? null,

                'emails'              => $emailsReq->isNotEmpty() ? $emailsReq->all() : null,
                'telefones'           => $telefonesReq->isNotEmpty() ? $telefonesReq->all() : null,

                // Endereço principal
                'endereco'            => $data['endereco'] ?? null,
                'numero'              => $data['numero'] ?? null,
                'complemento'         => $data['complemento'] ?? null,
                'bairro'              => $data['bairro'] ?? null,
                'cidade'              => $data['cidade'] ?? null,
                'uf'                  => $data['uf'] ?? null,
                'cep'                 => $data['cep'] ?? null,

                // Endereço secundário
                'endereco2'           => $data['endereco2'] ?? null,
                'numero2'             => $data['numero2'] ?? null,
                'complemento2'        => $data['complemento2'] ?? null,
                'bairro2'             => $data['bairro2'] ?? null,
                'cidade2'             => $data['cidade2'] ?? null,
                'uf2'                 => $data['uf2'] ?? null,
                'cep2'                => $data['cep2'] ?? null,

                'percentual_vendas'   => $data['percentual_vendas'],
            ]);

            // Cities: sincroniza
            $distribuidor->cities()->sync($cityIds->all());

            // Anexos (novos - append)
            if (!empty($data['contratos']) && is_array($data['contratos'])) {
                $idAtivoEscolhido = null;

                foreach ($data['contratos'] as $idx => $meta) {
                    $file = $request->file("contratos.$idx.arquivo");
                    if (!$file) continue;

                    $path   = $file->store("distribuidores/{$distribuidor->id}", 'public');
                    $ativo  = !empty($meta['ativo']);

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

                // no máx 1 ativo
                if ($distribuidor->anexos()->where('ativo', true)->count() > 1) {
                    $distribuidor->anexos()->where('ativo', true)
                        ->where('id', '<>', $idAtivoEscolhido)
                        ->update(['ativo' => false]);
                }
            }

            // contrato_assinado (derivado)
            $temAssinadoAgora = $distribuidor->anexos()->where('assinado', true)->exists();
            if ($distribuidor->contrato_assinado !== $temAssinadoAgora) {
                $distribuidor->update(['contrato_assinado' => $temAssinadoAgora]);
            }

            // aplica percentual/vencimento do ativo
            $ativo = $distribuidor->anexos()->where('ativo', true)->latest('id')->first();
            if ($ativo) {
                $payload = [];
                if ($ativo->percentual_vendas !== null) {
                    $payload['percentual_vendas'] = $ativo->percentual_vendas;
                }
                if ($ativo->data_vencimento) {
                    $payload['vencimento_contrato'] = $ativo->data_vencimento;
                }
                if (!empty($payload)) $distribuidor->update($payload);
            }
        });

        return redirect()
            ->route('admin.distribuidores.index')
            ->with('success', 'Distribuidor atualizado com sucesso!');
    }

    public function destroy(Distribuidor $distribuidor)
    {
        DB::transaction(function () use ($distribuidor) {
            $distribuidor->anexos()->delete();
            $distribuidor->cities()->detach();
            $distribuidor->delete();
        });

        return redirect()
            ->route('admin.distribuidores.index')
            ->with('success', 'Distribuidor removido com sucesso!');
    }

    public function destroyAnexo(Distribuidor $distribuidor, Anexo $anexo)
    {
        if ($anexo->anexavel_id !== $distribuidor->id || $anexo->anexavel_type !== Distribuidor::class) {
            abort(403, 'Acesso negado.');
        }

        if ($anexo->arquivo && Storage::disk('public')->exists($anexo->arquivo)) {
            Storage::disk('public')->delete($anexo->arquivo);
        }

        $anexo->delete();

        return back()->with('success', 'Anexo excluído com sucesso.');
    }

    public function ativarAnexo(Distribuidor $distribuidor, Anexo $anexo)
    {
        if ($anexo->anexavel_type !== Distribuidor::class || $anexo->anexavel_id !== $distribuidor->id) {
            abort(403, 'Anexo não pertence a este distribuidor.');
        }

        DB::transaction(function () use ($distribuidor, $anexo) {
            $distribuidor->anexos()->where('ativo', true)->update(['ativo' => false]);
            $anexo->update(['ativo' => true]);

            $payload = [];
            if ($anexo->percentual_vendas !== null) {
                $payload['percentual_vendas'] = $anexo->percentual_vendas;
            }
            if ($anexo->data_vencimento) {
                $payload['vencimento_contrato'] = $anexo->data_vencimento;
            }
            if (!empty($payload)) {
                $distribuidor->update($payload);
            }
        });

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

}
