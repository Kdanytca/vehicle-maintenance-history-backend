<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Configuracion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConfiguracionAlertaGarantiaController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json([
            'dias_previos' => Configuracion::diasPreviosAlertaGarantia(),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'dias_previos' => ['required', 'integer', 'min:1'],
        ]);

        $configuracion = Configuracion::updateOrCreate(
            ['clave' => Configuracion::CLAVE_DIAS_ALERTA_GARANTIA],
            ['valor' => $data['dias_previos']],
        );

        return response()->json([
            'message' => 'Configuración de alertas de garantía actualizada.',
            'dias_previos' => $configuracion->valor,
        ]);
    }
}
