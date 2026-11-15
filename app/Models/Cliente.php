<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cliente extends Model
{
    use HasFactory;

    protected $table = 'clientes';

    protected $fillable = [
        'nombre',
        'apellido',
        'dui',
        'telefono',
        'correo_electronico',
    ];

    /**
     * Un cliente puede tener uno o varios vehículos asociados.
     */
    public function vehiculos(): HasMany
    {
        return $this->hasMany(Vehiculo::class, 'cliente_id');
    }
}
