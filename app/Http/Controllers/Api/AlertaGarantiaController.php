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

    public function vencidas(Request $request, GenerarAlertasGarantia $generador): JsonResponse
    {
        $generador->ejecutar();
        $hoy = today();

        // Criterio 2: Solo deben mostrarse garantías cuya fecha haya superado la fecha actual
        $query = AlertaGarantia::with([
            'repuesto.factura.vehiculo.propietario',
            'repuesto.factura.vehiculo.cliente',
            'repuesto.mantenimiento.vehiculo.propietario',
            'repuesto.mantenimiento.vehiculo.cliente',
            'atendidaPor:id_usuario,username',
        ])
        ->whereDate('fecha_vencimiento', '<=', $hoy);

        // Filtro por estado
        if ($request->filled('estado')) {
            if ($request->estado === 'pendientes') {
                $query->whereNull('atendida_at');
            } elseif ($request->estado === 'gestionadas') {
                $query->whereNotNull('atendida_at');
            }
        }

        // Criterio 6: Filtrar por rango de fechas
        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha_vencimiento', '>=', $request->fecha_desde);
        }
        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha_vencimiento', '<=', $request->fecha_hasta);
        }

        // Criterio 6: Filtrar por propietario
        if ($request->filled('propietario')) {
            $termino = strtolower(trim($request->propietario));
            $query->whereHas('repuesto', function ($qRepuesto) use ($termino) {
                $qRepuesto->where(function ($qR) use ($termino) {
                    $qR->whereHas('factura.vehiculo.propietario', function ($qP) use ($termino) {
                        $qP->whereRaw('LOWER(nombre) LIKE ?', ["%{$termino}%"]);
                    })
                    ->orWhereHas('factura.vehiculo.cliente', function ($qC) use ($termino) {
                        $qC->whereRaw('LOWER(nombre) LIKE ?', ["%{$termino}%"]);
                    })
                    ->orWhereHas('mantenimiento.vehiculo.propietario', function ($qP) use ($termino) {
                        $qP->whereRaw('LOWER(nombre) LIKE ?', ["%{$termino}%"]);
                    })
                    ->orWhereHas('mantenimiento.vehiculo.cliente', function ($qC) use ($termino) {
                        $qC->whereRaw('LOWER(nombre) LIKE ?', ["%{$termino}%"]);
                    });
                });
            });
        }

        $garantias = $query->orderBy('fecha_vencimiento', 'asc')->orderByDesc('id')->get();

        return response()->json([
            'garantias' => $garantias,
            'total' => $garantias->count(),
            'pendientes' => $garantias->whereNull('atendida_at')->count(),
            'gestionadas' => $garantias->whereNotNull('atendida_at')->count(),
        ]);
    }

    public function gestionar(Request $request, AlertaGarantia $alerta): JsonResponse
    {
        // Criterio 4 y 5: Marcar como Gestionada y actualizar automáticamente el estado
        AlertaGarantia::whereKey($alerta->id)->whereNull('atendida_at')->update([
            'atendida_at' => now(),
            'id_usuario_atencion' => $request->user()->getKey(),
        ]);

        $alerta->refresh()->load([
            'repuesto.factura.vehiculo.propietario',
            'repuesto.factura.vehiculo.cliente',
            'repuesto.mantenimiento.vehiculo.propietario',
            'repuesto.mantenimiento.vehiculo.cliente',
            'atendidaPor:id_usuario,username',
        ]);

        return response()->json([
            'message' => 'Garantía marcada como Gestionada correctamente.',
            'alerta' => $alerta,
        ]);
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
