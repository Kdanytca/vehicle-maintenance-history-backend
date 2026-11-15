<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditLog extends Model
{
    use HasFactory;

    /**
     * Nombre de la tabla física en la base de datos.
     */
    protected $table = 'audit_logs';

    /**
     * Campos permitidos para inserción masiva.
     */
    protected $fillable = [
        'user_id',
        'accion',
        'modulo',
        'descripcion',
        'usuario_nombre',
        'rol_nombre',
        'tipo_acceso',
    ];

    /**
     * Relación con el usuario que realizó el cambio.
     * En este proyecto la autenticación usa la tabla usuarios y la PK es id_usuario.
     */
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'user_id', 'id_usuario');
    }

    /**
     * Alias útil si en algún punto se desea acceder con user().
     */
    public function user()
    {
        return $this->usuario();
    }

    /**
     * Registra un cambio del sistema de forma consistente.
     *
     * @param  string  $accion  Ejemplo: crear, actualizar, eliminar
     * @param  string  $modulo  Ejemplo: mantenimientos, usuarios, clientes
     * @param  string|null  $descripcion  Texto detallado del cambio
     */
    public static function registrar(string $accion, string $modulo, ?string $descripcion = null): void
    {
        $usuarioActual = Auth::user();

        self::create([
            'user_id' => $usuarioActual?->id_usuario,
            'accion' => $accion,
            'modulo' => $modulo,
            'descripcion' => $descripcion,
            'usuario_nombre' => $usuarioActual?->username,
            'rol_nombre' => $usuarioActual?->rol?->nombre_rol,
        ]);
    }

    public static function registrarAcceso(?string $username, ?Usuario $usuario, string $tipoAcceso): void
    {
        self::create([
            'user_id' => $usuario?->id_usuario,
            'usuario_nombre' => $username,
            'rol_nombre' => $usuario?->rol?->nombre_rol,
            'tipo_acceso' => $tipoAcceso,
            'accion' => 'inicio_sesion',
            'modulo' => 'autenticacion',
            'descripcion' => $tipoAcceso === 'exitoso'
                ? 'Inicio de sesión exitoso.'
                : 'Inicio de sesión fallido.',
        ]);
    }
}
