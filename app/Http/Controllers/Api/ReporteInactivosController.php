<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehiculo;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReporteInactivosController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'periodo' => 'sometimes|in:3,6,12',
            'orden' => 'sometimes|in:asc,desc',
        ]);

        $meses = (int) ($data['periodo'] ?? 3);
        $orden = $data['orden'] ?? 'desc';
        $fechaCorte = Carbon::today()->subMonths($meses);

        // Criterio 2: Listado de vehículos que no registran mantenimiento durante el período seleccionado
        $query = Vehiculo::with(['propietario', 'cliente'])
            ->withMax('mantenimientos as fecha_ultimo_mantenimiento', 'fecha_servicio')
            ->whereDoesntHave('mantenimientos', function ($q) use ($fechaCorte) {
                $q->whereDate('fecha_servicio', '>=', $fechaCorte);
            });

        $vehiculos = $query->get()->map(function ($v) {
            $propietario = $v->propietario?->nombre
                ?? ($v->cliente ? trim($v->cliente->nombre . ' ' . $v->cliente->apellido) : 'Sin propietario');

            $telefono = $v->propietario?->telefono
                ?? $v->cliente?->telefono
                ?? 'Sin teléfono';

            return [
                'id_vehiculo' => $v->id_vehiculo,
                'placa' => $v->placa,
                'marca' => $v->marca,
                'modelo' => $v->modelo,
                'anio' => $v->anio,
                'propietario' => $propietario,
                'telefono' => $telefono,
                'fecha_ultimo_mantenimiento' => $v->fecha_ultimo_mantenimiento,
            ];
        });

        // Criterio 4: Ordenar resultados por fecha del último mantenimiento
        $vehiculosOrdenados = $vehiculos->sortBy(function ($item) {
            return $item['fecha_ultimo_mantenimiento'] ?? '0000-00-00';
        }, SORT_REGULAR, $orden === 'desc')->values();

        return response()->json([
            'periodo_meses' => $meses,
            'fecha_corte' => $fechaCorte->toDateString(),
            'total_inactivos' => $vehiculosOrdenados->count(),
            'orden' => $orden,
            'vehiculos' => $vehiculosOrdenados,
        ]);
    }
}

