<?php

namespace App\Http\Requests\Admin\Gestor;

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
            'distribuidores'   => 'array',
            'distribuidores.*' => 'exists:distribuidores,id',
        ];
    }
}
