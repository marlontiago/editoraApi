<?php

namespace App\Services;

use App\Models\Anexo;
use App\Models\Distribuidor;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class DistribuidorAnexoService
{
    public function assertPertenceAoDistribuidor(Distribuidor $distribuidor, Anexo $anexo): void
    {
        if ($anexo->anexavel_type !== Distribuidor::class || (int)$anexo->anexavel_id !== (int)$distribuidor->id) {
            abort(404);
        }
    }

    /**
     * Retorna lista de cidades do distribuidor formatada (id, name, uf).
     * Usa detecção automática da coluna de UF (state/uf/estado/etc).
     */
    public function getCidadesDoDistribuidor(Distribuidor $distribuidor)
    {
        $ufCol = $this->cityUfColumn() ?? 'state'; // fallback pro seu caso atual

        // se não existir coluna nenhuma, devolve uf null
        $selectUf = $ufCol && Schema::hasColumn('cities', $ufCol)
            ? "cities.$ufCol as uf"
            : DB::raw("NULL as uf");

        return $distribuidor->cities()
            ->select('cities.id', 'cities.name', $selectUf)
            ->when($ufCol && Schema::hasColumn('cities', $ufCol), fn($q) => $q->orderBy("cities.$ufCol"))
            ->orderBy('cities.name')
            ->get();
    }

    /**
     * Miolo do update de anexo (valida, troca arquivo, controla ativo, datas, contrato_assinado e aplica props no distribuidor)
     */
    public function updateFromRequest(Request $request, Distribuidor $distribuidor, Anexo $anexo): Anexo
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

        // contrato_cidade: exige cidade_id e valida que pertence ao distribuidor
        if ($dados['tipo'] === 'contrato_cidade') {
            if (empty($dados['cidade_id'])) {
                abort(422, 'Selecione a cidade para "contrato_cidade".');
            }

            $cidadePertence = $distribuidor->cities()
                ->where('cities.id', $dados['cidade_id'])
                ->exists();

            if (!$cidadePertence) {
                abort(422, 'Esta cidade não pertence às cidades de atuação do distribuidor.');
            }
        } else {
            $dados['cidade_id'] = null;
        }

        // Datas -> assinatura/vencimento
        $novaDataAss = null;
        $novaDataVenc = null;

        if ($request->filled('data_inicio') && $request->filled('duracao_meses')) {
            $inicio = Carbon::parse($dados['data_inicio']);
            $meses  = (int) $dados['duracao_meses'];
            $venc   = (clone $inicio)->addMonthsNoOverflow($meses);

            $novaDataAss  = $inicio->toDateString();
            $novaDataVenc = $venc->toDateString();
        }

        return DB::transaction(function () use ($request, $distribuidor, $anexo, $dados, $novaDataAss, $novaDataVenc) {

            // Troca do arquivo
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

            // Se este veio ativo=1, desativa os demais
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

            // aplica props no distribuidor se o anexo ficou ativo
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
    }

    public function cityUfColumn(): ?string
    {
        foreach (['uf','state','estado','state_code','uf_code','sigla_uf','uf_sigla'] as $col) {
            if (Schema::hasColumn('cities', $col)) return $col;
        }
        return null;
    }
}
