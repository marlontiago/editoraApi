<?php

namespace App\Services\Admin;

use App\Models\Gestor;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class GestorService
{
    public function create(array $data): Gestor
    {
        return DB::transaction(function () use ($data) {
            // Upload contrato (opcional)
            $contratoPath = $this->storeContratoIfAny($data['contrato'] ?? null);

            // Cria usuário
            /** @var User $user */
            $user = User::create([
                'name'     => $data['razao_social'],
                'email'    => $data['email'],
                'password' => Hash::make($data['password']),
            ]);
            $user->assignRole('gestor');

            // Cria gestor
            /** @var Gestor $gestor */
            $gestor = Gestor::create([
                'user_id'             => $user->id,
                'estado_uf'           => $data['estado_uf'] ?? null,
                'razao_social'        => $data['razao_social'],
                'cnpj'                => $data['cnpj'],
                'representante_legal' => $data['representante_legal'],
                'cpf'                 => $data['cpf'],
                'rg'                  => $data['rg'],
                'telefone'            => $data['telefone'],
                'email'               => $data['email'],
                'endereco_completo'   => $data['endereco_completo'] ?? null,
                'percentual_vendas'   => $data['percentual_vendas'],
                'vencimento_contrato' => $data['vencimento_contrato'] ?? null,
                'contrato_assinado'   => (bool)($data['contrato_assinado'] ?? false),
                'contrato'            => $contratoPath,
            ]);

            // Sincroniza cidades (opcional)
            if (!empty($data['cities']) && is_array($data['cities'])) {
                $gestor->cities()->sync($data['cities']);
            }

            return $gestor->load('user', 'cities');
        });
    }

    public function update(Gestor $gestor, array $data): Gestor
    {
        return DB::transaction(function () use ($gestor, $data) {
            // Novo contrato? Substitui (remove o antigo para evitar lixo)
            if (!empty($data['contrato']) && $data['contrato'] instanceof UploadedFile) {
                if ($gestor->contrato && Storage::disk('public')->exists($gestor->contrato)) {
                    Storage::disk('public')->delete($gestor->contrato);
                }
                $gestor->contrato = $this->storeContratoIfAny($data['contrato']);
            }

            // Atualiza Gestor
            $gestor->fill([
                'razao_social'        => $data['razao_social'],
                'estado_uf'           => $data['estado_uf'] ?? null,
                'cnpj'                => $data['cnpj'],
                'representante_legal' => $data['representante_legal'],
                'cpf'                 => $data['cpf'],
                'rg'                  => $data['rg'],
                'telefone'            => $data['telefone'],
                'email'               => $data['email'] ?? $gestor->email, // mantém se não vier
                'endereco_completo'   => $data['endereco_completo'] ?? null,
                'percentual_vendas'   => $data['percentual_vendas'],
                'vencimento_contrato' => $data['vencimento_contrato'] ?? null,
                'contrato_assinado'   => (bool)($data['contrato_assinado'] ?? false),
            ])->save();

            // Atualiza User (nome sempre; e-mail só se enviado)
            $userData = ['name' => $data['razao_social']];
            if (!empty($data['email'])) {
                $userData['email'] = $data['email'];
            }
            $gestor->user->update($userData);

            // Sincroniza cidades
            if (array_key_exists('cities', $data)) {
                $gestor->cities()->sync($data['cities'] ?? []);
            }

            return $gestor->fresh()->load('user', 'cities');
        });
    }

    public function delete(Gestor $gestor): void
    {
        DB::transaction(function () use ($gestor) {
            $gestor->cities()->detach();

            if ($gestor->contrato && Storage::disk('public')->exists($gestor->contrato)) {
                Storage::disk('public')->delete($gestor->contrato);
            }

            // Remove também o usuário vinculado
            $gestor->user()->delete();
            $gestor->delete();
        });
    }

    private function storeContratoIfAny(?UploadedFile $file): ?string
    {
        return $file ? $file->store('contratos', 'public') : null;
    }
}
