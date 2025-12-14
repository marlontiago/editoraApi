<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use App\Models\Gestor;
use App\Models\Anexo;
use App\Models\City;
use Carbon\Carbon;

class GestorAnexoController extends Controller
{
    public function show(Gestor $gestor, Anexo $anexo)
    {
        $this->assertPertenceAoGestor($gestor, $anexo);

        // mesmas regras do edit(): pega UFs e lista cidades daquele UF
        $ufs = $gestor->ufs()->pluck('uf')->filter()->unique()->values();

        // seu banco usa state/name
        $cidades = City::when($ufs->isNotEmpty(), fn($q) => $q->whereIn('state', $ufs))
            ->orderBy('state')
            ->orderBy('name')
            ->get(['id', 'name', 'state as uf']);

        return response()->json([
            'ok' => true,
            'data' => [
                'gestor' => $gestor->only(['id', 'razao_social']),
                'anexo' => $anexo,
                'cidades' => $cidades,
            ],
        ]);
    }

    public function update(Request $request, Gestor $gestor, Anexo $anexo)
    {
        $this->assertPertenceAoGestor($gestor, $anexo);

        $dados = $request->validate([
            'tipo'               => ['required', Rule::in(['contrato','aditivo','contrato_cidade','outro'])],
            'descricao'          => ['nullable','string','max:255'],
            'percentual_vendas'  => ['nullable','numeric','between:0,100'],

            // Início + Duração (meses)
            'data_inicio'        => ['nullable','date', 'required_with:duracao_meses'],
            'duracao_meses'      => ['nullable','integer','min:1','max:240', 'required_with:data_inicio'],

            'assinado'           => ['nullable','boolean'],
            'ativo'              => ['nullable','boolean'],
            'cidade_id'          => ['nullable','integer','exists:cities,id'],
            'arquivo'            => ['nullable','file','mimes:pdf','max:10240'],
        ]);

        // flags booleanas
        $dados['assinado'] = $request->boolean('assinado');
        $dados['ativo']    = $request->boolean('ativo');

        // regra extra: contrato_cidade exige cidade
        if ($dados['tipo'] === 'contrato_cidade' && empty($dados['cidade_id'])) {
            return response()->json([
                'ok' => false,
                'errors' => ['cidade_id' => ['Selecione a cidade para "contrato_cidade".']],
            ], 422);
        }

        // UFs do gestor (mesma lógica do web, com fallback)
        $ufsFromRelation = method_exists($gestor, 'ufs')
            ? $gestor->ufs()->pluck('uf')
            : collect();

        $fallbacks = collect([
            $gestor->estado_uf ?? null,
            $gestor->uf ?? null,
        ])->filter();

        $ufs = $ufsFromRelation
            ->merge($fallbacks)
            ->filter()
            ->map(fn($s) => strtoupper(trim($s)))
            ->unique()
            ->values()
            ->all();

        // valida cidade pertence aos estados de atuação
        if ($dados['tipo'] === 'contrato_cidade') {
            $cidade = City::find($dados['cidade_id']);
            if (!$cidade) {
                return response()->json([
                    'ok' => false,
                    'errors' => ['cidade_id' => ['Cidade inválida.']],
                ], 422);
            }

            // aqui seu City tem uf “acessível” via accessor ou campo; no edit você usa state as uf.
            // Pra garantir, vamos checar state:
            $ufCidade = strtoupper(trim((string)($cidade->state ?? $cidade->uf ?? '')));

            if (!in_array($ufCidade, $ufs, true)) {
                return response()->json([
                    'ok' => false,
                    'errors' => ['cidade_id' => ['Esta cidade não pertence aos estados de atuação do gestor.']],
                ], 422);
            }
        } else {
            $dados['cidade_id'] = null;
        }

        // cálculo das datas (idêntico ao web)
        $novaDataAss  = null;
        $novaDataVenc = null;

        $temInicio = $request->filled('data_inicio');
        $temMeses  = $request->filled('duracao_meses');

        if ($temInicio && $temMeses) {
            $inicio = Carbon::parse($dados['data_inicio']);
            $meses  = (int)$dados['duracao_meses'];
            $venc   = (clone $inicio)->addMonthsNoOverflow($meses);

            $novaDataAss  = $inicio->toDateString();
            $novaDataVenc = $venc->toDateString();
        } elseif ($temInicio && !$temMeses) {
            if ($anexo->data_assinatura && $anexo->data_vencimento) {
                $antMeses = Carbon::parse($anexo->data_assinatura)
                    ->diffInMonths(Carbon::parse($anexo->data_vencimento));

                if ($antMeses > 0) {
                    $inicio = Carbon::parse($dados['data_inicio']);
                    $venc   = (clone $inicio)->addMonthsNoOverflow($antMeses);
                    $novaDataAss  = $inicio->toDateString();
                    $novaDataVenc = $venc->toDateString();
                } else {
                    return response()->json([
                        'ok' => false,
                        'errors' => ['duracao_meses' => ['Informe a duração (meses) para calcular o fim do contrato.']],
                    ], 422);
                }
            } else {
                return response()->json([
                    'ok' => false,
                    'errors' => ['duracao_meses' => ['Informe a duração (meses) para calcular o fim do contrato.']],
                ], 422);
            }
        } elseif (!$temInicio && $temMeses) {
            if ($anexo->data_assinatura) {
                $inicio = Carbon::parse($anexo->data_assinatura);
                $meses  = (int)$dados['duracao_meses'];
                $venc   = (clone $inicio)->addMonthsNoOverflow($meses);
                $novaDataAss  = $inicio->toDateString();
                $novaDataVenc = $venc->toDateString();
            } else {
                return response()->json([
                    'ok' => false,
                    'errors' => ['data_inicio' => ['Informe o início do contrato para calcular o fim.']],
                ], 422);
            }
        }

        // upload opcional (substitui arquivo)
        if ($request->hasFile('arquivo')) {
            if ($anexo->arquivo && Storage::disk('public')->exists($anexo->arquivo)) {
                Storage::disk('public')->delete($anexo->arquivo);
            }
            $dados['arquivo'] = $request->file('arquivo')->store("gestores/{$gestor->id}", 'public');
        }

        // payload do anexo
        $payload = [
            'tipo'      => $dados['tipo'],
            'cidade_id' => $dados['tipo'] === 'contrato_cidade'
                ? (!empty($dados['cidade_id']) ? (int)$dados['cidade_id'] : null)
                : null,
            'descricao' => $dados['descricao'] ?? null,
            'assinado'  => $dados['assinado'],
            'ativo'     => $dados['ativo'],
        ];

        if (array_key_exists('percentual_vendas', $dados)) {
            $payload['percentual_vendas'] = $dados['percentual_vendas'] !== null
                ? (float)$dados['percentual_vendas'] : null;
        }
        if (!empty($dados['arquivo'])) {
            $payload['arquivo'] = $dados['arquivo'];
        }
        if ($novaDataAss !== null)  $payload['data_assinatura'] = $novaDataAss;
        if ($novaDataVenc !== null) $payload['data_vencimento'] = $novaDataVenc;

        $anexo->update($payload);

        // deriva campos do gestor
        $temAssinado = $gestor->anexos()->where('assinado', true)->exists();
        if ($gestor->contrato_assinado !== $temAssinado) {
            $gestor->update(['contrato_assinado' => $temAssinado]);
        }

        if ($anexo->fresh()->ativo) {
            // garante único ativo (no web você não faz aqui, mas faz em ativarAnexo; eu recomendo manter consistência)
            $gestor->anexos()->where('ativo', true)->where('id', '<>', $anexo->id)->update(['ativo' => false]);

            $props = [];
            if ($anexo->percentual_vendas !== null) {
                $props['percentual_vendas'] = $anexo->percentual_vendas;
            }
            if ($anexo->data_vencimento) {
                $props['vencimento_contrato'] = $anexo->data_vencimento;
            }
            if ($props) $gestor->update($props);
        }

        return response()->json([
            'ok' => true,
            'message' => 'Anexo atualizado com sucesso!',
            'data' => $anexo->fresh(),
        ]);
    }

    protected function assertPertenceAoGestor(Gestor $gestor, Anexo $anexo): void
    {
        if ($anexo->anexavel_type !== Gestor::class || (int)$anexo->anexavel_id !== (int)$gestor->id) {
            abort(404);
        }
    }
}
