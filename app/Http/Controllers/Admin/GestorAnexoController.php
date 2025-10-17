<?php

namespace App\Http\Controllers\Admin;

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
    public function edit(Gestor $gestor, Anexo $anexo)
    {
        $this->assertPertenceAoGestor($gestor, $anexo);

        // pivot gestor_ufs tem 'uf' (ok)
        $ufs = $gestor->ufs()->pluck('uf')->filter()->unique()->values();

        // cities usa 'state' no banco; consulte por 'state' e faça alias para 'uf'
        $cidades = \App\Models\City::when($ufs->isNotEmpty(), fn($q) => $q->whereIn('state', $ufs))
            ->orderBy('state')
            ->orderBy('name')
            ->get(['id', 'name', 'state as uf']);

        return view('admin.gestores.anexos.edit', compact('gestor','anexo','cidades'));
    }



    public function update(Request $request, Gestor $gestor, Anexo $anexo)
    {
        $this->assertPertenceAoGestor($gestor, $anexo);

        // Validação: não temos mais data_vencimento editável
        $dados = $request->validate([
            'tipo'               => ['required', Rule::in(['contrato','aditivo','contrato_cidade','outro'])],
            'descricao'          => ['nullable','string','max:255'],
            'percentual_vendas'  => ['nullable','numeric','between:0,100'],

            // Início + Duração (meses) => calculam fim no backend
            'data_inicio'        => ['nullable','date', 'required_with:duracao_meses'],
            'duracao_meses'      => ['nullable','integer','min:1','max:240', 'required_with:data_inicio'],

            'assinado'           => ['nullable','boolean'],
            'ativo'              => ['nullable','boolean'],

            'cidade_id'          => ['nullable','integer','exists:cities,id'],

            'arquivo'            => ['nullable','file','mimes:pdf','max:10240'],
        ]);

        // Flags booleanas
        $dados['assinado'] = $request->boolean('assinado');
        $dados['ativo']    = $request->boolean('ativo');

        // Regra extra: contrato_cidade exige cidade
        if ($dados['tipo'] === 'contrato_cidade' && empty($dados['cidade_id'])) {
            return back()->withErrors(['cidade_id' => 'Selecione a cidade para "contrato_cidade".'])->withInput();
        }

        // Depois de $dados = $request->validate([...]);
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

        if ($dados['tipo'] === 'contrato_cidade') {
            if (empty($dados['cidade_id'])) {
                return back()->withErrors(['cidade_id' => 'Selecione a cidade para "contrato_cidade".'])->withInput();
            }

            $cidade = \App\Models\City::find($dados['cidade_id']);
            if (!$cidade) {
                return back()->withErrors(['cidade_id' => 'Cidade inválida.'])->withInput();
            }

            // ajuste 'state' para 'uf' se necessário
            if (!in_array(strtoupper($cidade->uf), $ufs, true)) {
                return back()->withErrors(['cidade_id' => 'Esta cidade não pertence aos estados de atuação do gestor.'])->withInput();
            }
        } else {
            // Não é contrato_cidade: zera para não vazar cidade
            $dados['cidade_id'] = null;
        }

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
            // Inferir duração antiga (se possível)
            if ($anexo->data_assinatura && $anexo->data_vencimento) {
                $antMeses = Carbon::parse($anexo->data_assinatura)
                    ->diffInMonths(Carbon::parse($anexo->data_vencimento));
                if ($antMeses > 0) {
                    $inicio = Carbon::parse($dados['data_inicio']);
                    $venc   = (clone $inicio)->addMonthsNoOverflow($antMeses);
                    $novaDataAss  = $inicio->toDateString();
                    $novaDataVenc = $venc->toDateString();
                } else {
                    return back()->withErrors([
                        'duracao_meses' => 'Informe a duração (meses) para calcular o fim do contrato.'
                    ])->withInput();
                }
            } else {
                return back()->withErrors([
                    'duracao_meses' => 'Informe a duração (meses) para calcular o fim do contrato.'
                ])->withInput();
            }
        } elseif (!$temInicio && $temMeses) {
            // Usar início antigo (se existir)
            if ($anexo->data_assinatura) {
                $inicio = Carbon::parse($anexo->data_assinatura);
                $meses  = (int)$dados['duracao_meses'];
                $venc   = (clone $inicio)->addMonthsNoOverflow($meses);
                $novaDataAss  = $inicio->toDateString(); // mantem a antiga
                $novaDataVenc = $venc->toDateString();
            } else {
                return back()->withErrors([
                    'data_inicio' => 'Informe o início do contrato para calcular o fim.'
                ])->withInput();
            }
        }

        // Upload opcional (substitui arquivo)
        if ($request->hasFile('arquivo')) {
            if ($anexo->arquivo && Storage::disk('public')->exists($anexo->arquivo)) {
                Storage::disk('public')->delete($anexo->arquivo);
            }
            $dados['arquivo'] = $request->file('arquivo')->store("gestores/{$gestor->id}", 'public');
        }

        // Aplica mudanças no Anexo
        $payload = [
            'tipo'              => $dados['tipo'],
            'cidade_id'         => $dados['tipo'] === 'contrato_cidade'
                ? (!empty($dados['cidade_id']) ? (int)$dados['cidade_id'] : null)
                : null,
            'descricao'         => $dados['descricao'] ?? null,
            'assinado'          => $dados['assinado'],
            'ativo'             => $dados['ativo'],
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

        // Deriva flags/campos no Gestor
        $temAssinado = $gestor->anexos()->where('assinado', true)->exists();
        if ($gestor->contrato_assinado !== $temAssinado) {
            $gestor->update(['contrato_assinado' => $temAssinado]);
        }

        if ($anexo->fresh()->ativo) {
            $props = [];
            if ($anexo->percentual_vendas !== null) {
                $props['percentual_vendas'] = $anexo->percentual_vendas;
            }
            if ($anexo->data_vencimento) {
                $props['vencimento_contrato'] = $anexo->data_vencimento;
            }
            if ($props) $gestor->update($props);
        }

        return redirect()
            ->route('admin.gestores.show', $gestor)
            ->with('success', 'Anexo atualizado com sucesso!');
    }

    protected function assertPertenceAoGestor(Gestor $gestor, Anexo $anexo): void
    {
        if ($anexo->anexavel_type !== Gestor::class || (int)$anexo->anexavel_id !== (int)$gestor->id) {
            abort(404);
        }
    }
}
