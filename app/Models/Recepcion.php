<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * G2DS-16: condiciones en las que se recibió un vehículo en el taller.
 */
class Recepcion extends Model
{
    public const NIVELES_COMBUSTIBLE = ['Vacío', '1/4', '1/2', '3/4', 'Lleno'];

    // El taller trabaja con la hora de El Salvador; el servidor guarda en UTC.
    public const ZONA_HORARIA = 'America/El_Salvador';

    protected $table = 'recepciones';

    protected $primaryKey = 'id_recepcion';

    protected $fillable = [
        'id_vehiculo',
        'kilometraje',
        'nivel_combustible',
        'danos_visibles',
        'observaciones',
        'fecha_recepcion',
        'id_usuario',
        'editado_at',
        'id_usuario_edicion',
        'motivo_edicion',
    ];

    protected $casts = [
        'kilometraje' => 'integer',
        'fecha_recepcion' => 'datetime',
        'editado_at' => 'datetime',
    ];

    public function vehiculo()
    {
        return $this->belongsTo(Vehiculo::class, 'id_vehiculo', 'id_vehiculo');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

    public function editor()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario_edicion', 'id_usuario');
    }

    /**
     * Solo se puede corregir el mismo día en que se registró (hora de El Salvador).
     */
    public function esDeHoy(): bool
    {
        return $this->fecha_recepcion->copy()->setTimezone(self::ZONA_HORARIA)->isSameDay(now(self::ZONA_HORARIA));
    }

    /**
     * Solo el administrador puede corregir, y solo el mismo día.
     */
    public function puedeEditar(?Usuario $usuario): bool
    {
        return $usuario?->rol?->nombre_rol === 'admin' && $this->esDeHoy();
    }
}