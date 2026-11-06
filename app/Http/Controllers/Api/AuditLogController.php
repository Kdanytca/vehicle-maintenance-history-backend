<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Carbon\CarbonImmutable;
use DateTimeZone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    /**
     * Obtiene el historial de auditoría ordenado del más reciente al más antiguo.
     * Solo pueden acceder administradores.
     */
    public function index(Request $request): JsonResponse
    {
        $usuarioActual = auth()->user();

        if (! $usuarioActual || ! $usuarioActual->rol || $usuarioActual->rol->nombre_rol !== 'admin') {
            abort(403, 'No tienes permiso para consultar el historial del sistema.');
        }

        $filters = $request->validate([
            'usuario' => ['sometimes', 'nullable', 'string', 'max:100'],
            'fecha' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'zona_horaria' => ['sometimes', 'required_with:fecha', 'timezone'],
            'rol' => ['sometimes', 'nullable', 'string', 'max:100'],
        ]);

        $query = AuditLog::with('usuario:id_usuario,username,id_rol', 'usuario.rol:id_rol,nombre_rol')
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if (! empty($filters['usuario'])) {
            $usuario = $filters['usuario'];
            $query->where(function ($query) use ($usuario) {
                $query->where('usuario_nombre', 'like', "%{$usuario}%")
                    ->orWhereHas('usuario', fn ($query) => $query->where('username', 'like', "%{$usuario}%"));
            });
        }

        if (! empty($filters['fecha'])) {
            $timezone = new DateTimeZone($filters['zona_horaria'] ?? 'UTC');
            $inicioLocal = CarbonImmutable::createFromFormat('!Y-m-d', $filters['fecha'], $timezone);
            $finLocal = $inicioLocal->addDay();

            $query->where('created_at', '>=', $inicioLocal->utc())
                ->where('created_at', '<', $finLocal->utc());
        }

        if (! empty($filters['rol'])) {
            $rol = $filters['rol'];
            $query->where(function ($query) use ($rol) {
                $query->where('rol_nombre', $rol)
                    ->orWhereHas('usuario.rol', fn ($query) => $query->where('nombre_rol', $rol));
            });
        }

        return response()->json($query->get());
    }
}
