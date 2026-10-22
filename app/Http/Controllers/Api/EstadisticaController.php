<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Mantenimiento;
use Illuminate\Support\Facades\DB;

class EstadisticaController extends Controller
{
    public function index()
    {
        // Total de mantenimientos registrados
        $totalMantenimientos = Mantenimiento::count();

        // Los 5 vehículos con más servicios registrados
        $vehiculosConMasServicios = DB::table('mantenimientos')
            ->join(
                'vehiculos',
                'mantenimientos.id_vehiculo',
                '=',
                'vehiculos.id_vehiculo'
            )
            ->select(
                'vehiculos.id_vehiculo',
                'vehiculos.placa',
                'vehiculos.marca',
                'vehiculos.modelo',
                DB::raw('COUNT(mantenimientos.id_mantenimiento) as total_servicios')
            )
            ->whereNotNull('mantenimientos.id_vehiculo')
            ->groupBy(
                'vehiculos.id_vehiculo',
                'vehiculos.placa',
                'vehiculos.marca',
                'vehiculos.modelo'
            )
            ->orderByDesc('total_servicios')
            ->limit(5)
            ->get();

        return response()->json([
            'total_mantenimientos' => $totalMantenimientos,
            'vehiculos_con_mas_servicios' => $vehiculosConMasServicios,
        ]);
    }
}
