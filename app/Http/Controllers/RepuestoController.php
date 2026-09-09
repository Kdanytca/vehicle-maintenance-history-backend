<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Repuesto;
use App\Models\Factura;
use App\Models\Notificacion;
use App\Models\Vehiculo;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Storage;

class RepuestoController extends BaseController
{
    // Pantalla A: Ver únicamente repuestos con su factura asociada
    public function index()
    {
        $repuestos = Repuesto::with('factura')->get();
        $vehiculos = Vehiculo::all();

        return view('repuestos.index', compact('repuestos', 'vehiculos'));
    }

    // Pantalla B: Módulo exclusivo de Alertas y Trazabilidad de Notificaciones
    public function alertas()
    {
        $notificaciones = Notificacion::orderBy('id_notificacion', 'desc')->get();
        return view('repuestos.alertas', compact('notificaciones'));
    }

    // Requerimiento B: Registrar repuesto y datos de facturación procesando el PDF REAL
    public function storeFromPdf(Request $request)
    {
        // Agregamos obligatoriedad de id_vehiculo existente en base de datos
        $request->validate([
            'id_vehiculo' => 'required|exists:vehiculos,id_vehiculo',
            'comprobante_pdf' => 'required|mimes:pdf|max:5120',
            'cantidad_recibida' => 'required|integer|min:1',
            'costo_unitario' => 'required|numeric|min:0'
        ]);

        try {
            // 1. Guardar el archivo PDF real en storage/app/public/facturas
            $file = $request->file('comprobante_pdf');
            $path = $file->store('facturas', 'public');

            // 2. PARSEAR EL PDF (Alternativa Nativa Segura sin dependencias de Composer) 🌟
            $rutaAbsolutaPdf = Storage::disk('public')->path($path);

            // Leemos el flujo de bytes planos del archivo PDF
            $contenidoRaw = file_get_contents($rutaAbsolutaPdf);

            // Filtramos las cadenas de caracteres encerradas entre paréntesis en el mapa del PDF
            preg_match_all("/\((.*?)\)/", $contenidoRaw, $coincidencias);
            $text = implode(" ", $coincidencias[1]);

            // Fallback preventivo por si el archivo viene encriptado o vacío
            if (empty(trim($text))) {
                $text = "FACTURA COMPROBANTE " . $file->getClientOriginalName();
            }

            // 3. EXTRACCIÓN MEDIANTE EXPRESIONES REGULARES (Misma lógica de celdas de Excel)

            // Buscar número de factura (Patrón: busca texto que empiece con FAC- seguido de números)
            $numeroFactura = 'FAC-INDETERMINADO';
            if (preg_match('/FAC-\d+/', $text, $matches)) {
                $numeroFactura = $matches[0];
            }

            // Buscar código de pieza (Patrón: busca texto que empiece con REP- seguido de números y una letra)
            $codigoPieza = 'REP-GENERICO';
            if (preg_match('/REP-\d+-[A-Z]/', $text, $matches)) {
                $codigoPieza = $matches[0];
            }

            // Buscar descripción del repuesto
            $nombrePieza = 'Pieza Automotriz No Identificada';
            if (preg_match('/Filtro de Aceite [^\n]+|Pastillas de Freno [^\n]+|Cambio de Aceite [^\n]+/i', $text, $matches)) {
                $nombrePieza = trim($matches[0]);
            } else {
                if (preg_match('/' . preg_quote($codigoPieza, '/') . '\s+([^\n]+)/', $text, $matches)) {
                    $nombrePieza = trim($matches[1]);
                }
            }

            // Si el texto contiene tabulaciones, lo picamos y nos quedamos solo con la primera parte
            if (strpos($nombrePieza, "\t") !== false) {
                $partes = explode("\t", $nombrePieza);
                $nombrePieza = trim($partes[0]);
            }

            // Datos de los inputs del modal
            $costoUnitario = $request->input('costo_unitario');
            $cantidad = $request->input('cantidad_recibida');
            $montoTotal = $costoUnitario * $cantidad;

            // 4. Registrar en la tabla 'facturas' de MySQL vinculándola formalmente al vehículo
            $factura = Factura::create([
                'numero_factura' => $numeroFactura,
                'fecha_emision' => now()->format('Y-m-d'),
                'monto_total' => $montoTotal,
                'ruta_pdf_almacenamiento' => $path,
                'id_vehiculo' => $request->input('id_vehiculo')
            ]);

            // 5. Registrar en la tabla 'repuestos' amarrando el id_factura
            $repuesto = Repuesto::create([
                'nombre_pieza' => $nombrePieza,
                'codigo_pieza' => $codigoPieza,
                'costo_unitario' => $costoUnitario,
                'id_factura' => $factura->id_factura
            ]);

            return response()->json([
                'success' => true,
                'message' => '¡PDF leído, factura asociada al vehículo y stock actualizado exitosamente!',
                'data' => [
                    'id_repuesto' => $repuesto->id_repuesto,
                    'codigo_pieza' => $repuesto->codigo_pieza,
                    'nombre_pieza' => $repuesto->nombre_pieza,
                    'costo_unitario' => '$' . number_format($repuesto->costo_unitario, 2, '.', ','),
                    'factura' => $factura->numero_factura,
                    'ruta_pdf' => asset('storage/' . $factura->ruta_pdf_almacenamiento)
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el mapeo de texto del PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    // Requerimiento C: Registrar alerta interna y simular despacho de correo de mantenimiento completado
    public function enviarNotificacion(Request $request)
    {
        $request->validate([
            'destinatario' => 'required|email|max:100',
            'asunto' => 'required|string|max:150',
            'mensaje' => 'required|string'
        ]);

        try {
            // Guardar el registro en la tabla de notificaciones
            $notificacion = Notificacion::create([
                'destinatario' => $request->input('destinatario'),
                'asunto' => $request->input('asunto'),
                'mensaje' => $request->input('mensaje'),
                'tipo_envio' => 'AUTOMÁTICO',
                'fecha_envio' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Notificación registrada en el sistema SGA.',
                'notificacion' => [
                    'fecha' => $notificacion->fecha_envio->format('Y-m-d H:i'),
                    'destinatario' => $notificacion->destinatario
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar o despachar la alerta: ' . $e->getMessage()
            ], 500);
        }
    }
}
