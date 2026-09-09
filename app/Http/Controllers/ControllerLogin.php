<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ControllerLogin extends Controller
{
    public function __invoke(LoginRequest $request)
    {
        $credentials = [
            'username' => $request->username,
            'password' => $request->password,
        ];

        if (Auth::attempt($credentials, $request->remember)) {
            // Regenerar sesión para evitar fijación
            $request->session()->regenerate();

            $user = Auth::user();

            if (!$user->estado_activo) {
                Auth::logout();
                Log::warning('Intento de acceso de usuario deshabilitado', [
                    'username' => $request->username,
                    'ip' => $request->ip()
                ]);
                return back()->withErrors(['login' => 'Credenciales incorrectas o usuario inactivo']);
            }

            // Obtenemos el nombre del rol asignado al usuario
            $rolNombre = $user->rol->nombre_rol ?? null;

            // Si el usuario tiene un rol válido en el sistema (sea admin, mecanico, u otro autorizado)
            if (in_array($rolNombre, ['admin', 'mecanico'])) {
                return redirect()->route('vehiculos.index');
            }

            // Si por algún motivo tiene un rol desconocido o nulo, bloqueamos preventivamente
            Auth::logout();
            return back()->withErrors(['login' => 'Tu rol no tiene permisos para acceder al sistema.']);
        }

        Log::warning('Intento de login fallido', [
            'username' => $request->username,
            'ip' => $request->ip()
        ]);

        return back()->withErrors(['login' => 'Credenciales incorrectas o usuario inactivo']);
    }
}
