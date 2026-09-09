<?php

namespace App\Http\Controllers;

use App\Models\Vehiculo;
use App\Models\Mantenimiento;
use App\Models\HistorialCambio;
use Illuminate\Http\Request;

class HistorialVehiculoController extends Controller
{
    public function index(Request $request)
    {
        $vehiculos = Vehiculo::with('propietario')->get();
        $vehiculo  = null;
        $mantenimientos = collect();
        $historial = collect();

        if ($request->filled('id_vehiculo')) {
            $vehiculo = Vehiculo::with('propietario')->findOrFail($request->id_vehiculo);

            $mantenimientos = Mantenimiento::where('id_vehiculo', $vehiculo->id_vehiculo)
                ->orderBy('fecha_servicio', 'desc')
                ->get();

            $historial = HistorialCambio::with('usuario')
                ->where('descripcion_evento', 'like', '%' . $vehiculo->placa . '%')
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return view('historial_vehiculo.index', compact('vehiculos', 'vehiculo', 'mantenimientos', 'historial'));
    }
}