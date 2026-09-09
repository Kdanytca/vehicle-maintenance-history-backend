<?php

namespace Database\Seeders;

use App\Models\Factura;
use App\Models\HistorialCambio;
use App\Models\Mantenimiento;
use App\Models\Notificacion;
use App\Models\Propietario;
use App\Models\Repuesto;
use App\Models\Usuario;
use App\Models\Vehiculo;
use Illuminate\Database\Seeder;

class DatosSeeder extends Seeder
{
    public function run(): void
    {
        $propietario = Propietario::updateOrCreate([
            'documento_identidad' => '01234567-8',
        ], [
            'nombre' => 'Carlos Martinez',
            'telefono' => '7777-1111',
            'correo' => 'carlos.demo@example.com',
        ]);

        $vehiculo = Vehiculo::updateOrCreate([
            'placa' => 'P-123456',
        ], [
            'marca' => 'Toyota',
            'modelo' => 'Corolla',
            'anio' => 2020,
            'id_propietario' => $propietario->id_propietario,
        ]);

        $mecanico = Usuario::where('username', 'mecanico')->firstOrFail();

        $mantenimiento = Mantenimiento::updateOrCreate([
            'id_vehiculo' => $vehiculo->id_vehiculo,
            'fecha_servicio' => '2026-09-09',
        ], [
            'descripcion_falla' => 'Revision general de demostracion',
            'estado' => 'En Proceso',
            'costo_mano_obra' => 60.00,
            'id_usuario_encargado' => $mecanico->id_usuario,
        ]);

        $factura = Factura::updateOrCreate([
            'numero_factura' => 'FAC-DEMO-001',
        ], [
            'fecha_emision' => '2026-09-09',
            'monto_total' => 38.50,
            'ruta_pdf_almacenamiento' => 'facturas/demo-fac-001.pdf',
            'id_vehiculo' => $vehiculo->id_vehiculo,
        ]);

        Repuesto::updateOrCreate([
            'codigo_pieza' => 'REP-DEMO-001',
        ], [
            'nombre_pieza' => 'Filtro de Aceite Demo',
            'costo_unitario' => 38.50,
            'id_mantenimiento' => $mantenimiento->id_mantenimiento,
            'id_factura' => $factura->id_factura,
        ]);

        Notificacion::updateOrCreate([
            'destinatario' => $propietario->correo,
            'asunto' => 'Servicio de demostracion registrado',
        ], [
            'mensaje' => 'Su vehiculo P-123456 tiene un mantenimiento de demostracion en proceso.',
            'tipo_envio' => 'AUTOMATICO',
            'fecha_envio' => now(),
        ]);

        HistorialCambio::updateOrCreate([
            'tipo_evento' => 'LOG_SISTEMA',
            'descripcion_evento' => 'Datos de demostracion cargados desde seeders.',
        ], [
            'direccion_ip' => '127.0.0.1',
            'id_usuario' => $mecanico->id_usuario,
        ]);
    }
}
