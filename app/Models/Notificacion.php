<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notificacion extends Model
{
    use HasFactory;

    protected $table = 'notificaciones';
    protected $primaryKey = 'id_notificacion';


    protected $fillable = [
        'destinatario',
        'asunto',
        'mensaje',
        'tipo_envio',
        'fecha_envio'
    ];
}
