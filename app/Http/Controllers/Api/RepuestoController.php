<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Factura;
use App\Models\Mantenimiento;
use App\Models\Notificacion;
use App\Models\Repuesto;
use App\Models\Vehiculo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RepuestoController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'repuestos' => Repuesto::with('factura.vehiculo')->latest('id_repuesto')->get(),
            'vehiculos' => Vehiculo::all(),
            'mantenimientos' => Mantenimiento::where('estado', '!=', 'Cancelado')
                ->whereHas('vehiculo', fn ($query) => $query->whereNull('deleted_at'))
                ->orderByDesc('fecha_servicio')
                ->get(['id_mantenimiento', 'id_vehiculo', 'fecha_servicio', 'descripcion_falla', 'estado']),
        ]);
    }

    public function alertas(): JsonResponse
    {
        return response()->json(Notificacion::orderByDesc('id_notificacion')->get());
    }

    public function storeFromPdf(Request $request): JsonResponse
    {
        $mensajePdf = 'Solo se permiten archivos en formato PDF (.pdf).';
        $data = $request->validate([
            'id_vehiculo' => ['required', Rule::exists('vehiculos', 'id_vehiculo')->whereNull('deleted_at')],
            'id_mantenimiento' => [
                'required',
                Rule::exists('mantenimientos', 'id_mantenimiento')
                    ->where('id_vehiculo', $request->input('id_vehiculo'))
                    ->where(fn ($query) => $query->where('estado', '!=', 'Cancelado')),
            ],
            'numero_factura' => 'required|string|max:50',
            'nombre_pieza' => 'required|string|max:100',
            'codigo_pieza' => 'nullable|string|max:50',
            'comprobante_pdf' => 'bail|required|file|extensions:pdf|mimes:pdf|max:5120',
            'cantidad_recibida' => 'required|integer|min:1',
            'costo_unitario' => 'required|numeric|min:0',
            'garantia_inicio' => 'nullable|required_with:garantia_fin|date_format:Y-m-d',
            'garantia_fin' => 'nullable|required_with:garantia_inicio|date_format:Y-m-d|after_or_equal:garantia_inicio',
        ], [
            'id_mantenimiento.required' => 'Selecciona el mantenimiento al que pertenece el repuesto.',
            'id_mantenimiento.exists' => 'El mantenimiento seleccionado no pertenece al vehículo o está cancelado.',
            'numero_factura.required' => 'Ingresa el número de factura.',
            'numero_factura.max' => 'El número de factura no puede superar 50 caracteres.',
            'nombre_pieza.required' => 'Ingresa el nombre de la pieza.',
            'nombre_pieza.max' => 'El nombre de la pieza no puede superar 100 caracteres.',
            'codigo_pieza.max' => 'El código de pieza no puede superar 50 caracteres.',
            'comprobante_pdf.required' => 'Adjunta la factura en formato PDF.',
            'comprobante_pdf.uploaded' => 'No se pudo subir el archivo. Verifica que no supere 5 MB.',
            'comprobante_pdf.file' => $mensajePdf,
            'comprobante_pdf.extensions' => $mensajePdf,
            'comprobante_pdf.mimes' => $mensajePdf,
            'comprobante_pdf.max' => 'La factura PDF no puede superar 5 MB.',
        ]);

        $path = null;

        try {
            return DB::transaction(function () use ($request, $data, &$path): JsonResponse {
                Vehiculo::lockForUpdate()->findOrFail($data['id_vehiculo']);

                $path = $request->file('comprobante_pdf')->store('facturas', 'public');

                $factura = Factura::create([
                    'numero_factura' => trim($data['numero_factura']),
                    'fecha_emision' => now()->format('Y-m-d'),
                    'monto_total' => $data['costo_unitario'] * $data['cantidad_recibida'],
                    'ruta_pdf_almacenamiento' => $path,
                    'id_vehiculo' => $data['id_vehiculo'],
                ]);

                $repuesto = Repuesto::create([
                    'nombre_pieza' => trim($data['nombre_pieza']),
                    'codigo_pieza' => filled($data['codigo_pieza'] ?? null) ? trim($data['codigo_pieza']) : null,
                    'costo_unitario' => $data['costo_unitario'],
                    'id_mantenimiento' => $data['id_mantenimiento'],
                    'id_factura' => $factura->id_factura,
                    'garantia_inicio' => $data['garantia_inicio'] ?? null,
                    'garantia_fin' => $data['garantia_fin'] ?? null,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Factura cargada correctamente y vinculada al repuesto.',
                    'data' => $repuesto->load(['factura.vehiculo', 'mantenimiento']),
                    'ruta_pdf' => Storage::disk('public')->url($path),
                ], 201);
            });
        } catch (\Throwable $exception) {
            if ($path) {
                Storage::disk('public')->delete($path);
            }

            return response()->json([
                'success' => false,
                'message' => 'No se pudo procesar la factura PDF.',
                'error' => $exception->getMessage(),
            ], 422);
        }
    }

    public function enviarNotificacion(Request $request): JsonResponse
    {
        $data = $request->validate([
            'destinatario' => 'required|email|max:100',
            'asunto' => 'required|string|max:150',
            'mensaje' => 'required|string',
        ]);

        $notificacion = Notificacion::create($data + [
            'tipo_envio' => 'AUTOMÁTICO',
            'fecha_envio' => now(),
        ]);

        return response()->json($notificacion, 201);
    }
}
