<?php

namespace App\Http\Controllers\Usuarios;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use Illuminate\Support\Facades\Auth;

class EliminarUsuarioController extends Controller
{
    public function __invoke($id)
    {
        $usuario = Usuario::findOrFail($id);
        
        // No permitir deshabilitarse a sí mismo
        if ($usuario->id_usuario === Auth::id()) {
            return redirect()->route('usuarios.index')
                ->with('error', 'No puedes deshabilitar tu propia cuenta.');
        }
        
        // Cambiar estado_activo a false (deshabilitar)
        $usuario->update(['estado_activo' => false]);
        
        return redirect()->route('usuarios.index')
            ->with('success', 'Usuario deshabilitado exitosamente.');
    }
}