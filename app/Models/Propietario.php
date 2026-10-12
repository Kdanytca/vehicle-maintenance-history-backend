<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Propietario extends Model
{
    protected $table = 'propietarios';

    protected $primaryKey = 'id_propietario';

    protected $fillable = [
        'nombre',
        'documento_identidad',
        'telefono',
        'correo'
    ];

    public function vehiculos()
    {
        return $this->hasMany(Vehiculo::class, 'id_propietario', 'id_propietario');
    }

    public function setDocumentoIdentidadAttribute($value): void
    {
        $this->attributes['documento_identidad'] = $this->emptyOwnerValue($value);
    }

    public function setTelefonoAttribute($value): void
    {
        $this->attributes['telefono'] = $this->emptyOwnerValue($value);
    }

    public function setCorreoAttribute($value): void
    {
        $this->attributes['correo'] = $this->emptyOwnerValue($value);
    }

    private function emptyOwnerValue($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' || strtoupper($value) === 'N/A' ? null : $value;
    }
}
