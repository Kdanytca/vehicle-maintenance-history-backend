<?php

namespace App\Services;

use App\Models\Mantenimiento;
use App\Models\Vehiculo;
use Illuminate\Http\Exceptions\HttpResponseException;

class ReporteVehiculo
{
    public function porPlaca(string $placa): array
    {
        $vehiculo = Vehiculo::withTrashed()->with('propietario')->where('placa', trim($placa))->first();
        if (! $vehiculo) {
            throw new HttpResponseException(response()->json(['message' => 'La placa ingresada no existe.'], 404));
        }
        $mantenimientos = Mantenimiento::with('encargado')->where('id_vehiculo', $vehiculo->id_vehiculo)
            ->orderBy('fecha_servicio')->orderBy('id_mantenimiento')->get();
        if ($mantenimientos->isEmpty()) {
            throw new HttpResponseException(response()->json(['message' => 'No existen mantenimientos para esta placa.', 'vehiculo' => $vehiculo], 404));
        }

        return compact('vehiculo', 'mantenimientos');
    }
}
