<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlertaGarantia extends Model
{
    protected $table = 'alertas_garantia';

    protected $fillable = ['id_repuesto', 'tipo', 'fecha_vencimiento'];

    protected $casts = ['fecha_vencimiento' => 'date:Y-m-d', 'atendida_at' => 'datetime'];

    public function repuesto(): BelongsTo
    {
        return $this->belongsTo(Repuesto::class, 'id_repuesto', 'id_repuesto');
    }

    public function atendidaPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario_atencion', 'id_usuario');
    }
}
