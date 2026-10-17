<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        // El proyecto identifica el rol administrador con el valor "admin".
        abort_unless(
            $request->user()?->rol?->nombre_rol === 'admin',
            403,
            'No tienes permiso para consultar el historial de auditoría.'
        );

        // Incluye al usuario y ordena por fecha y clave de forma descendente.
        return response()->json(
            AuditLog::with('user')
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->get()
        );
    }
}
