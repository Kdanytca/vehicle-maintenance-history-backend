<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Factura extends Model
{
    use HasFactory;

    protected $table = 'facturas';
    protected $primaryKey = 'id_factura';

    protected $fillable = [
        'numero_factura',
        'fecha_emision',
        'monto_total',
        'ruta_pdf_almacenamiento',
        'id_vehiculo'
    ];

    /**
     * Relación: Una factura pertenece a un Vehículo.
     */
    public function vehiculo()
    {
        return $this->belongsTo(Vehiculo::class, 'id_vehiculo', 'id_vehiculo');
    }

    /**
     * Relación: Una factura puede tener muchos repuestos asociados.
     */
    public function repuestos()
    {
        return $this->hasMany(Repuesto::class, 'id_factura', 'id_factura');
    }
}
