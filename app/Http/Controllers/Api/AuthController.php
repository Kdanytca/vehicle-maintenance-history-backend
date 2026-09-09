<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        if (! Auth::attempt($request->only('username', 'password'))) {
            return response()->json(['message' => 'Credenciales incorrectas o usuario inactivo'], 422);
        }

        $user = Auth::user()->load('rol.permisos');

        if (! $user->estado_activo || ! in_array($user->rol->nombre_rol ?? null, ['admin', 'mecanico'], true)) {
            Auth::logout();

            return response()->json(['message' => 'Tu usuario no tiene permisos para acceder al sistema'], 403);
        }

        return response()->json([
            'token' => $user->createToken('frontend')->plainTextToken,
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
