<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Permiso;
use App\Models\Rol;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RolPermisoController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'roles' => Rol::with('permisos')->get(),
            'permisos' => Permiso::all(),
        ]);
    }

    public function storeRol(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nombre_rol' => 'required|string|max:50|unique:roles,nombre_rol',
            'descripcion' => 'nullable|string|max:255',
        ]);

        $rol = Rol::create($data);

        AuditLog::registrar(
            'crear',
            'roles',
            'Se creó el rol '.$rol->nombre_rol.'.'
        );

        return response()->json($rol, 201);
    }

    public function destroyRol(int $id): JsonResponse
    {
        $rol = Rol::findOrFail($id);
        $nombreRol = $rol->nombre_rol;
        $rol->delete();

        AuditLog::registrar(
            'eliminar',
            'roles',
            'Se eliminó el rol '.$nombreRol.'.'
        );

        return response()->json(status: 204);
    }

    public function storePermiso(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nombre_permiso' => 'required|string|max:100|unique:permisos,nombre_permiso',
            'descripcion' => 'nullable|string|max:255',
        ]);

        $permiso = Permiso::create($data);

        AuditLog::registrar(
            'crear',
            'permisos',
            'Se creó el permiso '.$permiso->nombre_permiso.'.'
        );

        return response()->json($permiso, 201);
    }

    public function destroyPermiso(int $id): JsonResponse
    {
        $permiso = Permiso::findOrFail($id);
        $nombrePermiso = $permiso->nombre_permiso;
        $permiso->delete();

        AuditLog::registrar(
            'eliminar',
            'permisos',
            'Se eliminó el permiso '.$nombrePermiso.'.'
        );

        return response()->json(status: 204);
    }

    public function asignarPermisos(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'permisos' => 'array',
            'permisos.*' => 'exists:permisos,id_permiso',
        ]);

        $rol = Rol::findOrFail($id);
        $rol->permisos()->sync($data['permisos'] ?? []);

        AuditLog::registrar(
            'editar',
            'roles',
            'Se actualizó la asignación de permisos del rol '.$rol->nombre_rol.'.'
        );

        return response()->json($rol->load('permisos'));
    }
}
