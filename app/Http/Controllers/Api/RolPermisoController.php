<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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

        return response()->json(Rol::create($data), 201);
    }

    public function destroyRol(int $id): JsonResponse
    {
        Rol::findOrFail($id)->delete();

        return response()->json(status: 204);
    }

    public function storePermiso(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nombre_permiso' => 'required|string|max:100|unique:permisos,nombre_permiso',
            'descripcion' => 'nullable|string|max:255',
        ]);

        return response()->json(Permiso::create($data), 201);
    }

    public function destroyPermiso(int $id): JsonResponse
    {
        Permiso::findOrFail($id)->delete();

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

        return response()->json($rol->load('permisos'));
    }
}
