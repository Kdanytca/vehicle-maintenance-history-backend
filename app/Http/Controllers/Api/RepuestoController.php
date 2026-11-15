<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Factura;
use App\Models\Mantenimiento;
use App\Models\Notificacion;
use App\Models\Repuesto;
use App\Models\Vehiculo;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RepuestoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Repuesto::with(['factura.vehiculo', 'mantenimiento.vehiculo'])->latest('id_repuesto');

        $filtroRaw = (string) $request->input('garantia', $request->input('estado_garantia', $request->input('estado', '')));
        $filtro = strtolower(trim($filtroRaw));
        $filtro = str_replace(['á', 'é', 'í', 'ó', 'ú'], ['a', 'e', 'i', 'o', 'u'], $filtro);

        $filtroAplicado = 'todos';
        if (str_contains($filtro, 'vigente')) {
            $query->garantiaVigente();
            $filtroAplicado = 'vigente';
        } elseif (str_contains($filtro, 'vencid')) {
            $query->garantiaVencida();
            $filtroAplicado = 'vencida';
        }

        $repuestos = $query->get();

        return response()->json([
            'repuestos' => $repuestos,
            'filtro_garantia' => $filtroAplicado,
            'total' => $repuestos->count(),
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

    public function masUtilizados(): JsonResponse
    {
        $topRepuestos = Repuesto::topMasUtilizados(5);

        return response()->json([
            'success' => true,
            'total' => $topRepuestos->count(),
            'repuestos' => $topRepuestos,
        ]);
    }

    public function storeFromPdf(Request $request): JsonResponse
    {
        $mensajePdf = 'Solo se permiten archivos en formato PDF (.pdf).';
        $data = $request->validate([
            'id_vehiculo' => ['required', Rule::exists('vehiculos', 'id_vehiculo')->whereNull('deleted_at')],
            'id_mantenimiento' => [
                'nullable',
                Rule::exists('mantenimientos', 'id_mantenimiento')
                    ->where('id_vehiculo', $request->input('id_vehiculo'))
                    ->where(fn ($query) => $query->where('estado', '!=', 'Cancelado')),
            ],
            'numero_factura' => 'nullable|string|max:50',
            'nombre_pieza' => 'nullable|string|max:100',
            'codigo_pieza' => 'nullable|string|max:50',
            'proveedor' => 'nullable|string|max:100',
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
        $guardado = false;
        $numeroFactura = null;

        try {
            $response = DB::transaction(function () use ($request, $data, &$path, &$numeroFactura): JsonResponse {
                Vehiculo::lockForUpdate()->findOrFail($data['id_vehiculo']);

                $file = $request->file('comprobante_pdf');
                $raw = file_get_contents($file->getRealPath());
                preg_match_all('/\((.*?)\)/', $raw, $matches);
                $text = trim(implode(' ', $matches[1])) ?: 'FACTURA COMPROBANTE '.$file->getClientOriginalName();
                preg_match('/FAC-\d+/', $text, $facturaMatch);
                $numeroFactura = mb_strtoupper(trim($data['numero_factura'] ?? $facturaMatch[0] ?? ''));
                if ($numeroFactura === '') {
                    return response()->json([
                        'success' => false,
                        'message' => 'No se pudo identificar el número de factura en el PDF. Ingrésalo para continuar.',
                        'errors' => ['numero_factura' => ['El número de factura es obligatorio.']],
                    ], 422);
                }
                if (Factura::where('numero_factura', $numeroFactura)->exists()) {
                    return $this->facturaDuplicada($numeroFactura);
                }
                preg_match('/REP-\d+-[A-Z]/', $text, $codigoMatch);
                preg_match('/(?:codigoPieza|Código Pieza|Codigo Pieza)\s*[:\-]?\s*([^\t\n]+)/i', $text, $piezaCodigoMatch);
                preg_match('/Filtro de Aceite [^\n]+|Pastillas de Freno [^\n]+|Cambio de Aceite [^\n]+/i', $text, $piezaMatch);
                $path = $file->store('facturas', 'public');

                $factura = Factura::create([
                    'numero_factura' => $numeroFactura,
                    'fecha_emision' => now()->format('Y-m-d'),
                    'monto_total' => $data['costo_unitario'] * $data['cantidad_recibida'],
                    'ruta_pdf_almacenamiento' => $path,
                    'id_vehiculo' => $data['id_vehiculo'],
                ]);

                $repuesto = Repuesto::create([
                    'nombre_pieza' => trim($data['nombre_pieza'] ?? $piezaMatch[0] ?? $piezaCodigoMatch[1] ?? 'Pieza Automotriz No Identificada'),
                    'codigo_pieza' => filled($data['codigo_pieza'] ?? null) ? trim($data['codigo_pieza']) : ($codigoMatch[0] ?? 'REP-GENERICO'),
                    'proveedor' => filled($data['proveedor'] ?? null) ? trim($data['proveedor']) : null,
                    'costo_unitario' => $data['costo_unitario'],
                    'id_mantenimiento' => $data['id_mantenimiento'] ?? null,
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
            $guardado = $response->getStatusCode() === 201;

            return $response;
        } catch (QueryException $exception) {
            $sqlState = (string) ($exception->errorInfo[0] ?? $exception->getCode());
            if (in_array($sqlState, ['23000', '23505'], true)
                && $numeroFactura !== null
                && Factura::where('numero_factura', $numeroFactura)->exists()) {
                return $this->facturaDuplicada($numeroFactura);
            }
            throw $exception;
        } finally {
            if (! $guardado && $path !== null) Storage::disk('public')->delete($path);
        }
    }

    private function facturaDuplicada(string $numeroFactura): JsonResponse
    {
        $message = "El número de factura {$numeroFactura} ya fue registrado previamente.";

        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => ['numero_factura' => [$message]],
        ], 422);
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
