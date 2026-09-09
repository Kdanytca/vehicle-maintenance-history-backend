<?php

namespace App\Http\Requests\Usuarios;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActualizarUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $usuarioId = $this->route('id');
        
        return [
            'username' => [
                'required',
                'string',
                'min:3',
                'max:50',
                Rule::unique('usuarios', 'username')->ignore($usuarioId, 'id_usuario'),
            ],
            'password' => 'nullable|string|min:6|confirmed',
            'id_rol' => 'required|exists:roles,id_rol',
            'estado_activo' => 'required|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'username.required' => 'El nombre de usuario es obligatorio.',
            'username.unique' => 'Este nombre de usuario ya está en uso.',
            'username.min' => 'El nombre de usuario debe tener al menos 3 caracteres.',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
            'password.confirmed' => 'La confirmación de contraseña no coincide.',
            'id_rol.required' => 'Debe seleccionar un rol.',
            'id_rol.exists' => 'El rol seleccionado no es válido.',
            'estado_activo.required' => 'El estado es obligatorio.',
        ];
    }
}