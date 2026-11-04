<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReporteVehiculo;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ReportePdfController extends Controller
{
    public function __invoke(Request $request, ReporteVehiculo $reporte): Response
    {
        $data = $request->validate(['placa' => 'required|string']);
        $resultado = $reporte->porPlaca($data['placa']);
        $placaArchivo = preg_replace('/[^A-Za-z0-9_-]/', '_', $resultado['vehiculo']->placa);

        return Pdf::loadView('reportes.vehiculo-pdf', $resultado + ['generadoEn' => now()])
            ->setPaper('a4', 'landscape')
            ->download('reporte-'.$placaArchivo.'.pdf');
    }
}
