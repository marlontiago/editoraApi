<?php

namespace App\Http\Requests\Admin\Gestor;

use Illuminate\Foundation\Http\FormRequest;

class StoreGestorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ajuste se usar policies
    }

    public function rules(): array
    {
        return [
            'razao_social'        => 'required|string|max:255',
            'estado_uf'           => 'nullable|string|size:2',
            'cnpj'                => 'required|string|max:20',
            'representante_legal' => 'required|string|max:255',
            'cpf'                 => 'required|string|max:20',
            'rg'                  => 'required|string|max:20',
            'telefone'            => 'required|string|max:20',
            'email'               => 'required|email|unique:users,email',
            'password'            => 'required|string|min:6',
            'endereco_completo'   => 'nullable|string|max:255',
            'percentual_vendas'   => 'required|numeric|min:0|max:100',
            'vencimento_contrato' => 'nullable|date',
            'contrato_assinado'   => 'nullable|boolean',
            'contrato'            => 'nullable|file|mimes:pdf|max:5120',
            'cities'              => 'array|nullable',
            'cities.*'            => 'integer|exists:cities,id',
        ];
    }
}
