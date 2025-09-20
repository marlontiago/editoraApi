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
        // Remove linhas totalmente vazias de "contatos" antes da validação
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
            // vínculo
            'gestor_id'           => ['required','exists:gestores,id'],

            // credenciais (OPCIONAIS)
            'email'               => ['nullable','email','max:255','unique:users,email'],
            'password'            => ['nullable','string','min:8'],

            // dados cadastrais
            'razao_social'        => ['required','string','max:255'],
            'cnpj'                => ['required','string','max:18'],
            'representante_legal' => ['required','string','max:255'],
            'cpf'                 => ['required','string','max:14'],
            'rg'                  => ['nullable','string','max:30'],
            'telefone'            => ['nullable','string','max:20'],

            // endereço
            'endereco'            => ['nullable','string','max:255'],
            'numero'              => ['nullable','string','max:20'],
            'complemento'         => ['nullable','string','max:100'],
            'bairro'              => ['nullable','string','max:100'],
            'cidade'              => ['nullable','string','max:100'],
            'uf'                  => ['nullable','string','size:2'],
            'cep'                 => ['nullable','string','max:9'],

            // cidades de atuação
            'uf_cidades'          => ['nullable','string','size:2'],
            'cities'              => ['nullable','array'],
            'cities.*'            => ['integer','exists:cities,id'],

            // comerciais
            'percentual_vendas'   => ['required','numeric','min:0','max:100'],

            // ANEXOS com os mesmos campos do Gestor
            'contratos'                       => ['nullable','array'],
            'contratos.*.tipo'                => ['required_with:contratos.*.arquivo','in:contrato,aditivo,outro'],
            'contratos.*.arquivo'             => ['nullable','file','mimes:pdf','max:5120'],
            'contratos.*.descricao'           => ['nullable','string','max:255'],
            'contratos.*.assinado'            => ['nullable','boolean'],
            'contratos.*.percentual_vendas'   => ['nullable','numeric','min:0','max:100'],
            'contratos.*.ativo'               => ['nullable','boolean'],
            'contratos.*.data_assinatura'     => ['nullable','date'],
            'contratos.*.validade_meses'      => ['nullable','integer','min:1','max:120'],

            // contatos
            'contatos'                 => ['nullable','array'],
            'contatos.*.id'            => ['nullable','integer'],
            'contatos.*.nome'          => ['required_with:contatos.*.email,contatos.*.telefone,contatos.*.whatsapp,contatos.*.cargo,contatos.*.tipo,contatos.*.observacoes','nullable','string','max:255'],
            'contatos.*.email'         => ['nullable','email','max:255'],
            'contatos.*.telefone'      => ['nullable','string','max:30'],
            'contatos.*.whatsapp'      => ['nullable','string','max:30'],
            'contatos.*.cargo'         => ['nullable','string','max:100'],
            'contatos.*.tipo'          => ['nullable','in:principal,secundario,financeiro,comercial,outro'],
            'contatos.*.preferencial'  => ['nullable','boolean'],
            'contatos.*.observacoes'   => ['nullable','string','max:500'],
        ]);

        // Verifica ocupação das cidades (se enviadas)
        $cityIds = collect($data['cities'] ?? [])->map(fn($i)=>(int)$i)->unique()->values();
        if ($cityIds->isNotEmpty()) {
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

        // Contatos: máximo 1 preferencial
        $preferenciais = collect($data['contatos'] ?? [])->where('preferencial', true)->count();
        if ($preferenciais > 1) {
            throw ValidationException::withMessages([
                'contatos' => ['Selecione no máximo um contato como preferencial.'],
            ]);
        }

        $contatosSan = collect($data['contatos'] ?? [])
            ->filter(function($c){
                return trim((string)($c['nome'] ?? '')) !== '' ||
                       trim((string)($c['email'] ?? '')) !== '' ||
                       trim((string)($c['telefone'] ?? '')) !== '' ||
                       trim((string)($c['whatsapp'] ?? '')) !== '' ||
                       trim((string)($c['cargo'] ?? '')) !== '' ||
                       trim((string)($c['observacoes'] ?? '')) !== '';
            })
            ->map(function($c){
                $c['telefone'] = isset($c['telefone']) ? preg_replace('/\D+/', '', $c['telefone']) : null;
                $c['whatsapp'] = isset($c['whatsapp']) ? preg_replace('/\D+/', '', $c['whatsapp']) : null;
                $c['preferencial'] = !empty($c['preferencial']);
                $c['tipo'] = $c['tipo'] ?? 'outro';
                return $c;
            })
            ->values();

        // Derivar contrato_assinado a partir dos anexos enviados
        $temAssinado = false;
        if (!empty($data['contratos']) && is_array($data['contratos'])) {
            foreach ($data['contratos'] as $meta) {
                if (!empty($meta['assinado'])) { $temAssinado = true; break; }
            }
        }

        $distribuidor = DB::transaction(function () use ($data, $request, $cityIds, $contatosSan, $temAssinado) {
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

            // DISTRIBUIDOR
            /** @var \App\Models\Distribuidor $distribuidor */
            $distribuidor = Distribuidor::create([
                'user_id'             => $user->id,
                'gestor_id'           => $data['gestor_id'],

                'razao_social'        => $data['razao_social'],
                'cnpj'                => $data['cnpj'],
                'representante_legal' => $data['representante_legal'],
                'cpf'                 => $data['cpf'],
                'rg'                  => $data['rg'],
                'telefone'            => $data['telefone'] ?? null,

                'endereco'            => $data['endereco'] ?? null,
                'numero'              => $data['numero'] ?? null,
                'complemento'         => $data['complemento'] ?? null,
                'bairro'              => $data['bairro'] ?? null,
                'cidade'              => $data['cidade'] ?? null,
                'uf'                  => $data['uf'] ?? null,
                'cep'                 => $data['cep'] ?? null,

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

            // Contatos
            if ($contatosSan->isNotEmpty()) {
                foreach ($contatosSan as $c) {
                    $distribuidor->contatos()->create([
                        'nome'         => $c['nome'] ?? null,
                        'email'        => $c['email'] ?? null,
                        'telefone'     => $c['telefone'] ?? null,
                        'whatsapp'     => $c['whatsapp'] ?? null,
                        'cargo'        => $c['cargo'] ?? null,
                        'tipo'         => $c['tipo'] ?? 'outro',
                        'preferencial' => !empty($c['preferencial']),
                        'observacoes'  => $c['observacoes'] ?? null,
                    ]);
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
        $distribuidor->load(['user','gestor','cities','anexos','contatos']);
        return view('admin.distribuidores.show', compact('distribuidor'));
    }

    public function edit(Distribuidor $distribuidor)
    {
        $distribuidor->load(['user','gestor','cities','anexos','contatos']);
        $gestores = Gestor::orderBy('razao_social')->get(['id','razao_social']);
        return view('admin.distribuidores.edit', compact('distribuidor','gestores'));
    }

    public function update(Request $request, Distribuidor $distribuidor)
    {
        // Remove linhas totalmente vazias de "contatos" antes da validação
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
            'gestor_id'           => ['required','exists:gestores,id'],

            // e-mail/senha opcionais
            'email'               => ['nullable','email','max:255','unique:users,email,'.$distribuidor->user_id],
            'password'            => ['nullable','string','min:8'],

            'razao_social'        => ['required','string','max:255'],
            'cnpj'                => ['required','string','max:18'],
            'representante_legal' => ['required','string','max:255'],
            'cpf'                 => ['required','string','max:14'],
            'rg'                  => ['nullable','string','max:30'],
            'telefone'            => ['nullable','string','max:20'],

            'endereco'            => ['nullable','string','max:255'],
            'numero'              => ['nullable','string','max:20'],
            'complemento'         => ['nullable','string','max:100'],
            'bairro'              => ['nullable','string','max:100'],
            'cidade'              => ['nullable','string','max:100'],
            'uf'                  => ['nullable','string','size:2'],
            'cep'                 => ['nullable','string','max:9'],

            'uf_cidades'          => ['nullable','string','size:2'],
            'cities'              => ['nullable','array'],
            'cities.*'            => ['integer','exists:cities,id'],

            'percentual_vendas'   => ['required','numeric','min:0','max:100'],

            // anexos (novos - append)
            'contratos'                       => ['nullable','array'],
            'contratos.*.tipo'                => ['required_with:contratos.*.arquivo','in:contrato,aditivo,outro'],
            'contratos.*.arquivo'             => ['nullable','file','mimes:pdf','max:5120'],
            'contratos.*.descricao'           => ['nullable','string','max:255'],
            'contratos.*.assinado'            => ['nullable','boolean'],
            'contratos.*.percentual_vendas'   => ['nullable','numeric','min:0','max:100'],
            'contratos.*.ativo'               => ['nullable','boolean'],
            'contratos.*.data_assinatura'     => ['nullable','date'],
            'contratos.*.validade_meses'      => ['nullable','integer','min:1','max:120'],

            // contatos
            'contatos'                 => ['nullable','array'],
            'contatos.*.id'            => ['nullable','integer','exists:contatos,id'],
            'contatos.*.nome'          => ['required_with:contatos.*.email,contatos.*.telefone,contatos.*.whatsapp,contatos.*.cargo,contatos.*.tipo,contatos.*.observacoes','nullable','string','max:255'],
            'contatos.*.email'         => ['nullable','email','max:255'],
            'contatos.*.telefone'      => ['nullable','string','max:30'],
            'contatos.*.whatsapp'      => ['nullable','string','max:30'],
            'contatos.*.cargo'         => ['nullable','string','max:100'],
            'contatos.*.tipo'          => ['nullable','in:principal,secundario,financeiro,comercial,outro'],
            'contatos.*.preferencial'  => ['nullable','boolean'],
            'contatos.*.observacoes'   => ['nullable','string','max:500'],
        ]);

        // Verificar ocupação de cidades (excluindo o próprio distribuidor)
        $cityIds = collect($data['cities'] ?? [])->map(fn($i)=>(int)$i)->unique()->values();
        if ($cityIds->isNotEmpty()) {
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

        // Contatos: máximo 1 preferencial
        $preferenciais = collect($data['contatos'] ?? [])->where('preferencial', true)->count();
        if ($preferenciais > 1) {
            throw ValidationException::withMessages([
                'contatos' => ['Selecione no máximo um contato como preferencial.'],
            ]);
        }

        $contatosSan = collect($data['contatos'] ?? [])
            ->filter(function($c){
                return trim((string)($c['nome'] ?? '')) !== '' ||
                       trim((string)($c['email'] ?? '')) !== '' ||
                       trim((string)($c['telefone'] ?? '')) !== '' ||
                       trim((string)($c['whatsapp'] ?? '')) !== '' ||
                       trim((string)($c['cargo'] ?? '')) !== '' ||
                       trim((string)($c['observacoes'] ?? '')) !== '' ||
                       !empty($c['id']);
            })
            ->map(function($c){
                $c['telefone'] = isset($c['telefone']) ? preg_replace('/\D+/', '', $c['telefone']) : null;
                $c['whatsapp'] = isset($c['whatsapp']) ? preg_replace('/\D+/', '', $c['whatsapp']) : null;
                $c['preferencial'] = !empty($c['preferencial']);
                $c['tipo'] = $c['tipo'] ?? 'outro';
                return $c;
            })
            ->values();

        DB::transaction(function () use ($data, $request, $distribuidor, $cityIds, $contatosSan) {
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
                'rg'                  => $data['rg'],
                'telefone'            => $data['telefone'] ?? null,

                'endereco'            => $data['endereco'] ?? null,
                'numero'              => $data['numero'] ?? null,
                'complemento'         => $data['complemento'] ?? null,
                'bairro'              => $data['bairro'] ?? null,
                'cidade'              => $data['cidade'] ?? null,
                'uf'                  => $data['uf'] ?? null,
                'cep'                 => $data['cep'] ?? null,

                'percentual_vendas'   => $data['percentual_vendas'],
                // 'vencimento_contrato' permanece como está; será ajustado pelo anexo ativo abaixo
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
            $distribuidor->contatos()->delete();
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

    /**
     * Marcar um anexo do distribuidor como ATIVO e aplicar seu percentual/vencimento.
     */
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
}
