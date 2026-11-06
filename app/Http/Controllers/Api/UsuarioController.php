<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UsuarioController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'usuarios' => Usuario::with('rol')->paginate(10),
            'roles' => Rol::all(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'username' => 'required|string|min:3|max:50|unique:usuarios,username',
            'password' => 'required|string|min:6|confirmed',
            'id_rol' => 'required|exists:roles,id_rol',
            'estado_activo' => 'sometimes|boolean',
        ]);

        $usuario = Usuario::create([
            'username' => $data['username'],
            'password_hash' => Hash::make($data['password']),
            'estado_activo' => $data['estado_activo'] ?? true,
            'id_rol' => $data['id_rol'],
        ]);

        AuditLog::registrar(
            'crear',
            'usuarios',
            'Se creó el usuario '.$usuario->username.'.'
        );

        return response()->json($usuario->load('rol'), 201);
    }

    public function update(Request $request, int $usuario): JsonResponse
    {
        $usuario = Usuario::findOrFail($usuario);

        $data = $request->validate([
            'username' => [
                'required',
                'string',
                'min:3',
                'max:50',
                Rule::unique('usuarios', 'username')
                    ->ignore($usuario->id_usuario, 'id_usuario'),
            ],
            'password' => 'nullable|string|min:6|confirmed',
            'id_rol' => 'required|exists:roles,id_rol',
            'estado_activo' => 'required|boolean',
        ]);

        $usuario->fill(
            $request->only('username', 'id_rol', 'estado_activo')
        );

        if (!empty($data['password'])) {
            $usuario->password_hash = Hash::make($data['password']);
        }

        $usuario->save();

        AuditLog::registrar(
            'editar',
            'usuarios',
            'Se actualizó el usuario '.$usuario->username.'.'
        );

        return response()->json($usuario->load('rol'));
    }

    public function desbloquear(int $usuario): JsonResponse
    {
        $usuario = Usuario::findOrFail($usuario);

        $usuario->update([
            'intentos_fallidos' => 0,
            'bloqueado_hasta' => null,
        ]);

        AuditLog::registrar(
            'desbloquear',
            'usuarios',
            'Se desbloqueó manualmente el usuario '.$usuario->username.'.'
        );

        return response()->json([
            'message' => 'Usuario desbloqueado correctamente.',
            'usuario' => $usuario->load('rol'),
        ]);
    }

    public function destroy(int $usuario): JsonResponse
    {
        $usuario = Usuario::findOrFail($usuario);

        if (auth()->id() === $usuario->id_usuario) {
            return response()->json([
                'message' => 'No puedes inhabilitar tu propio usuario.',
            ], 422);
        }

        $usuario->update(['estado_activo' => false]);

        AuditLog::registrar(
            'eliminar',
            'usuarios',
            'Se inhabilitó el usuario '.$usuario->username.'.'
        );

        return response()->json($usuario->load('rol'));
    }
}
