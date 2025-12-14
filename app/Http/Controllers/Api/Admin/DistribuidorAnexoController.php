<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Anexo;
use App\Models\City;
use App\Models\Distribuidor;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;

class DistribuidorAnexoController extends Controller
{
    public function show(Distribuidor $distribuidor, Anexo $anexo)
    {
        $this->assertPertenceAoDistribuidor($distribuidor, $anexo);

        $cidades = $distribuidor->cities()
            ->select('cities.id', 'cities.name', 'cities.state as uf')
            ->orderBy('cities.state')
            ->orderBy('cities.name')
            ->get();

        return response()->json([
            'ok' => true,
            'data' => [
                'distribuidor' => [
                    'id' => $distribuidor->id,
                    'nome' => $distribuidor->nome ?? $distribuidor->razao_social ?? null,
                ],
                'anexo' => $anexo,
                'cidades' => $cidades,
            ],
        ]);
    }

   public function update(Request $request, Distribuidor $distribuidor, Anexo $anexo)
{
    $this->assertPertenceAoDistribuidor($distribuidor, $anexo);

    $dados = $request->validate([
        'tipo'               => ['required', Rule::in(['contrato','aditivo','contrato_cidade','outro'])],
        'descricao'          => ['nullable','string','max:255'],
        'percentual_vendas'  => ['nullable','numeric','between:0,100'],
        'data_inicio'        => ['nullable','date','required_with:duracao_meses'],
        'duracao_meses'      => ['nullable','integer','min:1','max:240','required_with:data_inicio'],
        'assinado'           => ['nullable','boolean'],
        'ativo'              => ['nullable','boolean'],
        'cidade_id'          => ['nullable','integer','exists:cities,id'],
        'arquivo'            => ['nullable','file','mimes:pdf','max:10240'],
    ]);

    $dados['assinado'] = $request->boolean('assinado');
    $dados['ativo']    = $request->boolean('ativo');

    // Se for contrato por cidade, exige cidade_id e valida que pertence às cidades do distribuidor
    if ($dados['tipo'] === 'contrato_cidade') {
        if (empty($dados['cidade_id'])) {
            return response()->json([
                'message' => 'Erro de validação.',
                'errors' => ['cidade_id' => ['Selecione a cidade para "contrato_cidade".']],
            ], 422);
        }

        $cidadePertence = $distribuidor->cities()
            ->where('cities.id', $dados['cidade_id'])
            ->exists();

        if (! $cidadePertence) {
            return response()->json([
                'message' => 'Erro de validação.',
                'errors' => ['cidade_id' => ['Esta cidade não pertence às cidades de atuação do distribuidor.']],
            ], 422);
        }
    } else {
        $dados['cidade_id'] = null;
    }

    // Datas (data_inicio + duracao_meses -> assinatura/vencimento)
    $novaDataAss = null;
    $novaDataVenc = null;

    if ($request->filled('data_inicio') && $request->filled('duracao_meses')) {
        $inicio = Carbon::parse($dados['data_inicio']);
        $meses  = (int) $dados['duracao_meses'];
        $venc   = (clone $inicio)->addMonthsNoOverflow($meses);

        $novaDataAss  = $inicio->toDateString();
        $novaDataVenc = $venc->toDateString();
    }

    // Transação para garantir consistência (especialmente ao mexer em "ativo")
    $out = DB::transaction(function () use ($request, $distribuidor, $anexo, $dados, $novaDataAss, $novaDataVenc) {

        // Se veio arquivo novo, troca o PDF
        if ($request->hasFile('arquivo')) {
            if ($anexo->arquivo && Storage::disk('public')->exists($anexo->arquivo)) {
                Storage::disk('public')->delete($anexo->arquivo);
            }
            $dados['arquivo'] = $request->file('arquivo')->store("distribuidores/{$distribuidor->id}", 'public');
        }

        $payload = [
            'tipo'              => $dados['tipo'],
            'cidade_id'         => $dados['tipo'] === 'contrato_cidade'
                ? (!empty($dados['cidade_id']) ? (int) $dados['cidade_id'] : null)
                : null,
            'descricao'         => $dados['descricao'] ?? null,
            'assinado'          => $dados['assinado'],
            'ativo'             => $dados['ativo'],
        ];

        if (array_key_exists('percentual_vendas', $dados)) {
            $payload['percentual_vendas'] = $dados['percentual_vendas'] !== null
                ? (float) $dados['percentual_vendas']
                : null;
        }

        if (!empty($dados['arquivo'])) $payload['arquivo'] = $dados['arquivo'];
        if ($novaDataAss)  $payload['data_assinatura'] = $novaDataAss;
        if ($novaDataVenc) $payload['data_vencimento'] = $novaDataVenc;

        // ✅ Regra nova: se este anexo vier como ativo=1, desativa os demais primeiro
        if (!empty($dados['ativo'])) {
            $distribuidor->anexos()
                ->where('ativo', true)
                ->where('id', '<>', $anexo->id)
                ->update(['ativo' => false]);
        }

        $anexo->update($payload);

        // contrato_assinado no distribuidor
        $temAssinado = $distribuidor->anexos()->where('assinado', true)->exists();
        if ($distribuidor->contrato_assinado !== $temAssinado) {
            $distribuidor->update(['contrato_assinado' => $temAssinado]);
        }

        // aplica props no distribuidor se este anexo estiver ativo
        $anexoAtual = $anexo->fresh();
        if ($anexoAtual->ativo) {
            $props = [];
            if ($anexoAtual->percentual_vendas !== null) {
                $props['percentual_vendas'] = $anexoAtual->percentual_vendas;
            }
            if ($anexoAtual->data_vencimento) {
                $props['vencimento_contrato'] = $anexoAtual->data_vencimento;
            }
            if ($props) $distribuidor->update($props);
        }

        return $anexoAtual->load('cidade');
    });

    return response()->json([
        'ok' => true,
        'message' => 'Anexo atualizado com sucesso!',
        'data' => $out,
    ]);
}


    protected function assertPertenceAoDistribuidor(Distribuidor $distribuidor, Anexo $anexo): void
    {
        if ($anexo->anexavel_type !== Distribuidor::class || (int)$anexo->anexavel_id !== (int)$distribuidor->id) {
            abort(404);
        }
    }
}
