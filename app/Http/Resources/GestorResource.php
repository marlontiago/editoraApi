<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GestorResource extends JsonResource
{
    
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'razao_social'       => $this->razao_social,
            'estado_uf'          => $this->estado_uf,
            'cnpj'               => $this->cnpj,
            'representante_legal'=> $this->representante_legal,
            'cpf'                => $this->cpf,
            'rg'                 => $this->rg,
            'telefone'           => $this->telefone,
            'email'              => $this->email,
            'endereco_completo'  => $this->endereco_completo,
            'percentual_vendas'  => $this->percentual_vendas,
            'vencimento_contrato'=> $this->vencimento_contrato,
            'contrato_assinado'  => (bool)$this->contrato_assinado,
            'user'               => [
                'id'    => $this->user->id,
                'name'  => $this->user->name,
                'email' => $this->user->email,
            ],
            'cities'             => $this->whenLoaded('cities', fn () => $this->cities->map(fn ($c) => [
                'id' => $c->id, 'name' => $c->name, 'state' => $c->state,
            ])),
        ];
    }
}
