<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProdutoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome' => 'required|string|max:255',
            'titulo' => 'nullable|string|max:255',
            'isbn' => 'nullable|string|max:50',
            'autores' => 'nullable|string|max:255',
            'edicao' => 'nullable|string|max:50',
            'ano' => 'nullable|integer|min:1900|max:' . date('Y'),
            'numero_paginas' => 'nullable|integer|min:1',
            'quantidade_por_caixa' => 'required|integer|min:1',
            'peso' => 'nullable|numeric|min:0',
            'ano_escolar' => 'nullable|in:Ens Inf,Fund 1,Fund 2,EM',
            'colecao_id' => 'nullable|exists:colecoes,id',
            'descricao' => 'nullable|string',
            'preco' => 'required|numeric|min:0',
            'quantidade_estoque' => 'required|integer|min:0',
            'imagem' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
        ];
    }
}
