<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGestorRequest extends FormRequest
{
    
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $gestor = $this->route('gestor');

        return [
            'razao_social'        => 'required|string|max:255',
            'estado_uf'           => 'nullable|string|size:2',
            'cnpj'                => 'required|string|max:20',
            'representante_legal' => 'required|string|max:255',
            'cpf'                 => 'required|string|max:20',
            'rg'                  => 'required|string|max:20',
            'telefone'            => 'required|string|max:20',
            'email'               => [
                'nullable',
                'email',
                Rule::unique('users', 'email')->ignore($gestor->user_id),
            ],
            'password'            => 'nullable|string|min:6',
            'endereco_completo'   => 'nullable|string|max:255',
            'percentual_vendas'   => 'required|numeric|min:0|max:100',
            'vencimento_contrato' => 'nullable|date',
            'contrato_assinado'   => 'nullable|boolean',
            'contrato'            => 'nullable|file|mimes:pdf|max:2048',
        ];
    }

    public function validated($key = null, $default = null)
    {
        $data = parent::validated($key, $default);
        $data['contrato_assinado'] = (bool)($data['contrato_assinado'] ?? false);
        return $data;
    }
}
