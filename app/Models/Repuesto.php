<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Repuesto extends Model
{
    use HasFactory;

    protected $table = 'repuestos';
    protected $primaryKey = 'id_repuesto';

    protected $fillable = [
        'nombre_pieza',
        'codigo_pieza',
        'costo_unitario',
        'id_mantenimiento',
        'id_proveedor',
        'id_factura'
    ];

    // Relación inversa: Un repuesto pertenece a una factura cargada
    public function factura()
    {
        return $this->belongsTo(Factura::class, 'id_factura', 'id_factura');
    }
}
