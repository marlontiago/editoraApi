<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class VincularDistribuidoresRequest extends FormRequest
{
    
    public function authorize(): bool
    {
        return true;
    }

    
    public function rules(): array
    {
        return [
            'gestor_id'        => 'required|exists:gestores,id',
            'distribuidores'   => 'array|nullable',
            'distribuidores.*' => 'integer|exists:distribuidores,id',
        ];
    }

    public function validated($key = null, $default = null)
    {
        $data = parent::validated($key, $default);
        $data['distribuidores'] = $data['distribuidores'] ?? [];
        return $data;
    }
}
