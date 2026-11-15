<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Configuracion extends Model
{
    public const CLAVE_DIAS_ALERTA_GARANTIA = 'dias_alerta_garantia';

    public const DIAS_ALERTA_GARANTIA_PREDETERMINADOS = 15;

    protected $table = 'configuraciones';

    protected $primaryKey = 'clave';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['clave', 'valor'];

    protected $casts = ['valor' => 'integer'];

    public static function diasPreviosAlertaGarantia(): int
    {
        return (int) static::firstOrCreate(
            ['clave' => self::CLAVE_DIAS_ALERTA_GARANTIA],
            ['valor' => self::DIAS_ALERTA_GARANTIA_PREDETERMINADOS],
        )->valor;
    }
}
