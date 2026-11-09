<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlertaGarantia extends Model
{
    protected $table = 'alertas_garantia';

    protected $fillable = ['id_repuesto', 'tipo', 'fecha_vencimiento', 'atendida_at', 'id_usuario_atencion'];

    protected $casts = ['fecha_vencimiento' => 'date:Y-m-d', 'atendida_at' => 'datetime'];

    protected $appends = ['dias_vencidos', 'estado'];

    public function repuesto(): BelongsTo
    {
        return $this->belongsTo(Repuesto::class, 'id_repuesto', 'id_repuesto');
    }

    public function atendidaPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario_atencion', 'id_usuario');
    }

    public function getDiasVencidosAttribute(): int
    {
        if (! $this->fecha_vencimiento) {
            return 0;
        }
        $vencimiento = \Carbon\Carbon::parse($this->fecha_vencimiento)->startOfDay();
        $hoy = \Carbon\Carbon::today();

        if ($vencimiento->gt($hoy)) {
            return 0;
        }

        $dias = (int) $vencimiento->diffInDays($hoy);

        return max(1, $dias);
    }

    public function getEstadoAttribute(): string
    {
        return $this->atendida_at ? 'Gestionada' : 'Pendiente';
    }
}
