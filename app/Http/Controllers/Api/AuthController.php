<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\AuditLog;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $usuario = Usuario::with('rol')
            ->where('username', $request->string('username')->toString())
            ->first();

        if (!$usuario) {
            AuditLog::registrarAcceso(
                $request->input('username'),
                null,
                'fallido'
            );

            return response()->json([
                'message' => 'Credenciales incorrectas o usuario inactivo'
            ], 422);
        }

        // Comprobar que la cuenta esté activa y tenga un rol permitido.
        if (
            !$usuario->estado_activo ||
            !in_array($usuario->rol->nombre_rol ?? null, ['admin', 'mecanico'], true)
        ) {
            AuditLog::registrarAcceso($usuario->username, $usuario, 'fallido');

            return response()->json([
                'message' => 'Tu usuario no tiene permisos para acceder al sistema'
            ], 403);
        }

        // Comprobar si la cuenta continúa bloqueada.
        if ($usuario->bloqueado_hasta && now()->lt($usuario->bloqueado_hasta)) {
            $segundosRestantes = max(
                0,
                now()->diffInSeconds($usuario->bloqueado_hasta, false)
            );

            return response()->json([
                'message' => 'Tu cuenta está bloqueada temporalmente.',
                'segundos_restantes' => $segundosRestantes
            ], 423);
        }

        // Restablecer el bloqueo vencido.
        if ($usuario->bloqueado_hasta && now()->gte($usuario->bloqueado_hasta)) {
            $usuario->intentos_fallidos = 0;
            $usuario->bloqueado_hasta = null;
            $usuario->save();
        }

        // Validar las credenciales.
        if (!Auth::attempt($request->only('username', 'password'))) {
            $usuario->intentos_fallidos++;

            if ($usuario->intentos_fallidos >= 3) {
                $usuario->intentos_fallidos = 3;
                $usuario->bloqueado_hasta = now()->addMinutes(5);
            }

            $usuario->save();

            AuditLog::registrarAcceso($usuario->username, $usuario, 'fallido');

            if ($usuario->bloqueado_hasta) {
                return response()->json([
                    'message' => 'Has superado los 3 intentos. Tu cuenta está bloqueada durante 5 minutos.',
                    'segundos_restantes' => 300
                ], 423);
            }

            return response()->json([
                'message' => 'Credenciales incorrectas. Intentos restantes: ' .
                    (3 - $usuario->intentos_fallidos)
            ], 422);
        }

        $user = Auth::user()->load('rol.permisos');

        // Reiniciar el contador después del inicio de sesión exitoso.
        $user->intentos_fallidos = 0;
        $user->bloqueado_hasta = null;
        $user->save();

        $token = $user->createToken('frontend')->plainTextToken;

        AuditLog::registrarAcceso($user->username, $user, 'exitoso');

        return response()->json([
            'token' => $token,
            'user' => $user,
        ]);
    }

    public function me(): JsonResponse
    {
        return response()->json(auth()->user()->load('rol.permisos'));
    }

    public function logout(): JsonResponse
    {
        auth()->user()->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Sesion cerrada correctamente'
        ]);
    }
}
