<?php

namespace App\Services;

use App\Models\Distribuidor;
use App\Models\Gestor;
use App\Models\User;
use App\Models\City;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class DistribuidorService
{
    public function criar(array $dados): Distribuidor
    {
        return DB::transaction(function () use ($dados) {
            $gestor = Gestor::findOrFail($dados['gestor_id']);

            if (!$gestor->estado_uf) {
                throw ValidationException::withMessages([
                    'gestor_id' => 'O gestor selecionado não tem UF vinculada.',
                ]);
            }

            $this->validarCidadesDisponiveis($dados['cities']);
            $this->validarCidadesDaUf($dados['cities'], $gestor->estado_uf);

            $caminhoContrato = $this->salvarContrato($dados['contrato'] ?? null);

            $user = User::create([
                'name'     => $dados['razao_social'],
                'email'    => $dados['email'],
                'password' => Hash::make($dados['password']),
            ]);
            $user->assignRole('distribuidor');

            $distribuidor = Distribuidor::create([
                'user_id'             => $user->id,
                'gestor_id'           => $dados['gestor_id'],
                'razao_social'        => $dados['razao_social'],
                'cnpj'                => $dados['cnpj'],
                'representante_legal' => $dados['representante_legal'],
                'cpf'                 => $dados['cpf'],
                'rg'                  => $dados['rg'],
                'telefone'            => $dados['telefone'] ?? null,
                'endereco_completo'   => $dados['endereco_completo'] ?? null,
                'percentual_vendas'   => $dados['percentual_vendas'],
                'vencimento_contrato' => $dados['vencimento_contrato'] ?? null,
                'contrato_assinado'   => (bool)$dados['contrato_assinado'],
                'contrato'            => $caminhoContrato,
            ]);

            $distribuidor->cities()->sync($dados['cities']);

            return $distribuidor->load(['user', 'cities', 'gestor.user']);
        });
    }

    public function atualizar(Distribuidor $distribuidor, array $dados): Distribuidor
    {
        return DB::transaction(function () use ($distribuidor, $dados) {
            $gestor = Gestor::findOrFail($dados['gestor_id']);

            if (!$gestor->estado_uf) {
                throw ValidationException::withMessages([
                    'gestor_id' => 'O gestor selecionado não tem UF vinculada.',
                ]);
            }

            $this->validarCidadesDisponiveis($dados['cities'], $distribuidor->id);
            $this->validarCidadesDaUf($dados['cities'], $gestor->estado_uf);

            if (isset($dados['contrato']) && $dados['contrato'] instanceof UploadedFile) {
                $distribuidor->contrato = $this->salvarContrato($dados['contrato']);
                $distribuidor->save();
            }

            // Se vier 'name', use-o; senão mantenha padrão: igual à razão social
            $userName = $dados['name'] ?? $dados['razao_social'];

            $distribuidor->user->update([
                'name'     => $userName,
                'email'    => $dados['email'],
                'password' => !empty($dados['password']) ? Hash::make($dados['password']) : $distribuidor->user->password,
            ]);

            $distribuidor->update([
                'gestor_id'           => $dados['gestor_id'],
                'razao_social'        => $dados['razao_social'],
                'cnpj'                => $dados['cnpj'],
                'representante_legal' => $dados['representante_legal'],
                'cpf'                 => $dados['cpf'],
                'rg'                  => $dados['rg'],
                'telefone'            => $dados['telefone'] ?? null,
                'endereco_completo'   => $dados['endereco_completo'] ?? null,
                'percentual_vendas'   => $dados['percentual_vendas'],
                'vencimento_contrato' => $dados['vencimento_contrato'] ?? null,
                'contrato_assinado'   => (bool)$dados['contrato_assinado'],
            ]);

            $distribuidor->cities()->sync($dados['cities']);

            return $distribuidor->load(['user', 'cities', 'gestor.user']);
        });
    }

    public function excluir(Distribuidor $distribuidor): void
    {
        DB::transaction(function () use ($distribuidor) {
            $distribuidor->cities()->detach();
            $distribuidor->user()->delete();
            $distribuidor->delete();
        });
    }

    public function opcoesPorGestor(Gestor $gestor)
    {
        return $gestor->distribuidores()
            ->with('user:id,name')
            ->orderBy('razao_social')
            ->get(['id','razao_social','user_id','gestor_id'])
            ->map(fn($d) => [
                'id'   => $d->id,
                'text' => $d->user?->name ?? $d->razao_social,
            ]);
    }

    // -------- Helpers 

    public function validarCidadesDisponiveis(array $idsCidade, ?int $ignorarDistribuidorId = null): void
    {
        $q = DB::table('city_distribuidor')->whereIn('city_id', $idsCidade);
        if ($ignorarDistribuidorId) {
            $q->where('distribuidor_id', '!=', $ignorarDistribuidorId);
        }
        $ocupadas = $q->pluck('city_id')->toArray();

        if (!empty($ocupadas)) {
            $nomes = City::whereIn('id', $ocupadas)->pluck('name')->toArray();
            throw ValidationException::withMessages([
                'cities' => 'As seguintes cidades já possuem um distribuidor: ' . implode(', ', $nomes),
            ]);
        }
    }

    public function validarCidadesDaUf(array $idsCidade, string $uf): void
    {
        $fora = City::whereIn('id', $idsCidade)->where('state', '!=', $uf)->exists();
        if ($fora) {
            throw ValidationException::withMessages([
                'cities' => 'Há cidades que não pertencem à UF do gestor selecionado.',
            ]);
        }
    }

    public function salvarContrato(?UploadedFile $arquivo): ?string
    {
        return $arquivo ? $arquivo->store('contratos', 'public') : null;
    }
}
