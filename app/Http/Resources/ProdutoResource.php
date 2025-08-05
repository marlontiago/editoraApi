<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProdutoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'descricao' => $this->descricao,
            'preco' => $this->preco,
            'quantidade_estoque' => $this->quantidade_estoque,
            'imagem_url' => $this->imagem ? asset("storage/{$this->imagem}") : null,
            'created_at' => $this->created_at->format('d/m/Y H:i'),
        ];
    }
}
