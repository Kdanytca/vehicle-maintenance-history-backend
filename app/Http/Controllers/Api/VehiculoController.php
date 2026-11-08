<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Factura;
use App\Models\HistorialCambio;
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
        return response()->json(
            Vehiculo::with('propietario')->withCount('recepciones')->latest('id_vehiculo')->get()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'placa' => [
                'required',
                'string',
                'max:10',
                'regex:/^(P|A|C|M|D|T|RE|AB|MI|CC|CD|N|V)[0-9]{3}-[0-9]{3}$/i',
                'unique:vehiculos,placa',
            ],
            'marca' => 'required|string|max:100',
            'modelo' => 'required|string|max:6',
            'anio' => 'required|integer|min:1900|max:' . (now()->year + 1),
            'propietario' => 'required|string|max:150',
        ] + RecepcionController::reglas(false)); // G2DS-16: campos de recepción

        // Si algo falla, no se guarda ni el vehículo ni la recepción.
        return DB::transaction(function () use ($request, $data): JsonResponse {
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

            AuditLog::registrar(
                'crear',
                'vehiculos',
                'Se creó el vehículo '.$vehiculo->placa.' para el propietario '.$propietario->nombre
            );

            // G2DS-16: si se enviaron los datos de recepción, se guardan junto con el vehículo.
            if ($request->filled('kilometraje')) {
                RecepcionController::registrar($vehiculo, $data, $request->user()?->id_usuario);
            }

            return response()->json($vehiculo->load('propietario')->loadCount('recepciones'), 201);
        });
    }

    public function show(int $vehiculo): JsonResponse
    {
        $vehiculo = Vehiculo::withTrashed()->with('propietario')->findOrFail($vehiculo);
        $mantenimientos = Mantenimiento::where('id_vehiculo', $vehiculo->id_vehiculo)->orderByDesc('fecha_servicio')->get();

        return response()->json(compact('vehiculo', 'mantenimientos'));
    }

    public function update(Request $request, int $vehiculo): JsonResponse
    {
        return DB::transaction(function () use ($request, $vehiculo): JsonResponse {
            $vehiculo = Vehiculo::with('propietario')->lockForUpdate()->findOrFail($vehiculo);

            $data = $request->validate([
                'placa' => [
                    'required',
                    'string',
                    'max:10',
                    'regex:/^(P|A|C|M|D|T|RE|AB|MI|CC|CD|N|V)[0-9]{3}-[0-9]{3}$/i',
                    'unique:vehiculos,placa,' . $vehiculo->id_vehiculo . ',id_vehiculo',
                ],
                'marca' => 'required|string|max:100',
                'modelo' => 'required|string|max:6',
                'anio' => 'required|integer|min:1900|max:' . (now()->year + 1),
                'propietario' => 'required|string|max:150',
            ]);

            $vehiculo->update($request->only('placa', 'marca', 'modelo', 'anio'));
            $vehiculo->propietario?->update(['nombre' => $data['propietario']]);

            AuditLog::registrar(
                'editar',
                'vehiculos',
                'Se actualizó el vehículo '.$vehiculo->placa.' (marca: '.$vehiculo->marca.', modelo: '.$vehiculo->modelo.')'
            );

            return response()->json($vehiculo->refresh()->load('propietario'));
        });
    }

    public function destroy(Request $request, int $vehiculo): JsonResponse
    {
        $vehiculo = Vehiculo::findOrFail($vehiculo);
        $impacto = $this->deleteImpact($vehiculo);
        if ($request->boolean('solo_validar')) {
            return response()->json([
                'message' => 'El archivo conservará los registros y costos; los trabajos pendientes o en proceso se cancelarán.',
                'requires_confirmation' => true,
                'impacto' => $impacto,
            ]);
        }

        if (! $request->boolean('confirmar_eliminacion')) {
            return response()->json([
                'message' => 'Confirma el archivo del vehículo y la cancelación de sus trabajos activos. Se conservarán los registros y costos.',
                'requires_confirmation' => true,
                'impacto' => $impacto,
            ], 409);
        }

        return DB::transaction(function () use ($request, $vehiculo): JsonResponse {
            $vehiculo = Vehiculo::lockForUpdate()->findOrFail($vehiculo->id_vehiculo);
            $impacto = $this->deleteImpact($vehiculo);
            $cancelados = Mantenimiento::where('id_vehiculo', $vehiculo->id_vehiculo)
                ->whereIn('estado', ['Pendiente', 'En Proceso'])
                ->update(['estado' => 'Cancelado']);
            $vehiculo->delete();

            HistorialCambio::create([
                'tipo_evento' => 'VEHICULO_ARCHIVADO',
                'descripcion_evento' => "Vehículo {$vehiculo->placa} (ID {$vehiculo->id_vehiculo}) archivado. Trabajos cancelados: {$cancelados}. Registros y costos conservados.",
                'direccion_ip' => $request->ip(),
                'id_usuario' => $request->user()->id_usuario,
            ]);

            return response()->json([
                'message' => 'Vehículo archivado correctamente. Se conservaron todos los registros y costos.',
                'archived_id' => $vehiculo->id_vehiculo,
                'deleted_at' => $vehiculo->deleted_at,
                'mantenimientos_cancelados' => $cancelados,
                'impacto' => $impacto,
            ]);
        });
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

    public function reportePorPlaca(Request $request, \App\Services\ReporteVehiculo $reporte): JsonResponse
    {
        $data = $request->validate(['placa' => 'required|string']);

        return response()->json($reporte->porPlaca($data['placa']));
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