<?php

namespace App\Services;

use App\Models\Gestor;
use App\Models\User;
use App\Models\City;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Arr;

class GestorService
{
    public function create(array $data): Gestor
    {
        return DB::transaction(function () use ($data){
            
            $user = User::create([
                'name' => $data['razao_social'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ]);

            $user->assignRole('gestor');

            $contratoPath = $this->storeContrato($data['contrato'] ?? null);

            $gestor = Gestor::create([
                'user_id'             => $user->id,
                'estado_uf'           => $data['estado_uf'] ?? null,
                'razao_social'        => $data['razao_social'],
                'cnpj'                => $data['cnpj'],
                'representante_legal' => $data['representante_legal'],
                'cpf'                 => $data['cpf'],
                'rg'                  => $data['rg'] ?? null,
                'telefone'            => $data['telefone'],
                'email'               => $data['email'],
                'endereco_completo'   => $data['endereco_completo'] ?? null,
                'percentual_vendas'   => $data['percentual_vendas'],
                'vencimento_contrato' => $data['vencimento_contrato'] ?? null,
                'contrato_assinado'   => (bool)($data['contrato_assinado'] ?? false),
                'contrato'            => $contratoPath,
            ]);


            return $gestor->load(['user', 'cities']);
        });
    }

    public function update(Gestor $gestor, array $data): Gestor
    {
        return DB::transaction(function () use ($gestor, $data){
            if(isset($data['contrato']) && $data['contrato'] instanceof UploadedFile){
                $gestor->contrato = $this->storeContrato($data['contrato']);
            }

            $gestor->fill([
                'razao_social'        => $data['razao_social'],
                'estado_uf'           => $data['estado_uf'] ?? null,
                'cnpj'                => $data['cnpj'],
                'representante_legal' => $data['representante_legal'],
                'cpf'                 => $data['cpf'],
                'rg'                  => $data['rg'] ?? null,
                'telefone'            => $data['telefone'],
                'email'               => $data['email'] ?? $gestor->email,
                'endereco_completo'   => $data['endereco_completo'] ?? null,
                'percentual_vendas'   => $data['percentual_vendas'],
                'vencimento_contrato' => $data['vencimento_contrato'] ?? null,
                'contrato_assinado'   => (bool)($data['contrato_assinado'] ?? false),
            ])->save();

            $userData = ['name' => $data['razao_social']];
            if(!empty($data['email'])){
                $userData['email'] = $data['email'];
            }
            if(!empty($data['password'])){
                $userData['password'] = Hash::make($data['password']);
            }
            $gestor->user()->update($userData);

            
            return $gestor->load(['user','cities']);
        });
    }

    public function delete(Gestor $gestor): void
    {
        DB::transaction(function () use ($gestor){
            $gestor->cities()->detach();
            $gestor->user()->delete();
            $gestor->delete();
        });
    }

    public function vincularDistribuidores(int $gestorId, array $distribuidorIds): void
    {
        DB::table('distribuidores')
            ->whereIn('id', $distribuidorIds)
            ->update(['gestor_id' => $gestorId]);
    }

    public function cidadesPorGestor(Gestor $gestor)
    {
        if(!$gestor->estado_uf){
            return collect();
        }

        $cidades = City::where('state', strtoupper($gestor->estado_uf))
                                ->orderBy('name')
                                ->get(['id', 'name']);

        if($cidades->isEmpty()){
            return collect();
        }

        $ocupacoes = DB::table('city_distribuidor as cd')
                        ->join('distribuidores as d', 'd.id', '=', 'cd.distribuidor_id')
                        ->leftJoin('users as u', 'u.id', '=', 'd.user_id')
                        ->whereIn('cd.city_id', $cidades->pluck('id'))
                        ->select([
                            'cd.city_id',
                            'd.id as distribuidor_id',
                            'd.razao_social',
                            'u.name as user_name',
                            'u.email as user_email',
                        ])->get()->keyBy('city_id');

        return $cidades->map(function ($c) use ($ocupacoes){
            $occ = $ocupacoes->get($c->id);
            return [
                'id'                 => $c->id,
                'name'               => $c->name,
                'occupied'           => (bool) $occ,
                'distribuidor_id'    => $occ->distribuidor_id ?? null,
                'distribuidor_name'  => $occ->user_name ?? $occ->razao_social ?? null,
                'distribuidor_email' => $occ->user_email ?? null,
            ];
        });
    }

    private function storeContrato(?UploadedFile $file): ?string
    {
        return $file ? $file->store('contratos', 'public'): null;
    }
}