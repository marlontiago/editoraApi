<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Gestor;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

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

            // Endereço (todos opcionais)
            'endereco'            => ['nullable','string','max:255'],
            'numero'              => ['nullable','string','max:20'],
            'complemento'         => ['nullable','string','max:100'],
            'bairro'              => ['nullable','string','max:100'],
            'cidade'              => ['nullable','string','max:100'],
            'uf'                  => ['nullable','string','size:2'],
            'cep'                 => ['nullable','string','max:9'],

            // Contratuais (agora via início + validade)
            'inicio_contrato'     => ['nullable','date'],
            'validade_meses'      => ['nullable','integer','min:1','max:120'],

            // Anexos múltiplos (sem datas por anexo)
            'contratos'             => ['nullable','array'],
            'contratos.*.tipo'      => ['required_with:contratos.*.arquivo','in:contrato,aditivo,outro'],
            'contratos.*.arquivo'   => ['nullable','file','mimes:pdf','max:5120'],
            'contratos.*.descricao' => ['nullable','string','max:255'],
            'contratos.*.assinado'  => ['nullable','boolean'],
        ]);

        // Calcula vencimento com base em início + meses
        $inicio = !empty($data['inicio_contrato']) ? Carbon::parse($data['inicio_contrato']) : null;
        $meses  = !empty($data['validade_meses']) ? (int) $data['validade_meses'] : null;
        $vencimento = null;
        if ($inicio && $meses) {
            $vencimento = (clone $inicio)->addMonthsNoOverflow($meses);
        }

        // Derivar se há contrato assinado a partir dos anexos enviados
        $temAssinado = false;
        if (!empty($data['contratos']) && is_array($data['contratos'])) {
            foreach ($data['contratos'] as $meta) {
                if (!empty($meta['assinado'])) { $temAssinado = true; break; }
            }
        }

        $gestor = DB::transaction(function () use ($data, $request, $vencimento, $temAssinado) {
            // 1) Resolver e-mail/senha do USER (placeholder se email vazio)
            $userEmail = trim((string)($data['email'] ?? ''));
            $userPass  = (string)($data['password'] ?? '');

            if ($userEmail === '') {
                $userEmail = 'gestor+'.Str::uuid().'@placeholder.local';
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
                $user->assignRole('gestor');
            }

            // 3) Criar GESTOR (contrato_assinado derivado dos anexos do request)
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

                // e-mail do GESTOR (opcional; exibição tratada por accessor)
                'email'               => $data['email'] ?? null,

                // Endereço
                'endereco'            => $data['endereco'] ?? null,
                'numero'              => $data['numero'] ?? null,
                'complemento'         => $data['complemento'] ?? null,
                'bairro'              => $data['bairro'] ?? null,
                'cidade'              => $data['cidade'] ?? null,
                'uf'                  => $data['uf'] ?? null,
                'cep'                 => $data['cep'] ?? null,

                // Contratuais
                'percentual_vendas'   => $data['percentual_vendas'] ?? 0,
                'vencimento_contrato' => $vencimento,
                'contrato_assinado'   => $temAssinado,
            ]);

            // 4) Salvar ANEXOS (se vieram)
            if (!empty($data['contratos']) && is_array($data['contratos'])) {
                foreach ($data['contratos'] as $idx => $meta) {
                    $file = $request->file("contratos.$idx.arquivo");
                    if (!$file) continue;

                    $path = $file->store("gestores/{$gestor->id}", 'public');

                    $gestor->anexos()->create([
                        'tipo'       => $meta['tipo'] ?? 'contrato',
                        'arquivo'    => $path,
                        'descricao'  => $meta['descricao'] ?? null,
                        'assinado'   => !empty($meta['assinado']),
                    ]);
                }
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
        $gestor->load('anexos');
        return view('admin.gestores.edit', compact('gestor'));
    }

    public function update(Request $request, Gestor $gestor)
    {
        $data = $request->validate([
            'razao_social'        => ['required','string','max:255'],
            'cnpj'                => ['required','string','max:18'],
            'representante_legal' => ['required','string','max:255'],
            'cpf'                 => ['required','string','max:14'],
            'rg'                  => ['nullable','string','max:30'],
            'telefone'            => ['nullable','string','max:20'],
            'estado_uf'           => ['nullable','string','size:2'],

            // e-mail/senha OPCIONAIS (apenas se desejar atualizar o USER)
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

            // Contratuais (agora via início + validade)
            'inicio_contrato'     => ['nullable','date'],
            'validade_meses'      => ['nullable','integer','min:1','max:120'],
            'percentual_vendas'   => ['nullable','numeric','min:0','max:100'],

            // Anexos (sem datas por anexo)
            'contratos'             => ['nullable','array'],
            'contratos.*.tipo'      => ['required_with:contratos.*.arquivo','in:contrato,aditivo,outro'],
            'contratos.*.arquivo'   => ['nullable','file','mimes:pdf','max:5120'],
            'contratos.*.descricao' => ['nullable','string','max:255'],
            'contratos.*.assinado'  => ['nullable','boolean'],
        ]);

        // Recalcular vencimento, se informou início/validade
        $inicio = !empty($data['inicio_contrato']) ? Carbon::parse($data['inicio_contrato']) : null;
        $meses  = !empty($data['validade_meses']) ? (int) $data['validade_meses'] : null;
        $vencimento = $gestor->vencimento_contrato; // mantém o atual por padrão
        if ($inicio && $meses) {
            $vencimento = (clone $inicio)->addMonthsNoOverflow($meses);
        }

        DB::transaction(function () use ($data, $request, $gestor, $vencimento) {
            // Atualizar USER se email/senha reais vierem
            $user = $gestor->user;

            if (!empty($data['email'])) {
                $user->email = $data['email'];
            }
            if (!empty($data['password'])) {
                $user->password = Hash::make($data['password']);
            }
            if (!empty($data['email']) || !empty($data['password'])) {
                $user->save();
            }

            // Atualizar GESTOR (por enquanto sem mexer em contrato_assinado)
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

                'percentual_vendas'   => $data['percentual_vendas'] ?? 0,
                'vencimento_contrato' => $vencimento,
            ]);

            // Novos anexos (append)
            if (!empty($data['contratos']) && is_array($data['contratos'])) {
                foreach ($data['contratos'] as $idx => $meta) {
                    $file = $request->file("contratos.$idx.arquivo");
                    if (!$file) continue;

                    $path = $file->store("gestores/{$gestor->id}", 'public');

                    $gestor->anexos()->create([
                        'tipo'       => $meta['tipo'] ?? 'contrato',
                        'arquivo'    => $path,
                        'descricao'  => $meta['descricao'] ?? null,
                        'assinado'   => !empty($meta['assinado']),
                    ]);
                }
            }

            // Recalcular contrato_assinado olhando TODOS os anexos atuais
            $temAssinadoAgora = $gestor->anexos()->where('assinado', true)->exists();
            if ($gestor->contrato_assinado !== $temAssinadoAgora) {
                $gestor->update(['contrato_assinado' => $temAssinadoAgora]);
            }
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
}
