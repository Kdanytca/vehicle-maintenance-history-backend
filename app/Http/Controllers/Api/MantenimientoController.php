<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Mantenimiento;
use App\Models\Usuario;
use App\Models\Vehiculo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MantenimientoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'fecha_inicio' => ['nullable', 'date_format:Y-m-d'],
            'fecha_fin' => ['nullable', 'date_format:Y-m-d'],
        ]);

        if (
            $request->filled('fecha_inicio') &&
            $request->filled('fecha_fin') &&
            $request->date('fecha_inicio')->greaterThan($request->date('fecha_fin'))
        ) {
            throw ValidationException::withMessages([
                'fecha_fin' => ['La fecha final debe ser mayor o igual que la fecha de inicio.'],
            ]);
        }

        $query = Mantenimiento::with([
            'vehiculo',
            'encargado.rol',
        ])
            ->orderByDesc('id_mantenimiento');

        if ($request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
            $query->whereBetween('fecha_servicio', [
                $request->input('fecha_inicio'),
                $request->input('fecha_fin'),
            ]);
        } elseif ($request->filled('fecha_inicio')) {
            $query->whereDate('fecha_servicio', '>=', $request->input('fecha_inicio'));
        } elseif ($request->filled('fecha_fin')) {
            $query->whereDate('fecha_servicio', '<=', $request->input('fecha_fin'));
        }

        return response()->json([
            'mantenimientos' => $query->get(),
            'vehiculos' => Vehiculo::all(),
            'usuarios' => Usuario::with('rol')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $mantenimiento = DB::transaction(function () use ($request) {
            $datos = $this->validated($request);
            $this->requireActiveVehicle((int) $datos['id_vehiculo']);

            return Mantenimiento::create($datos);
        });

        AuditLog::registrar(
            'crear',
            'mantenimientos',
            'Se creó el mantenimiento #'.$mantenimiento->id_mantenimiento.' para el vehículo '.$mantenimiento->id_vehiculo
        );

        return response()->json(
            $mantenimiento->load(['vehiculo', 'encargado.rol']),
            201
        );
    }

    public function update(
        Request $request,
        int $mantenimiento
    ): JsonResponse {
        $registro = DB::transaction(function () use ($request, $mantenimiento) {
            $registro = Mantenimiento::findOrFail($mantenimiento);
            $datos = $this->validated($request, $registro->id_mantenimiento);
            $ids = array_unique([(int) $registro->id_vehiculo, (int) $datos['id_vehiculo']]);
            sort($ids);
            foreach ($ids as $id) {
                $this->requireActiveVehicle($id);
            }
            $registro = $this->reloadUnchangedJob($registro);
            $registro->update($datos);

            return $registro;
        });

        AuditLog::registrar(
            'editar',
            'mantenimientos',
            'Se actualizó el mantenimiento #'.$registro->id_mantenimiento.' con Estado: '.$registro->estado
        );

        return response()->json(
            $registro->load(['vehiculo', 'encargado.rol'])
        );
    }

    public function destroy(int $mantenimiento): JsonResponse
    {
        $registro = DB::transaction(function () use ($mantenimiento) {
            $registro = Mantenimiento::findOrFail($mantenimiento);
            $this->requireActiveVehicle($registro->id_vehiculo);
            $registro = $this->reloadUnchangedJob($registro);
            $registro->update(['estado' => 'Cancelado']);

            return $registro;
        });

        AuditLog::registrar(
            'eliminar',
            'mantenimientos',
            'Se canceló el mantenimiento #'.$registro->id_mantenimiento.' y quedó en estado Cancelado.'
        );

        return response()->json($registro);
    }

    private function validated(
        Request $request,
        ?int $excluirMantenimiento = null
    ): array {
        $datos = $request->validate([
            'fecha_servicio' => 'required|date',
            'tipo_servicio' => 'nullable|string|max:100',
            'descripcion_falla' => 'required|string',
            'estado' => 'required|string',
            'costo_mano_obra' => 'nullable|numeric',
            'id_vehiculo' => ['required', Rule::exists('vehiculos', 'id_vehiculo')->whereNull('deleted_at')],
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

    private function requireActiveVehicle(int $id): void
    {
        // El mismo bloqueo que usa el archivo evita crear/reactivar trabajos durante la baja.
        $vehiculo = Vehiculo::withTrashed()->lockForUpdate()->find($id);
        if (! $vehiculo || $vehiculo->trashed()) {
            throw ValidationException::withMessages([
                'id_vehiculo' => 'El vehículo está archivado; sus mantenimientos se conservan solo para consulta.',
            ]);
        }
    }

    private function reloadUnchangedJob(Mantenimiento $registro): Mantenimiento
    {
        // Bloquear siempre vehículo antes que mantenimiento, igual que al archivar.
        $actual = Mantenimiento::lockForUpdate()->findOrFail($registro->id_mantenimiento);
        if ($actual->id_vehiculo !== $registro->id_vehiculo) {
            throw ValidationException::withMessages([
                'id_vehiculo' => 'El mantenimiento cambió de vehículo. Recarga los datos antes de continuar.',
            ]);
        }

        return $actual;
    }
}
