<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->route('usuario')?->id;

            return [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($userId), // ignora o email do próprio usuário ao atualizar
            ],
            'password' => $this->isMethod('post') ? 'required|confirmed|min:6' : 'nullable|confirmed|min:6',
            'telefone' => 'nullable|string|max:20',
            'role' => 'required|string|exists:roles,name',
            'gestor_id' => 'nullable|exists:gestores,id',
        ];
    }
}
