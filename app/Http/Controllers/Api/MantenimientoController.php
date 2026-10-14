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
    public function index(): JsonResponse
    {
        return response()->json([
            'mantenimientos' => Mantenimiento::with(['vehiculo', 'encargado'])
                ->latest('id_mantenimiento')
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
            $mantenimiento->load(['vehiculo', 'encargado']),
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
            $registro->load(['vehiculo', 'encargado'])
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
