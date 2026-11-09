<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Repuesto extends Model
{
    use HasFactory;

    protected $table = 'repuestos';
    protected $primaryKey = 'id_repuesto';

    protected $fillable = [
        'nombre_pieza',
        'codigo_pieza',
        'proveedor',
        'costo_unitario',
        'id_mantenimiento',
        'id_factura',
        'garantia_inicio',
        'garantia_fin',
    ];

    protected $casts = [
        'garantia_inicio' => 'date:Y-m-d',
        'garantia_fin' => 'date:Y-m-d',
    ];

    protected $appends = [
        'estado_garantia',
        'vehiculo',
    ];

    public function getEstadoGarantiaAttribute(): ?string
    {
        if (empty($this->garantia_fin)) {
            return 'Sin garantía';
        }

        $fechaFin = $this->garantia_fin instanceof \Carbon\CarbonInterface
            ? $this->garantia_fin
            : \Illuminate\Support\Carbon::parse($this->garantia_fin);

        return $fechaFin->startOfDay()->gte(now()->startOfDay()) ? 'Vigente' : 'Vencida';
    }

    public function getVehiculoAttribute(): ?Vehiculo
    {
        return $this->factura?->vehiculo ?? $this->mantenimiento?->vehiculo;
    }

    public function scopeGarantiaVigente($query, $date = null)
    {
        $date = $date ? \Illuminate\Support\Carbon::parse($date)->toDateString() : now()->toDateString();

        return $query->whereNotNull('garantia_fin')->whereDate('garantia_fin', '>=', $date);
    }

    public function scopeGarantiaVencida($query, $date = null)
    {
        $date = $date ? \Illuminate\Support\Carbon::parse($date)->toDateString() : now()->toDateString();

        return $query->whereNotNull('garantia_fin')->whereDate('garantia_fin', '<', $date);
    }

    public static function topMasUtilizados(int $limite = 5)
    {
        return self::query()
            ->select(
                'nombre_pieza',
                \Illuminate\Support\Facades\DB::raw('COUNT(*) as cantidad_utilizado'),
                \Illuminate\Support\Facades\DB::raw("COALESCE(MAX(proveedor), 'Proveedor no especificado') as proveedor")
            )
            ->whereNotNull('nombre_pieza')
            ->where('nombre_pieza', '!=', '')
            ->groupBy('nombre_pieza')
            ->orderByDesc('cantidad_utilizado')
            ->orderBy('nombre_pieza')
            ->limit($limite)
            ->get()
            ->map(function ($item) {
                return [
                    'nombre_repuesto' => $item->nombre_pieza,
                    'nombre_pieza' => $item->nombre_pieza,
                    'cantidad_utilizado' => (int) $item->cantidad_utilizado,
                    'proveedor' => $item->proveedor ?: 'Proveedor no especificado',
                ];
            });
    }

    public function mantenimiento()
    {
        return $this->belongsTo(Mantenimiento::class, 'id_mantenimiento', 'id_mantenimiento');
    }

    // Relación inversa: Un repuesto pertenece a una factura cargada
    public function factura()
    {
        return $this->belongsTo(Factura::class, 'id_factura', 'id_factura');
    }
}
