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
        $resumen = null;

        if ($request->filled('id_vehiculo')) {
            $vehiculo = Vehiculo::with('propietario')->findOrFail($request->id_vehiculo);
            $mantenimientos = Mantenimiento::with(['encargado.rol', 'repuestos'])
                ->where('id_vehiculo', $vehiculo->id_vehiculo)
                ->orderByDesc('fecha_servicio')
                ->get()
                ->each(function ($m) {
                    $m->total_repuestos = round((float) $m->repuestos->sum('costo_unitario'), 2);
                    $m->costo_total = round((float) $m->costo_mano_obra + $m->total_repuestos, 2);
                });
            $historial = HistorialCambio::with('usuario')
                ->where('descripcion_evento', 'like', '%' . $vehiculo->placa . '%')
                ->orderByDesc('created_at')
                ->get();

            $costoManoObra = round((float) $mantenimientos->sum('costo_mano_obra'), 2);
            $costoRepuestos = round((float) $mantenimientos->sum('total_repuestos'), 2);

            $resumen = [
                'total_servicios' => $mantenimientos->count(),
                'costo_mano_obra' => $costoManoObra,
                'costo_repuestos' => $costoRepuestos,
                'costo_total' => round($costoManoObra + $costoRepuestos, 2),
                'ultimo_servicio' => $mantenimientos->first()?->fecha_servicio,
                'por_estado' => $mantenimientos->countBy('estado'),
            ];
        }

        return response()->json(compact('vehiculos', 'vehiculo', 'mantenimientos', 'historial', 'resumen'));
    }
}
