<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Factura;
use App\Models\Notificacion;
use App\Models\Repuesto;
use App\Models\Vehiculo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RepuestoController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'repuestos' => Repuesto::with('factura.vehiculo')->latest('id_repuesto')->get(),
            'vehiculos' => Vehiculo::all(),
        ]);
    }

    public function alertas(): JsonResponse
    {
        return response()->json(Notificacion::orderByDesc('id_notificacion')->get());
    }

    public function storeFromPdf(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'id_vehiculo' => 'required|exists:vehiculos,id_vehiculo',
                'comprobante_pdf' => 'required|mimes:pdf|max:5120',
                'cantidad_recibida' => 'required|integer|min:1',
                'costo_unitario' => 'required|numeric|min:0',
            ]);

            $file = $request->file('comprobante_pdf');
            $path = $file->store('facturas', 'public');
            $raw = file_get_contents(Storage::disk('public')->path($path));

            preg_match_all('/\((.*?)\)/', $raw, $matches);
            $text = trim(implode(' ', $matches[1])) ?: 'FACTURA COMPROBANTE ' . $file->getClientOriginalName();

            preg_match('/FAC-\d+/', $text, $facturaMatch);
            preg_match('/REP-\d+-[A-Z]/', $text, $codigoMatch);
            preg_match('/(?:codigoPieza|Código Pieza|Codigo Pieza)\s*[:\-]?\s*([^\t\n]+)/i', $text, $piezaCodigoMatch);
            preg_match('/Filtro de Aceite [^\n]+|Pastillas de Freno [^\n]+|Cambio de Aceite [^\n]+/i', $text, $piezaMatch);

            $factura = Factura::create([
                'numero_factura' => $facturaMatch[0] ?? 'FAC-INDETERMINADO',
                'fecha_emision' => now()->format('Y-m-d'),
                'monto_total' => $data['costo_unitario'] * $data['cantidad_recibida'],
                'ruta_pdf_almacenamiento' => $path,
                'id_vehiculo' => $data['id_vehiculo'],
            ]);

            $repuesto = Repuesto::create([
                'nombre_pieza' => trim($piezaMatch[0] ?? $piezaCodigoMatch[1] ?? 'Pieza Automotriz No Identificada'),
                'codigo_pieza' => $codigoMatch[0] ?? 'REP-GENERICO',
                'costo_unitario' => $data['costo_unitario'],
                'id_factura' => $factura->id_factura,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Factura procesada y repuesto vinculado correctamente.',
                'data' => $repuesto->load('factura.vehiculo'),
                'ruta_pdf' => Storage::disk('public')->url($path),
            ], 201);
        } catch (\Throwable $exception) {
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
