<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreDistribuidorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                 => 'nullable|string|max:255',
            'email'                => 'required|email|unique:users,email',
            'password'             => 'required|string|min:6|confirmed',
            'gestor_id'            => 'required|exists:gestores,id',
            'razao_social'         => 'required|string|max:255',
            'cnpj'                 => 'required|string|max:20',
            'representante_legal'  => 'required|string|max:255',
            'cpf'                  => 'required|string|max:20',
            'rg'                   => 'required|string|max:20',
            'telefone'             => 'nullable|string|max:20',
            'endereco_completo'    => 'nullable|string|max:255',
            'percentual_vendas'    => 'required|numeric|min:0|max:100',
            'vencimento_contrato'  => 'nullable|date',
            'contrato_assinado'    => 'nullable|boolean',
            'contrato'             => 'nullable|file|mimes:pdf|max:2048',
            'cities'               => 'required|array|min:1',
            'cities.*'             => 'integer|exists:cities,id',
        ];
    }

    public function validated($key = null, $default = null)
    {
        $data = parent::validated($key, $default);
        $data['contrato_assinado'] = (bool)($data['contrato_assinado'] ?? false);
        return $data;
    }
}
