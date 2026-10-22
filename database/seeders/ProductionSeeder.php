<?php

namespace Database\Seeders;

use App\Models\Permiso;
use App\Models\Rol;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $admin = Rol::firstOrCreate(
                ['nombre_rol' => 'admin'],
                ['descripcion' => 'Administrador del sistema']
            );
            $mecanico = Rol::firstOrCreate(
                ['nombre_rol' => 'mecanico'],
                ['descripcion' => 'Mecánico del taller']
            );

            $permisos = collect([
                'gestionar_usuarios' => 'Crear y administrar usuarios del sistema',
                'gestionar_roles' => 'Crear roles y asignar permisos',
                'gestionar_vehiculos' => 'Registrar y consultar vehículos',
                'gestionar_mantenimientos' => 'Registrar mantenimientos y consultar historial',
                'gestionar_repuestos' => 'Cargar facturas, repuestos y alertas',
            ])->map(fn ($descripcion, $nombre) => Permiso::firstOrCreate(
                ['nombre_permiso' => $nombre],
                ['descripcion' => $descripcion]
            ));

            // Agrega permisos base sin quitar asignaciones personalizadas.
            $admin->permisos()->syncWithoutDetaching($permisos->pluck('id_permiso'));
            $mecanico->permisos()->syncWithoutDetaching(
                $permisos->only([
                    'gestionar_vehiculos',
                    'gestionar_mantenimientos',
                    'gestionar_repuestos',
                ])->pluck('id_permiso')
            );
        });
    }
}
