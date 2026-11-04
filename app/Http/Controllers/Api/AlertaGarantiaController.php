<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AlertaGarantia;
use App\Services\GenerarAlertasGarantia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AlertaGarantiaController extends Controller
{
    public function index(Request $request, GenerarAlertasGarantia $generador): JsonResponse
    {
        $data = $request->validate(['estado' => 'sometimes|in:activas,atendidas']);
        $generador->ejecutar();
        $query = AlertaGarantia::with(['repuesto.factura.vehiculo', 'atendidaPor:id_usuario,username']);
        if (($data['estado'] ?? 'activas') === 'atendidas') {
            $query->whereNotNull('atendida_at')->orderByDesc('atendida_at');
        } else {
            $query->whereNull('atendida_at');
        }

        return response()->json($query->orderByDesc('id')->get());
    }

    public function atender(Request $request, AlertaGarantia $alerta): JsonResponse
    {
        // Escritura condicional: conserva la primera atención incluso ante peticiones simultáneas.
        AlertaGarantia::whereKey($alerta->id)->whereNull('atendida_at')->update([
            'atendida_at' => now(),
            'id_usuario_atencion' => $request->user()->getKey(),
        ]);

        return response()->json([
            'message' => 'Alerta marcada como atendida.',
            'alerta' => $alerta->refresh()->load('atendidaPor:id_usuario,username'),
        ]);
    }
}
