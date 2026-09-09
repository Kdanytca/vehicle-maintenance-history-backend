<?php

namespace App\Http\Controllers\Usuarios;

use App\Http\Controllers\Controller;
use App\Http\Requests\Usuarios\CrearUsuarioRequest;
use App\Models\Usuario;
use App\Models\Rol;
use Illuminate\Support\Facades\Hash;

class CrearUsuarioController extends Controller
{
    public function __invoke(CrearUsuarioRequest $request)
    {
        // Crear el usuario
        Usuario::create([
            'username' => $request->username,
            'password_hash' => Hash::make($request->password),
            'estado_activo' => $request->estado_activo ?? true,
            'id_rol' => $request->id_rol,
        ]);

        // Redirigir al listado con mensaje de éxito
        return redirect()->route('usuarios.index')
            ->with('success', 'Usuario creado exitosamente.');
    }
}