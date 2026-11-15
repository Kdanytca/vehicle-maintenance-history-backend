<?php

namespace App\Http\Requests;

use App\Models\AuditLog;
use App\Models\Usuario;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username' => 'required|string|max:50',
            'password' => 'required|string',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        $username = $this->input('username');
        $usuario = is_string($username)
            ? Usuario::with('rol')->where('username', $username)->first()
            : null;

        AuditLog::registrarAcceso(is_string($username) ? mb_substr($username, 0, 100) : null, $usuario, 'fallido');

        parent::failedValidation($validator);
    }

    public function messages(): array
    {
        return [
            'username.required' => 'El nombre de usuario es obligatorio',
            'password.required' => 'La contraseña es obligatoria',
        ];
    }
}
