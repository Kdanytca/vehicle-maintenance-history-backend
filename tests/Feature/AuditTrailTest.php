<?php

namespace Tests\Feature;

use App\Models\AuditLog;
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
            'modelo' => 'Coroll',
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

    public function test_successful_and_failed_logins_are_recorded_with_role_and_username(): void
    {
        $rol = Rol::create(['nombre_rol' => 'mecanico', 'descripcion' => 'Mecánico']);
        $usuario = Usuario::create([
            'username' => 'mecanico',
            'password_hash' => bcrypt('secret123'),
            'estado_activo' => true,
            'id_rol' => $rol->id_rol,
        ]);

        $this->postJson('/api/login', ['username' => 'mecanico', 'password' => 'incorrecta'])
            ->assertUnprocessable();
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $usuario->id_usuario,
            'usuario_nombre' => 'mecanico',
            'rol_nombre' => 'mecanico',
            'tipo_acceso' => 'fallido',
        ]);

        $this->postJson('/api/login', ['username' => 'no-existe', 'password' => 'secret123'])
            ->assertUnprocessable();
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => null,
            'usuario_nombre' => 'no-existe',
            'rol_nombre' => null,
            'tipo_acceso' => 'fallido',
        ]);

        $this->postJson('/api/login', ['username' => 'mecanico'])
            ->assertUnprocessable();
        $this->assertDatabaseHas('audit_logs', [
            'usuario_nombre' => 'mecanico',
            'rol_nombre' => 'mecanico',
            'tipo_acceso' => 'fallido',
        ]);

        $this->postJson('/api/login', ['username' => 'mecanico', 'password' => 'secret123'])
            ->assertOk();
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $usuario->id_usuario,
            'usuario_nombre' => 'mecanico',
            'rol_nombre' => 'mecanico',
            'tipo_acceso' => 'exitoso',
        ]);
    }

    public function test_access_history_can_be_filtered_by_username_and_date_and_is_admin_only(): void
    {
        $adminRole = Rol::create(['nombre_rol' => 'admin', 'descripcion' => 'Administrador']);
        $admin = Usuario::create([
            'username' => 'admin',
            'password_hash' => bcrypt('secret123'),
            'estado_activo' => true,
            'id_rol' => $adminRole->id_rol,
        ]);
        $mechanicRole = Rol::create(['nombre_rol' => 'mecanico', 'descripcion' => 'Mecánico']);
        $mechanic = Usuario::create([
            'username' => 'mecanico',
            'password_hash' => bcrypt('secret123'),
            'estado_activo' => true,
            'id_rol' => $mechanicRole->id_rol,
        ]);

        $this->travelTo(now()->setDate(2026, 11, 5)->startOfDay());
        AuditLog::registrarAcceso('mecanico', $mechanic, 'exitoso');
        $this->travel(1)->days();
        AuditLog::registrarAcceso('mecanico', $mechanic, 'fallido');
        AuditLog::registrarAcceso('admin', $admin, 'exitoso');

        $this->actingAs($admin, 'sanctum');
        $this->getJson('/api/audit-logs?usuario=mecanico&fecha=2026-11-05')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.tipo_acceso', 'exitoso')
            ->assertJsonPath('0.rol_nombre', 'mecanico');
        $this->postJson('/api/audit-logs')->assertMethodNotAllowed();

        $this->actingAs($mechanic, 'sanctum');
        $this->getJson('/api/audit-logs')->assertForbidden();
    }

    public function test_access_history_filters_by_role_and_local_calendar_date(): void
    {
        $adminRole = Rol::create(['nombre_rol' => 'admin', 'descripcion' => 'Administrador']);
        $admin = Usuario::create([
            'username' => 'admin',
            'password_hash' => bcrypt('secret123'),
            'estado_activo' => true,
            'id_rol' => $adminRole->id_rol,
        ]);
        $mechanicRole = Rol::create(['nombre_rol' => 'mecanico', 'descripcion' => 'Mecánico']);
        $mechanic = Usuario::create([
            'username' => 'mecanico',
            'password_hash' => bcrypt('secret123'),
            'estado_activo' => true,
            'id_rol' => $mechanicRole->id_rol,
        ]);

        $mechanicLog = AuditLog::create([
            'user_id' => $mechanic->id_usuario,
            'usuario_nombre' => 'mecanico',
            'rol_nombre' => 'mecanico',
            'tipo_acceso' => 'exitoso',
            'accion' => 'inicio_sesion',
            'modulo' => 'autenticacion',
        ]);
        $mechanicLog->forceFill(['created_at' => '2026-11-06 05:30:00'])->save();
        $adminLog = AuditLog::create([
            'user_id' => $admin->id_usuario,
            'usuario_nombre' => 'admin',
            'rol_nombre' => 'admin',
            'tipo_acceso' => 'exitoso',
            'accion' => 'inicio_sesion',
            'modulo' => 'autenticacion',
        ]);
        $adminLog->forceFill(['created_at' => '2026-11-06 06:30:00'])->save();

        $this->actingAs($admin, 'sanctum');
        $this->getJson('/api/audit-logs?rol=mecanico')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.rol_nombre', 'mecanico');

        $this->getJson('/api/audit-logs?fecha=2026-11-05&zona_horaria=America%2FEl_Salvador')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.usuario_nombre', 'mecanico');
    }
}
