<?php

namespace App\Services;

use App\Models\AlertaGarantia;
use App\Models\Repuesto;

class GenerarAlertasGarantia
{
    public function ejecutar(): void
    {
        $hoy = today();
        Repuesto::whereNotNull('garantia_fin')
            ->whereDate('garantia_inicio', '<=', $hoy)
            ->whereDate('garantia_fin', '<=', $hoy->copy()->addDays(30))
            ->chunkById(200, function ($repuestos) use ($hoy) {
                foreach ($repuestos as $repuesto) {
                    // La clave única también evita duplicados entre consultas y el scheduler.
                    AlertaGarantia::firstOrCreate([
                        'id_repuesto' => $repuesto->id_repuesto,
                        'tipo' => $repuesto->garantia_fin->lte($hoy) ? 'vencida' : 'por_vencer',
                        'fecha_vencimiento' => $repuesto->garantia_fin->toDateString(),
                    ]);
                }
            }, 'id_repuesto');
    }
}
