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
        $usuario = Usuario::with('rol')->where('username', $request->string('username')->toString())->first();

        if (! Auth::attempt($request->only('username', 'password'))) {
            AuditLog::registrarAcceso($request->input('username'), $usuario, 'fallido');

            return response()->json(['message' => 'Credenciales incorrectas o usuario inactivo'], 422);
        }

        $user = Auth::user()->load('rol.permisos');

        if (! $user->estado_activo || ! in_array($user->rol->nombre_rol ?? null, ['admin', 'mecanico'], true)) {
            AuditLog::registrarAcceso($user->username, $user, 'fallido');
            Auth::logout();

            return response()->json(['message' => 'Tu usuario no tiene permisos para acceder al sistema'], 403);
        }

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

        return response()->json(['message' => 'Sesion cerrada correctamente']);
    }
}
