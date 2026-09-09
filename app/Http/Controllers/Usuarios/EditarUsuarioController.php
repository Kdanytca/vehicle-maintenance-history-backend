<?php

namespace App\Http\Controllers\Usuarios;

use App\Http\Controllers\Controller;
use App\Http\Requests\Usuarios\ActualizarUsuarioRequest;
use App\Models\Usuario;
use App\Models\Rol;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class EditarUsuarioController extends Controller
{
    public function __invoke(ActualizarUsuarioRequest $request, $id)
    {
        $usuario = Usuario::findOrFail($id);
        
        // Datos a actualizar
        $data = [
            'username' => $request->username,
            'id_rol' => $request->id_rol,
            'estado_activo' => $request->estado_activo,
        ];
        
        // Si se proporcionó nueva contraseña, actualizarla
        if ($request->filled('password')) {
            $data['password_hash'] = Hash::make($request->password);
        }
        
        $usuario->update($data);
        
        // Mensaje según si se deshabilitó a sí mismo
        if ($usuario->id_usuario === Auth::id() && !$request->estado_activo) {
            Auth::logout();
            return redirect('/login')->with('warning', 'Tu cuenta ha sido deshabilitada.');
        }
        
        return redirect()->route('usuarios.index')
            ->with('success', 'Usuario actualizado exitosamente.');
    }
}