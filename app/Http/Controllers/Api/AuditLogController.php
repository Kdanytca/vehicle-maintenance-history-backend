<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;

class AuditLogController extends Controller
{
    /**
     * Obtiene el historial de auditoría ordenado del más reciente al más antiguo.
     * Solo pueden acceder administradores.
     */
    public function index(): JsonResponse
    {
        $usuarioActual = auth()->user();

        if (!$usuarioActual || !$usuarioActual->rol || $usuarioActual->rol->nombre_rol !== 'admin') {
            abort(403, 'No tienes permiso para consultar el historial del sistema.');
        }

        $auditLogs = AuditLog::with('usuario:id_usuario,username')
            ->orderByDesc('created_at')
            ->get();

        return response()->json($auditLogs);
    }
}
