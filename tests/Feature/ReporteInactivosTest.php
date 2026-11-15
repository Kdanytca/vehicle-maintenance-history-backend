<?php

namespace Tests\Feature;

use App\Models\Mantenimiento;
use App\Models\Propietario;
use App\Models\Usuario;
use App\Models\Vehiculo;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReporteInactivosTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $admin;
    private Usuario $mecanico;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->admin = Usuario::where('username', 'admin')->firstOrFail();
        $this->mecanico = Usuario::where('username', 'mecanico')->firstOrFail();

        // Fijamos fecha base: 2026-11-08
        $this->travelTo(now()->setDate(2026, 11, 8)->startOfDay());

        // Limpiamos vehículos creados por seeders para tener control exacto
        Mantenimiento::query()->delete();
        Vehiculo::query()->forceDelete();

        // 1. Propietario con teléfono
        $prop1 = Propietario::create([
            'nombre' => 'Carlos Rodriguez',
            'telefono' => '7123-4567',
            'correo' => 'carlos@example.com',
            'documento_identidad' => '01111111-1',
        ]);

        $prop2 = Propietario::create([
            'nombre' => 'Maria Lopez',
            'telefono' => '7890-1234',
            'correo' => 'maria@example.com',
            'documento_identidad' => '02222222-2',
        ]);

        // V1: Activo reciente (mantenimiento hace 1 mes: 2026-10-08)
        $v1 = Vehiculo::create(['placa' => 'P111-111', 'marca' => 'Toyota', 'modelo' => 'Corolla', 'anio' => 2021, 'id_propietario' => $prop1->id_propietario]);
        Mantenimiento::create([
            'id_vehiculo' => $v1->id_vehiculo,
            'fecha_servicio' => '2026-10-08',
            'tipo_servicio' => 'Cambio de aceite',
            'descripcion_falla' => 'Servicio preventivo',
            'estado' => 'Completado',
            'costo_mano_obra' => 25.00,
            'id_usuario_encargado' => $this->mecanico->id_usuario,
        ]);

        // V2: Inactivo hace 4 meses (último mantenimiento: 2026-07-01)
        // -> Inactivo para 3 meses, pero ACTIVO para 6 y 12 meses
        $v2 = Vehiculo::create(['placa' => 'P222-222', 'marca' => 'Nissan', 'modelo' => 'Sentra', 'anio' => 2019, 'id_propietario' => $prop2->id_propietario]);
        Mantenimiento::create([
            'id_vehiculo' => $v2->id_vehiculo,
            'fecha_servicio' => '2026-07-01',
            'tipo_servicio' => 'Frenos',
            'descripcion_falla' => 'Cambio de pastillas',
            'estado' => 'Completado',
            'costo_mano_obra' => 35.00,
            'id_usuario_encargado' => $this->mecanico->id_usuario,
        ]);

        // V3: Inactivo hace 8 meses (último mantenimiento: 2026-03-01)
        // -> Inactivo para 3 y 6 meses, pero ACTIVO para 12 meses
        $v3 = Vehiculo::create(['placa' => 'P333-333', 'marca' => 'Hyundai', 'modelo' => 'Elantra', 'anio' => 2018, 'id_propietario' => $prop1->id_propietario]);
        Mantenimiento::create([
            'id_vehiculo' => $v3->id_vehiculo,
            'fecha_servicio' => '2026-03-01',
            'tipo_servicio' => 'Suspensión',
            'descripcion_falla' => 'Amortiguadores delanteros',
            'estado' => 'Completado',
            'costo_mano_obra' => 50.00,
            'id_usuario_encargado' => $this->mecanico->id_usuario,
        ]);

        // V4: Inactivo hace 15 meses (último mantenimiento: 2025-08-01)
        // -> Inactivo para 3, 6 y 12 meses
        $v4 = Vehiculo::create(['placa' => 'P444-444', 'marca' => 'Kia', 'modelo' => 'Rio', 'anio' => 2017, 'id_propietario' => $prop2->id_propietario]);
        Mantenimiento::create([
            'id_vehiculo' => $v4->id_vehiculo,
            'fecha_servicio' => '2025-08-01',
            'tipo_servicio' => 'Afinamiento mayor',
            'descripcion_falla' => 'Bujías y filtros',
            'estado' => 'Completado',
            'costo_mano_obra' => 60.00,
            'id_usuario_encargado' => $this->mecanico->id_usuario,
        ]);

        // V5: Sin mantenimientos registrados nunca
        // -> Inactivo para todos los períodos
        Vehiculo::create(['placa' => 'P555-555', 'marca' => 'Honda', 'modelo' => 'Civic', 'anio' => 2022, 'id_propietario' => $prop1->id_propietario]);
    }

    public function test_solo_administradores_pueden_acceder(): void
    {
        // Sin autenticar
        $this->getJson('/api/reportes/vehiculos-inactivos')->assertUnauthorized();

        // Autenticado como mecánico
        Sanctum::actingAs($this->mecanico);
        $this->getJson('/api/reportes/vehiculos-inactivos')->assertForbidden();

        // Autenticado como administrador
        Sanctum::actingAs($this->admin);
        $this->getJson('/api/reportes/vehiculos-inactivos')->assertOk();
    }

    public function test_permite_seleccionar_periodo_3_meses(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/reportes/vehiculos-inactivos?periodo=3')->assertOk();
        $placas = collect($response->json('vehiculos'))->pluck('placa')->all();

        // V1 (hace 1 mes) no debe aparecer
        $this->assertNotContains('P111-111', $placas);

        // V2 (4 meses), V3 (8 meses), V4 (15 meses) y V5 (sin mantenimientos) deben aparecer
        $this->assertContains('P222-222', $placas);
        $this->assertContains('P333-333', $placas);
        $this->assertContains('P444-444', $placas);
        $this->assertContains('P555-555', $placas);
        $this->assertCount(4, $placas);
    }

    public function test_permite_seleccionar_periodo_6_meses(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/reportes/vehiculos-inactivos?periodo=6')->assertOk();
        $placas = collect($response->json('vehiculos'))->pluck('placa')->all();

        // V1 (1 mes) y V2 (4 meses) tuvieron servicio en los últimos 6 meses -> no deben aparecer
        $this->assertNotContains('P111-111', $placas);
        $this->assertNotContains('P222-222', $placas);

        // V3 (8 meses), V4 (15 meses) y V5 (nunca) deben aparecer
        $this->assertContains('P333-333', $placas);
        $this->assertContains('P444-444', $placas);
        $this->assertContains('P555-555', $placas);
        $this->assertCount(3, $placas);
    }

    public function test_permite_seleccionar_periodo_12_meses(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/reportes/vehiculos-inactivos?periodo=12')->assertOk();
        $placas = collect($response->json('vehiculos'))->pluck('placa')->all();

        // V1, V2 y V3 tuvieron servicio en los últimos 12 meses -> no deben aparecer
        $this->assertNotContains('P111-111', $placas);
        $this->assertNotContains('P222-222', $placas);
        $this->assertNotContains('P333-333', $placas);

        // V4 (15 meses) y V5 (nunca) deben aparecer
        $this->assertContains('P444-444', $placas);
        $this->assertContains('P555-555', $placas);
        $this->assertCount(2, $placas);
    }

    public function test_reporte_muestra_campos_requeridos(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/reportes/vehiculos-inactivos?periodo=3')->assertOk();
        $vehiculo = collect($response->json('vehiculos'))->firstWhere('placa', 'P222-222');

        $this->assertNotNull($vehiculo);
        // Criterio 3: placa del vehículo
        $this->assertEquals('P222-222', $vehiculo['placa']);
        // Criterio 3: propietario
        $this->assertEquals('Maria Lopez', $vehiculo['propietario']);
        // Criterio 3: teléfono de contacto
        $this->assertEquals('7890-1234', $vehiculo['telefono']);
        // Criterio 3: fecha del último mantenimiento
        $this->assertEquals('2026-07-01', $vehiculo['fecha_ultimo_mantenimiento']);
    }

    public function test_permite_ordenar_por_fecha_del_ultimo_mantenimiento(): void
    {
        Sanctum::actingAs($this->admin);

        // Orden descendente (más recientes primero)
        $respDesc = $this->getJson('/api/reportes/vehiculos-inactivos?periodo=3&orden=desc')->assertOk();
        $fechasDesc = collect($respDesc->json('vehiculos'))->pluck('fecha_ultimo_mantenimiento')->filter()->values();
        $this->assertEquals(['2026-07-01', '2026-03-01', '2025-08-01'], $fechasDesc->all());

        // Orden ascendente (más antiguos / null primero)
        $respAsc = $this->getJson('/api/reportes/vehiculos-inactivos?periodo=3&orden=asc')->assertOk();
        $fechasAsc = collect($respAsc->json('vehiculos'))->pluck('fecha_ultimo_mantenimiento')->filter()->values();
        $this->assertEquals(['2025-08-01', '2026-03-01', '2026-07-01'], $fechasAsc->all());
    }
}

