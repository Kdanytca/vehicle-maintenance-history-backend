<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermiso
{
    public function handle(Request $request, Closure $next, string $permiso): Response
    {
        $usuario = auth()->user();

        if (!$usuario) {
            return redirect('/login');
        }

        // Admin tiene acceso a todo
        if ($usuario->rol->nombre_rol === 'admin') {
            return $next($request);
        }

        // Verificar si el rol del usuario tiene el permiso requerido
        $tienePermiso = $usuario->rol->permisos->contains('nombre_permiso', $permiso);

        if (!$tienePermiso) {
            abort(403, 'No tienes permiso para acceder a esta sección.');
        }

        return $next($request);
    }
}