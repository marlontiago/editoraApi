<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CidadesDisponiveisResource extends JsonResource
{
    
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this['id'],
            'name'               => $this['name'],
            'occupied'           => (bool) $this['occupied'],
            'distribuidor_id'    => $this['distribuidor_id'],
            'distribuidor_name'  => $this['distribuidor_name'],
            'distribuidor_email' => $this['distribuidor_email'],
        ];
    }
}
