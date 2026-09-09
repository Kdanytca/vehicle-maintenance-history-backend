<?php

namespace App\Http\Controllers;

use App\Models\Rol;
use App\Models\Permiso;
use Illuminate\Http\Request;

class RolPermisoController extends Controller
{
    public function index()
    {
        $roles = Rol::with('permisos')->get();
        $permisos = Permiso::all();
        return view('roles_permisos.index', compact('roles', 'permisos'));
    }

    public function storeRol(Request $request)
    {
        $request->validate([
            'nombre_rol'  => 'required|string|max:50|unique:roles,nombre_rol',
            'descripcion' => 'nullable|string|max:255',
        ]);

        Rol::create($request->only('nombre_rol', 'descripcion'));

        return redirect()->route('roles-permisos.index')
            ->with('success', 'Rol creado exitosamente.');
    }

    public function destroyRol($id)
    {
        $rol = Rol::findOrFail($id);
        $rol->delete();
        return redirect()->route('roles-permisos.index')
            ->with('success', 'Rol eliminado.');
    }

    public function storePermiso(Request $request)
    {
        $request->validate([
            'nombre_permiso' => 'required|string|max:100|unique:permisos,nombre_permiso',
            'descripcion'    => 'nullable|string|max:255',
        ]);

        Permiso::create($request->only('nombre_permiso', 'descripcion'));

        return redirect()->route('roles-permisos.index')
            ->with('success', 'Permiso creado exitosamente.');
    }

    public function destroyPermiso($id)
    {
        $permiso = Permiso::findOrFail($id);
        $permiso->delete();
        return redirect()->route('roles-permisos.index')
            ->with('success', 'Permiso eliminado.');
    }

    public function asignarPermisos(Request $request, $id_rol)
    {
        $rol = Rol::findOrFail($id_rol);
        $permisos = $request->input('permisos', []);
        $rol->permisos()->sync($permisos);

        return redirect()->route('roles-permisos.index')
            ->with('success', "Permisos del rol '{$rol->nombre_rol}' actualizados.");
    }
}