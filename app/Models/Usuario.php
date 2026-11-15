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

    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'username',
        'password_hash',
        'estado_activo',
        'id_rol',
        'intentos_fallidos',
        'bloqueado_hasta',
        'clave_recuperacion_hash',
    ];

    protected $hidden = [
        'password_hash',
        'clave_recuperacion_hash',
    ];

    protected $casts = [
        'estado_activo' => 'boolean',
        'intentos_fallidos' => 'integer',
        'bloqueado_hasta' => 'datetime',
    ];

    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    public function getAuthIdentifierName()
    {
        return 'id_usuario';
    }

    public function getAuthIdentifier()
    {
        return $this->getAttribute($this->getAuthIdentifierName());
    }

    public function rol()
    {
        return $this->belongsTo(Rol::class, 'id_rol', 'id_rol');
    }

    public function mantenimientos()
    {
        return $this->hasMany(Mantenimiento::class, 'id_usuario_encargado', 'id_usuario');
    }
}