<?php

namespace App\Services;

use App\Models\Anexo;
use App\Models\City;
use App\Models\Gestor;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class GestorAnexoService
{
    public function getEditPayload(Gestor $gestor, Anexo $anexo): array
    {
        $this->assertPertenceAoGestor($gestor, $anexo);

        $ufs = $gestor->ufs()->pluck('uf')->filter()->unique()->values();

        $cidades = City::when($ufs->isNotEmpty(), fn($q) => $q->whereIn('state', $ufs))
            ->orderBy('state')
            ->orderBy('name')
            ->get(['id', 'name', 'state as uf']);

        return compact('gestor', 'anexo', 'cidades');
    }

    public function updateFromRequest(Request $request, Gestor $gestor, Anexo $anexo): Anexo
    {
        $this->assertPertenceAoGestor($gestor, $anexo);

        $dados = $request->validate([
            'tipo'               => ['required', Rule::in(['contrato','aditivo','contrato_cidade','outro'])],
            'descricao'          => ['nullable','string','max:255'],
            'percentual_vendas'  => ['nullable','numeric','between:0,100'],

            'data_inicio'        => ['nullable','date', 'required_with:duracao_meses'],
            'duracao_meses'      => ['nullable','integer','min:1','max:240', 'required_with:data_inicio'],

            'assinado'           => ['nullable','boolean'],
            'ativo'              => ['nullable','boolean'],
            'cidade_id'          => ['nullable','integer','exists:cities,id'],
            'arquivo'            => ['nullable','file','mimes:pdf','max:10240'],
        ]);

        $dados['assinado'] = $request->boolean('assinado');
        $dados['ativo']    = $request->boolean('ativo');

        // --- valida cidade x UFs do gestor ---
        $ufsFromRelation = method_exists($gestor, 'ufs') ? $gestor->ufs()->pluck('uf') : collect();
        $fallbacks = collect([$gestor->estado_uf ?? null, $gestor->uf ?? null])->filter();

        $ufs = $ufsFromRelation
            ->merge($fallbacks)
            ->filter()
            ->map(fn($s) => strtoupper(trim($s)))
            ->unique()
            ->values()
            ->all();

        if ($dados['tipo'] === 'contrato_cidade') {
            if (empty($dados['cidade_id'])) {
                abort(422, 'Selecione a cidade para "contrato_cidade".');
            }

            $cidade = City::find($dados['cidade_id']);
            if (!$cidade) abort(422, 'Cidade inválida.');

            $ufCidade = strtoupper(trim((string)($cidade->state ?? $cidade->uf ?? '')));
            if (!in_array($ufCidade, $ufs, true)) {
                abort(422, 'Esta cidade não pertence aos estados de atuação do gestor.');
            }
        } else {
            $dados['cidade_id'] = null;
        }

        // --- calcula datas ---
        [$novaDataAss, $novaDataVenc] = $this->calcularDatas($request, $dados, $anexo);

        // --- upload (substitui arquivo) ---
        if ($request->hasFile('arquivo')) {
            if ($anexo->arquivo && Storage::disk('public')->exists($anexo->arquivo)) {
                Storage::disk('public')->delete($anexo->arquivo);
            }
            $dados['arquivo'] = $request->file('arquivo')->store("gestores/{$gestor->id}", 'public');
        }

        return DB::transaction(function () use ($gestor, $anexo, $dados, $novaDataAss, $novaDataVenc) {

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
                $payload['percentual_vendas'] = $dados['percentual_vendas'] !== null ? (float)$dados['percentual_vendas'] : null;
            }
            if (!empty($dados['arquivo'])) {
                $payload['arquivo'] = $dados['arquivo'];
            }
            if ($novaDataAss !== null)  $payload['data_assinatura'] = $novaDataAss;
            if ($novaDataVenc !== null) $payload['data_vencimento'] = $novaDataVenc;

            $anexo->update($payload);

            // garante único ativo (recomendado)
            if ($anexo->fresh()->ativo) {
                $gestor->anexos()
                    ->where('ativo', true)
                    ->where('id', '<>', $anexo->id)
                    ->update(['ativo' => false]);
            }

            // contrato_assinado (derivado)
            $temAssinado = $gestor->anexos()->where('assinado', true)->exists();
            if ($gestor->contrato_assinado !== $temAssinado) {
                $gestor->update(['contrato_assinado' => $temAssinado]);
            }

            // aplica no gestor se estiver ativo
            if ($anexo->fresh()->ativo) {
                $props = [];
                if ($anexo->percentual_vendas !== null) $props['percentual_vendas'] = $anexo->percentual_vendas;
                if ($anexo->data_vencimento) $props['vencimento_contrato'] = $anexo->data_vencimento;
                if ($props) $gestor->update($props);
            }

            return $anexo->fresh();
        });
    }

    private function calcularDatas(Request $request, array $dados, Anexo $anexo): array
    {
        $novaDataAss = null;
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
                    abort(422, 'Informe a duração (meses) para calcular o fim do contrato.');
                }
            } else {
                abort(422, 'Informe a duração (meses) para calcular o fim do contrato.');
            }
        } elseif (!$temInicio && $temMeses) {
            if ($anexo->data_assinatura) {
                $inicio = Carbon::parse($anexo->data_assinatura);
                $meses  = (int)$dados['duracao_meses'];
                $venc   = (clone $inicio)->addMonthsNoOverflow($meses);
                $novaDataAss  = $inicio->toDateString();
                $novaDataVenc = $venc->toDateString();
            } else {
                abort(422, 'Informe o início do contrato para calcular o fim.');
            }
        }

        return [$novaDataAss, $novaDataVenc];
    }

    private function assertPertenceAoGestor(Gestor $gestor, Anexo $anexo): void
    {
        if ($anexo->anexavel_type !== Gestor::class || (int)$anexo->anexavel_id !== (int)$gestor->id) {
            abort(404);
        }
    }
}
