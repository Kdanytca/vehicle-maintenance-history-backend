<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Usuario extends Authenticatable
{
    use HasApiTokens;

    protected $table = 'usuarios';
    protected $primaryKey = 'id_usuario';
    protected $rememberTokenName = 'remember_token';

    // Indica explícitamente que tu ID es un entero autoincrementable
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'username',
        'password_hash',
        'estado_activo',
        'id_rol',
    ];

    protected $hidden = [
        'password_hash',
    ];

    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    //Esto le dice a Laravel qué NOMBRE tiene la columna de la llave primaria
    public function getAuthIdentifierName()
    {
        return 'id_usuario';
    }

    //Esto le devuelve a Laravel el VALOR real del ID del usuario activo
    public function getAuthIdentifier()
    {
        return $this->getAttribute($this->getAuthIdentifierName());
    }

    public function rol()
    {
        return $this->belongsTo(Rol::class, 'id_rol', 'id_rol');
    }

    /**
     * Obtiene todos los mantenimientos encargados a este usuario.
     */
    public function mantenimientos()
    {
        return $this->hasMany(Mantenimiento::class, 'id_usuario_encargado', 'id_usuario');
    }
}
