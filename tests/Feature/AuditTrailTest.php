<?php

namespace Tests\Feature;

use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditTrailTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_vehicle_is_recorded_in_the_audit_log(): void
    {
        $rol = Rol::create([
            'nombre_rol' => 'admin',
            'descripcion' => 'Administrador del sistema',
        ]);

        $usuario = Usuario::create([
            'username' => 'admin',
            'password_hash' => bcrypt('secret123'),
            'estado_activo' => true,
            'id_rol' => $rol->id_rol,
        ]);

        $this->actingAs($usuario, 'sanctum');

        $response = $this->postJson('/api/vehiculos', [
            'placa' => 'P123-456',
            'marca' => 'Toyota',
            'modelo' => 'Corolla',
            'anio' => 2024,
            'propietario' => 'Juan Pérez',
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('audit_logs', [
            'modulo' => 'vehiculos',
            'accion' => 'crear',
            'user_id' => $usuario->id_usuario,
        ]);
    }
}
