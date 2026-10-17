<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use LogicException;

class AuditLog extends Model
{
    protected $table = 'audit_logs';

    protected $fillable = [
        'user_id',
        'accion',
        'modulo',
        'descripcion',
    ];

    // "user" apunta al modelo autenticable real del proyecto: Usuario.
    public function user(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'user_id', 'id_usuario');
    }

    // Registra un cambio asociado al usuario autenticado.
    public static function registrar(string $accion, string $modulo, ?string $descripcion): self
    {
        $userId = Auth::id();

        if ($userId === null) {
            throw new LogicException('No se puede registrar una auditoría sin un usuario autenticado.');
        }

        return static::create([
            'user_id' => $userId,
            'accion' => $accion,
            'modulo' => $modulo,
            'descripcion' => $descripcion,
        ]);
    }
}
