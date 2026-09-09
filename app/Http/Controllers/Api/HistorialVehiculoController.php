<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HistorialCambio;
use App\Models\Mantenimiento;
use App\Models\Vehiculo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HistorialVehiculoController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $vehiculos = Vehiculo::with('propietario')->get();
        $vehiculo = null;
        $mantenimientos = [];
        $historial = [];

        if ($request->filled('id_vehiculo')) {
            $vehiculo = Vehiculo::with('propietario')->findOrFail($request->id_vehiculo);
            $mantenimientos = Mantenimiento::where('id_vehiculo', $vehiculo->id_vehiculo)->orderByDesc('fecha_servicio')->get();
            $historial = HistorialCambio::with('usuario')
                ->where('descripcion_evento', 'like', '%' . $vehiculo->placa . '%')
                ->orderByDesc('created_at')
                ->get();
        }

        return response()->json(compact('vehiculos', 'vehiculo', 'mantenimientos', 'historial'));
    }
}
