<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProdutoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'titulo' => $this->titulo,
            'isbn' => $this->isbn,
            'autores' => $this->autores,
            'edicao' => $this->edicao,
            'ano' => $this->ano,
            'numero_paginas' => $this->numero_paginas,
            'peso' => $this->peso,
            'ano_escolar' => $this->ano_escolar,
            'colecao' => $this->colecao?->nome,
            'descricao' => $this->descricao,
            'preco' => $this->preco,
            'quantidade_estoque' => $this->quantidade_estoque,
            'imagem_url' => $this->imagem ? asset("storage/{$this->imagem}") : null,
            'created_at' => $this->created_at->format('d/m/Y H:i'),
        ];
    }
}
