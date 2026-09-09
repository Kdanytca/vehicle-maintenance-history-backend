<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Usuario;
use App\Models\Rol;
use App\Models\Permiso;
use Illuminate\Support\Facades\Hash;

class UsuariosSeeder extends Seeder
{
    public function run(): void
    {
        $rolAdmin = Rol::updateOrCreate(
            ['nombre_rol' => 'admin'],
            ['descripcion' => 'Administrador del sistema']
        );

        $rolMecanico = Rol::updateOrCreate(
            ['nombre_rol' => 'mecanico'],
            ['descripcion' => 'Mecánico del taller']
        );

        $permisos = collect([
            ['nombre_permiso' => 'gestionar_usuarios', 'descripcion' => 'Crear y administrar usuarios del sistema'],
            ['nombre_permiso' => 'gestionar_roles', 'descripcion' => 'Crear roles y asignar permisos'],
            ['nombre_permiso' => 'gestionar_vehiculos', 'descripcion' => 'Registrar y consultar vehículos'],
            ['nombre_permiso' => 'gestionar_mantenimientos', 'descripcion' => 'Registrar mantenimientos y consultar historial'],
            ['nombre_permiso' => 'gestionar_repuestos', 'descripcion' => 'Cargar facturas, repuestos y alertas'],
        ])->map(fn ($permiso) => Permiso::updateOrCreate(
            ['nombre_permiso' => $permiso['nombre_permiso']],
            ['descripcion' => $permiso['descripcion']]
        ));

        $rolAdmin->permisos()->sync($permisos->pluck('id_permiso'));
        $rolMecanico->permisos()->sync(
            $permisos
                ->whereIn('nombre_permiso', [
                    'gestionar_vehiculos',
                    'gestionar_mantenimientos',
                    'gestionar_repuestos',
                ])
                ->pluck('id_permiso')
        );

        Usuario::updateOrCreate([
            'username' => 'admin',
        ], [
            'password_hash' => Hash::make('12345678'),
            'estado_activo' => true,
            'id_rol' => $rolAdmin->id_rol,
        ]);

        Usuario::updateOrCreate([
            'username' => 'mecanico',
        ], [
            'password_hash' => Hash::make('12345678'),
            'estado_activo' => true,
            'id_rol' => $rolMecanico->id_rol,
        ]);

        Usuario::updateOrCreate([
            'username' => 'suspendido',
        ], [
            'password_hash' => Hash::make('12345678'),
            'estado_activo' => false,
            'id_rol' => $rolMecanico->id_rol,
        ]);
    }
}
