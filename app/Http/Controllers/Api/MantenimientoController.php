<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Mantenimiento;
use App\Models\Usuario;
use App\Models\Vehiculo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MantenimientoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        // Valida las fechas opcionales con el formato YYYY-MM-DD.
        $fechas = $request->validate([
            'fecha_inicio' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'fecha_fin' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
        ]);

        // Comprueba el orden del rango cuando se proporcionan ambas fechas.
        if (
            ! empty($fechas['fecha_inicio']) &&
            ! empty($fechas['fecha_fin']) &&
            $fechas['fecha_inicio'] > $fechas['fecha_fin']
        ) {
            throw ValidationException::withMessages([
                'fecha_inicio' => 'La fecha de inicio debe ser anterior o igual a la fecha de fin.',
            ]);
        }

        // Mantiene las relaciones existentes y filtra solo si ambas fechas están presentes.
        $consulta = Mantenimiento::with(['vehiculo', 'encargado.rol']);

        if (! empty($fechas['fecha_inicio']) && ! empty($fechas['fecha_fin'])) {
            $consulta->whereBetween('fecha_servicio', [
                $fechas['fecha_inicio'],
                $fechas['fecha_fin'],
            ]);
        }

        // Ordena por fecha descendente y conserva la respuesta JSON actual del endpoint.
        return response()->json([
            'mantenimientos' => $consulta
                ->orderByDesc('fecha_servicio')
                ->orderByDesc('id_mantenimiento')
                ->get(),
            'vehiculos' => Vehiculo::all(),
            'usuarios' => Usuario::with('rol')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $datos = $this->validated($request);

        $mantenimiento = Mantenimiento::create($datos);

        return response()->json(
            $mantenimiento->load(['vehiculo', 'encargado.rol']),
            201
        );
    }

    public function update(Request $request, int $mantenimiento): JsonResponse
    {
        $registro = Mantenimiento::findOrFail($mantenimiento);

        $datos = $this->validated(
            $request,
            $registro->id_mantenimiento
        );

        $registro->update($datos);

        return response()->json(
            $registro->load(['vehiculo', 'encargado.rol'])
        );
    }

    public function destroy(int $mantenimiento): JsonResponse
    {
        $registro = Mantenimiento::findOrFail($mantenimiento);

        $registro->update([
            'estado' => 'Cancelado',
        ]);

        return response()->json($registro);
    }

    private function validated(
        Request $request,
        ?int $excluirMantenimiento = null
    ): array {
        $datos = $request->validate([
            'fecha_servicio' => 'required|date',
            'descripcion_falla' => 'required|string',
            'estado' => 'required|string',
            'costo_mano_obra' => 'nullable|numeric',
            'id_vehiculo' => 'required|exists:vehiculos,id_vehiculo',
            'id_usuario_encargado' => 'required|exists:usuarios,id_usuario',
        ]);

        // Pendiente y En Proceso ocupan una plaza.
        // Completado y Cancelado liberan la plaza.
        $estadosActivos = ['Pendiente', 'En Proceso'];

        if (in_array($datos['estado'], $estadosActivos, true)) {
            $ocupados = Mantenimiento::query()
                ->where(
                    'id_usuario_encargado',
                    $datos['id_usuario_encargado']
                )
                ->whereIn('estado', $estadosActivos)
                ->when(
                    $excluirMantenimiento !== null,
                    function ($query) use ($excluirMantenimiento) {
                        $query->where(
                            'id_mantenimiento',
                            '!=',
                            $excluirMantenimiento
                        );
                    }
                )
                ->count();

            if ($ocupados >= 4) {
                throw ValidationException::withMessages([
                    'id_usuario_encargado' =>
                        'Este mecánico ya tiene cuatro vehículos asignados. '
                        . 'Seleccione otro mecánico o espere a que libere una plaza.',
                ]);
            }
        }

        return $datos;
    }
}
