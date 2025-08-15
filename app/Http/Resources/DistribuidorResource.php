<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DistribuidorResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'                 => $this->id,
            'razao_social'       => $this->razao_social,
            'cnpj'               => $this->cnpj,
            'representante_legal'=> $this->representante_legal,
            'cpf'                => $this->cpf,
            'rg'                 => $this->rg,
            'telefone'           => $this->telefone,
            'endereco_completo'  => $this->endereco_completo,
            'percentual_vendas'  => $this->percentual_vendas,
            'vencimento_contrato'=> $this->vencimento_contrato,
            'contrato_assinado'  => (bool)$this->contrato_assinado,

            'user' => $this->whenLoaded('user', fn() => [
                'id'    => $this->user->id,
                'name'  => $this->user->name,
                'email' => $this->user->email,
            ]),

            'gestor' => $this->whenLoaded('gestor', fn() => [
                'id' => $this->gestor->id,
                'uf' => $this->gestor->estado_uf,
                'user' => $this->whenLoaded('gestor.user', fn() => [
                    'id'    => $this->gestor->user->id,
                    'name'  => $this->gestor->user->name,
                    'email' => $this->gestor->user->email,
                ]),
            ]),

            'cities' => $this->whenLoaded('cities', fn() =>
                $this->cities->map(fn($c) => [
                    'id' => $c->id, 'name' => $c->name, 'state' => $c->state
                ])
            ),
        ];
    }
}
