<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Distribuidor;
use App\Models\Gestor;
use App\Models\User;
use App\Models\City;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

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
            'rg'                  => ['required','string','max:30'],
            'telefone'            => ['nullable','string','max:20'],

            // endereço (mesmo padrão do Gestor)
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

            // contrato por início + validade (calcula vencimento)
            'inicio_contrato'     => ['nullable','date'],
            'validade_meses'      => ['nullable','integer','min:1','max:120'],
            'contrato_assinado'   => ['nullable','boolean'],

            // anexos múltiplos
            'contratos'                   => ['nullable','array'],
            'contratos.*.tipo'            => ['required_with:contratos.*.arquivo','in:contrato,aditivo,outro'],
            'contratos.*.arquivo'         => ['nullable','file','mimes:pdf','max:5120'],
            'contratos.*.descricao'       => ['nullable','string','max:255'],
            'contratos.*.assinado'        => ['nullable','boolean'],
        ]);

        // Calcula vencimento com base em início + meses
        $inicio = !empty($data['inicio_contrato']) ? Carbon::parse($data['inicio_contrato']) : null;
        $meses  = !empty($data['validade_meses']) ? (int) $data['validade_meses'] : null;
        $vencimento = null;
        if ($inicio && $meses) {
            $vencimento = (clone $inicio)->addMonthsNoOverflow($meses);
        }

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

        $distribuidor = DB::transaction(function () use ($data, $request, $vencimento, $cityIds) {

            // 1) Resolver e-mail/senha opcionais (usa placeholder se vazio)
            $userEmail = trim((string)($data['email'] ?? ''));
            $userPass  = (string)($data['password'] ?? '');

            if ($userEmail === '') {
                $userEmail = 'distribuidor+'.Str::uuid().'@placeholder.local';
            }
            if ($userPass === '') {
                $userPass = Str::random(12);
            }

            // 2) Criar USER
            /** @var \App\Models\User $user */
            $user = User::create([
                'name'     => $data['razao_social'],
                'email'    => $userEmail,
                'password' => Hash::make($userPass),
            ]);
            if (method_exists($user, 'assignRole')) {
                $user->assignRole('distribuidor');
            }

            // 3) Criar DISTRIBUIDOR
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

                // endereço
                'endereco'            => $data['endereco'] ?? null,
                'numero'              => $data['numero'] ?? null,
                'complemento'         => $data['complemento'] ?? null,
                'bairro'              => $data['bairro'] ?? null,
                'cidade'              => $data['cidade'] ?? null,
                'uf'                  => $data['uf'] ?? null,
                'cep'                 => $data['cep'] ?? null,

                // comerciais/contratuais
                'percentual_vendas'   => $data['percentual_vendas'],
                'vencimento_contrato' => $vencimento,
                'contrato_assinado'   => !empty($data['contrato_assinado']),
            ]);

            // 4) Cidades (many-to-many) — só attach das livres
            if ($cityIds->isNotEmpty()) {
                $distribuidor->cities()->attach($cityIds->all());
            }

            // 5) Anexos múltiplos
            if (!empty($data['contratos']) && is_array($data['contratos'])) {
                foreach ($data['contratos'] as $idx => $meta) {
                    $file = $request->file("contratos.$idx.arquivo");
                    if (!$file) continue;

                    $path = $file->store("distribuidores/{$distribuidor->id}", 'public');

                    $distribuidor->anexos()->create([
                        'tipo'      => $meta['tipo'] ?? 'contrato',
                        'arquivo'   => $path,
                        'descricao' => $meta['descricao'] ?? null,
                        'assinado'  => !empty($meta['assinado']),
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
        $distribuidor->load(['user','gestor','cities','anexos']);
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
        $data = $request->validate([
            'gestor_id'           => ['required','exists:gestores,id'],

            // e-mail/senha opcionais (só valida unique se informou)
            'email'               => ['nullable','email','max:255','unique:users,email,'.$distribuidor->user_id],
            'password'            => ['nullable','string','min:8'],

            'razao_social'        => ['required','string','max:255'],
            'cnpj'                => ['required','string','max:18'],
            'representante_legal' => ['required','string','max:255'],
            'cpf'                 => ['required','string','max:14'],
            'rg'                  => ['required','string','max:30'],
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

            'inicio_contrato'     => ['nullable','date'],
            'validade_meses'      => ['nullable','integer','min:1','max:120'],
            'contrato_assinado'   => ['nullable','boolean'],

            'contratos'                   => ['nullable','array'],
            'contratos.*.tipo'            => ['required_with:contratos.*.arquivo','in:contrato,aditivo,outro'],
            'contratos.*.arquivo'         => ['nullable','file','mimes:pdf','max:5120'],
            'contratos.*.descricao'       => ['nullable','string','max:255'],
            'contratos.*.assinado'        => ['nullable','boolean'],
        ]);

        // Recalcula vencimento se informou início/validade
        $inicio = !empty($data['inicio_contrato']) ? Carbon::parse($data['inicio_contrato']) : null;
        $meses  = !empty($data['validade_meses']) ? (int) $data['validade_meses'] : null;
        $vencimento = $distribuidor->vencimento_contrato;
        if ($inicio && $meses) {
            $vencimento = (clone $inicio)->addMonthsNoOverflow($meses);
        }

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

        DB::transaction(function () use ($data, $request, $distribuidor, $vencimento, $cityIds) {
            // Atualizar USER se informou email/senha
            $user = $distribuidor->user;

            if (!empty($data['email'])) {
                $user->email = $data['email'];
            }
            if (!empty($data['password'])) {
                $user->password = Hash::make($data['password']);
            }
            if (!empty($data['email']) || !empty($data['password'])) {
                $user->save();
            }

            // Atualizar DISTRIBUIDOR
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
                'vencimento_contrato' => $vencimento,
                'contrato_assinado'   => !empty($data['contrato_assinado']),
            ]);

            // Cities: sincroniza (mantendo a regra de ocupação já validada)
            $distribuidor->cities()->sync($cityIds->all());

            // Novos anexos (append)
            if (!empty($data['contratos']) && is_array($data['contratos'])) {
                foreach ($data['contratos'] as $idx => $meta) {
                    $file = $request->file("contratos.$idx.arquivo");
                    if (!$file) continue;

                    $path = $file->store("distribuidores/{$distribuidor->id}", 'public');

                    $distribuidor->anexos()->create([
                        'tipo'      => $meta['tipo'] ?? 'contrato',
                        'arquivo'   => $path,
                        'descricao' => $meta['descricao'] ?? null,
                        'assinado'  => !empty($meta['assinado']),
                    ]);
                }
            }
        });

        return redirect()
            ->route('admin.distribuidores.index')
            ->with('success', 'Distribuidor atualizado com sucesso!');
    }

    public function destroy(Distribuidor $distribuidor)
    {
        DB::transaction(function () use ($distribuidor) {
            // remove anexos (registros). Se quiser, apague os arquivos do disco também.
            $distribuidor->anexos()->delete();
            $distribuidor->cities()->detach();
            $distribuidor->delete();
        });

        return redirect()
            ->route('admin.distribuidores.index')
            ->with('success', 'Distribuidor removido com sucesso!');
    }
}
