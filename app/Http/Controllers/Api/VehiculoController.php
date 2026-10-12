<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Factura;
use App\Models\Mantenimiento;
use App\Models\Propietario;
use App\Models\Repuesto;
use App\Models\Vehiculo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VehiculoController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Vehiculo::with('propietario')->latest('id_vehiculo')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'placa' => 'required|unique:vehiculos,placa',
            'marca' => 'required|string|max:100',
            'modelo' => 'required|string|max:100',
            'anio' => 'required|integer|min:1900|max:' . (now()->year + 1),
            'propietario' => 'required|string|max:150',
        ]);

        $propietario = Propietario::create([
            'nombre' => $data['propietario'],
            'documento_identidad' => null,
            'telefono' => null,
            'correo' => null,
        ]);

        $vehiculo = Vehiculo::create([
            'placa' => $data['placa'],
            'marca' => $data['marca'],
            'modelo' => $data['modelo'],
            'anio' => $data['anio'],
            'id_propietario' => $propietario->id_propietario,
        ]);

        return response()->json($vehiculo->load('propietario'), 201);
    }

    public function show(int $vehiculo): JsonResponse
    {
        $vehiculo = Vehiculo::with('propietario')->findOrFail($vehiculo);
        $mantenimientos = Mantenimiento::where('id_vehiculo', $vehiculo->id_vehiculo)->orderByDesc('fecha_servicio')->get();

        return response()->json(compact('vehiculo', 'mantenimientos'));
    }

    public function update(Request $request, int $vehiculo): JsonResponse
    {
        $vehiculo = Vehiculo::with('propietario')->findOrFail($vehiculo);

        $data = $request->validate([
            'placa' => 'required|unique:vehiculos,placa,' . $vehiculo->id_vehiculo . ',id_vehiculo',
            'marca' => 'required|string|max:100',
            'modelo' => 'required|string|max:100',
            'anio' => 'required|integer|min:1900|max:' . (now()->year + 1),
            'propietario' => 'required|string|max:150',
        ]);

        $vehiculo->update($request->only('placa', 'marca', 'modelo', 'anio'));
        $vehiculo->propietario?->update(['nombre' => $data['propietario']]);

        return response()->json($vehiculo->refresh()->load('propietario'));
    }

    public function destroy(Request $request, int $vehiculo): JsonResponse
    {
        $vehiculo = Vehiculo::findOrFail($vehiculo);
        $vehiculoId = $vehiculo->id_vehiculo;
        $impacto = $this->deleteImpact($vehiculo);

        $debeConfirmar = $impacto['mantenimientos_pendientes'] > 0
            || $impacto['mantenimientos_total'] > 0
            || $impacto['facturas_total'] > 0
            || $impacto['repuestos_total'] > 0;

        if (! $debeConfirmar && $request->boolean('solo_validar')) {
            return response()->json([
                'message' => 'El vehículo no tiene registros relacionados.',
                'requires_confirmation' => false,
                'impacto' => $impacto,
            ]);
        }

        if ($debeConfirmar && ! $request->boolean('confirmar_eliminacion')) {
            return response()->json([
                'message' => 'Confirma la eliminación del vehículo y sus registros relacionados.',
                'requires_confirmation' => true,
                'impacto' => $impacto,
            ], 409);
        }

        if ($impacto['mantenimientos_pendientes'] > 0 && ! $request->boolean('cancelar_pendientes')) {
            return response()->json([
                'message' => 'El vehículo tiene mantenimientos pendientes. Confirma si deseas cancelarlos antes de eliminar.',
                'requires_pending_cancellation' => true,
                'impacto' => $impacto,
            ], 409);
        }

        $eliminados = DB::transaction(function () use ($vehiculoId, $impacto): array {
            $mantenimientoIds = Mantenimiento::where('id_vehiculo', $vehiculoId)->pluck('id_mantenimiento');
            $facturaIds = Factura::where('id_vehiculo', $vehiculoId)->pluck('id_factura');

            if ($impacto['mantenimientos_pendientes'] > 0) {
                Mantenimiento::where('id_vehiculo', $vehiculoId)
                    ->whereIn('estado', ['Pendiente', 'En Proceso'])
                    ->update(['estado' => 'Cancelado']);
            }

            $repuestosEliminados = Repuesto::whereIn('id_factura', $facturaIds)
                ->orWhereIn('id_mantenimiento', $mantenimientoIds)
                ->delete();

            $facturasEliminadas = Factura::where('id_vehiculo', $vehiculoId)->delete();
            $mantenimientosEliminados = Mantenimiento::where('id_vehiculo', $vehiculoId)->delete();
            $vehiculosEliminados = Vehiculo::whereKey($vehiculoId)->delete();

            return [
                'repuestos' => $repuestosEliminados,
                'facturas' => $facturasEliminadas,
                'mantenimientos' => $mantenimientosEliminados,
                'vehiculos' => $vehiculosEliminados,
            ];
        });

        $existeDespues = Vehiculo::whereKey($vehiculoId)->exists();

        if ($existeDespues || $eliminados['vehiculos'] !== 1) {
            return response()->json([
                'message' => 'No se pudo confirmar la eliminación del vehículo.',
                'deleted_id' => $vehiculoId,
                'exists_after_delete' => $existeDespues,
                'deleted_counts' => $eliminados,
                'impacto' => $impacto,
            ], 500);
        }

        return response()->json([
            'message' => 'Vehículo eliminado correctamente.',
            'deleted_id' => $vehiculoId,
            'exists_after_delete' => false,
            'deleted_counts' => $eliminados,
            'impacto' => $impacto,
        ]);
    }

    public function buscar(Request $request): JsonResponse
    {
        $data = $request->validate(['termino' => 'required|string']);

        $termino = strtolower($data['termino']);

        $vehiculos = Vehiculo::with('propietario')
            ->whereRaw('LOWER(placa) LIKE ?', ['%' . $termino . '%'])
            ->orWhereHas('propietario', fn ($query) => $query->whereRaw('LOWER(nombre) LIKE ?', ['%' . $termino . '%']))
            ->get();

        return response()->json($vehiculos);
    }

    public function reportePorPlaca(Request $request): JsonResponse
    {
        $data = $request->validate(['placa' => 'required|string']);
        $vehiculo = Vehiculo::with('propietario')->where('placa', trim($data['placa']))->first();

        if (! $vehiculo) {
            return response()->json(['message' => 'La placa ingresada no existe.'], 404);
        }

        $mantenimientos = Mantenimiento::with('encargado')->where('id_vehiculo', $vehiculo->id_vehiculo)->orderBy('fecha_servicio')->get();

        if ($mantenimientos->isEmpty()) {
            return response()->json(['message' => 'No existen mantenimientos para esta placa.', 'vehiculo' => $vehiculo], 404);
        }

        return response()->json(compact('vehiculo', 'mantenimientos'));
    }

    private function deleteImpact(Vehiculo $vehiculo): array
    {
        $mantenimientos = Mantenimiento::where('id_vehiculo', $vehiculo->id_vehiculo);
        $mantenimientoIds = (clone $mantenimientos)->pluck('id_mantenimiento');
        $facturaIds = Factura::where('id_vehiculo', $vehiculo->id_vehiculo)->pluck('id_factura');

        return [
            'mantenimientos_pendientes' => (clone $mantenimientos)->whereIn('estado', ['Pendiente', 'En Proceso'])->count(),
            'mantenimientos_total' => (clone $mantenimientos)->count(),
            'facturas_total' => $facturaIds->count(),
            'repuestos_total' => Repuesto::whereIn('id_factura', $facturaIds)
                ->orWhereIn('id_mantenimiento', $mantenimientoIds)
                ->count(),
        ];
    }
}
