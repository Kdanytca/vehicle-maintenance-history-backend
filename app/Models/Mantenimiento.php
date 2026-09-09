<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mantenimiento extends Model
{
    protected $table = 'mantenimientos';

    protected $primaryKey = 'id_mantenimiento';

    protected $fillable = [
        'fecha_servicio',
        'descripcion_falla',
        'estado',
        'costo_mano_obra',
        'id_vehiculo',
        'id_usuario_encargado'
    ];

    /**
     * Obtiene el vehículo asociado al mantenimiento.
     */
    public function vehiculo()
    {
        return $this->belongsTo(Vehiculo::class, 'id_vehiculo', 'id_vehiculo');
    }

    /**
     * Obtiene el usuario del sistema encargado de supervisar este mantenimiento.
     */
    public function encargado()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario_encargado', 'id_usuario');
    }
}
