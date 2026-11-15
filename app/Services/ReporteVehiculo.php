<?php

namespace App\Services;

use App\Models\Mantenimiento;
use App\Models\Vehiculo;
use Illuminate\Http\Exceptions\HttpResponseException;

class ReporteVehiculo
{
    public function porPlaca(string $placa): array
    {
        $placaTrim = trim($placa);
        $vehiculo = Vehiculo::withTrashed()->with('propietario')
            ->whereRaw('UPPER(TRIM(placa)) = ?', [strtoupper($placaTrim)])
            ->first();

        if (! $vehiculo) {
            $placaSinGuiones = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $placaTrim));
            $vehiculo = Vehiculo::withTrashed()->with('propietario')
                ->whereRaw("UPPER(REPLACE(REPLACE(placa, '-', ''), ' ', '')) = ?", [$placaSinGuiones])
                ->first();
        }

        if (! $vehiculo) {
            throw new HttpResponseException(response()->json(['message' => 'La placa ingresada no existe.'], 404));
        }

        $mantenimientos = Mantenimiento::with(['encargado.rol'])->where('id_vehiculo', $vehiculo->id_vehiculo)
            ->orderBy('fecha_servicio')->orderBy('id_mantenimiento')->get();

        if ($mantenimientos->isEmpty()) {
            throw new HttpResponseException(response()->json([
                'message' => 'No existen mantenimientos para esta placa.',
                'vehiculo' => $vehiculo,
                'mantenimientos' => [],
            ], 404));
        }

        return compact('vehiculo', 'mantenimientos');
    }
}
