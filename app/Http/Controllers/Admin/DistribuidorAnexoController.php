<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use App\Models\Distribuidor;
use App\Models\Anexo;
use App\Models\City;
use Carbon\Carbon;

class DistribuidorAnexoController extends Controller
{
    public function edit(Distribuidor $distribuidor, Anexo $anexo)
{
    $this->assertPertenceAoDistribuidor($distribuidor, $anexo);

    $cidades = $distribuidor->cities()
        ->select('cities.id', 'cities.name', 'cities.state as uf') // <-- qualificado
        ->orderBy('cities.state')                                  // <-- qualificado
        ->orderBy('cities.name')                                   // <-- qualificado
        ->get();

    return view('admin.distribuidores.anexos.edit', compact('distribuidor', 'anexo', 'cidades'));
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

        // 🔒 valida se a cidade pertence ao distribuidor
        if ($dados['tipo'] === 'contrato_cidade') {
            if (empty($dados['cidade_id'])) {
                return back()->withErrors(['cidade_id' => 'Selecione a cidade para "contrato_cidade".'])->withInput();
            }

            $cidadePertence = $distribuidor->cities()
                ->where('cities.id', $dados['cidade_id'])
                ->exists();

            if (! $cidadePertence) {
                return back()->withErrors([
                    'cidade_id' => 'Esta cidade não pertence às cidades de atuação do distribuidor.'
                ])->withInput();
            }
        } else {
            $dados['cidade_id'] = null;
        }

        // 🧮 calcula data de fim (mesma lógica do gestor)
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
        }

        if ($request->hasFile('arquivo')) {
            if ($anexo->arquivo && Storage::disk('public')->exists($anexo->arquivo)) {
                Storage::disk('public')->delete($anexo->arquivo);
            }
            $dados['arquivo'] = $request->file('arquivo')->store("distribuidores/{$distribuidor->id}", 'public');
        }

        $payload = [
            'tipo'              => $dados['tipo'],
            'cidade_id'         => $dados['tipo'] === 'contrato_cidade'
                ? (!empty($dados['cidade_id']) ? (int)$dados['cidade_id'] : null)
                : null,
            'descricao'         => $dados['descricao'] ?? null,
            'assinado'          => $dados['assinado'],
            'ativo'             => $dados['ativo'],
        ];

        if (isset($dados['percentual_vendas'])) {
            $payload['percentual_vendas'] = $dados['percentual_vendas'] !== null
                ? (float)$dados['percentual_vendas'] : null;
        }
        if (!empty($dados['arquivo'])) $payload['arquivo'] = $dados['arquivo'];
        if ($novaDataAss)  $payload['data_assinatura'] = $novaDataAss;
        if ($novaDataVenc) $payload['data_vencimento'] = $novaDataVenc;

        $anexo->update($payload);

        // Atualiza flags no distribuidor (igual ao gestor)
        $temAssinado = $distribuidor->anexos()->where('assinado', true)->exists();
        if ($distribuidor->contrato_assinado !== $temAssinado) {
            $distribuidor->update(['contrato_assinado' => $temAssinado]);
        }

        if ($anexo->fresh()->ativo) {
            $props = [];
            if ($anexo->percentual_vendas !== null) {
                $props['percentual_vendas'] = $anexo->percentual_vendas;
            }
            if ($anexo->data_vencimento) {
                $props['vencimento_contrato'] = $anexo->data_vencimento;
            }
            if ($props) $distribuidor->update($props);
        }

        return redirect()
            ->route('admin.distribuidores.show', $distribuidor)
            ->with('success', 'Anexo atualizado com sucesso!');
    }

    protected function assertPertenceAoDistribuidor(Distribuidor $distribuidor, Anexo $anexo): void
    {
        if ($anexo->anexavel_type !== Distribuidor::class || (int)$anexo->anexavel_id !== (int)$distribuidor->id) {
            abort(404);
        }
    }
}
