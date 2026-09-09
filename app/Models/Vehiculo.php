<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehiculo extends Model
{
    protected $table = 'vehiculos';

    protected $primaryKey = 'id_vehiculo';

    protected $fillable = [
        'placa',
        'marca',
        'modelo',
        'anio',
        'id_propietario'
    ];

    public function propietario()
    {
        return $this->belongsTo(
            Propietario::class,
            'id_propietario',
            'id_propietario'
        );
    }
}