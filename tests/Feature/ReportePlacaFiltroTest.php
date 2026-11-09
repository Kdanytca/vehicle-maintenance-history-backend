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

class ReportePlacaFiltroTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $admin;
    private Vehiculo $vehiculo1;
    private Vehiculo $vehiculo2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->admin = Usuario::where('username', 'admin')->firstOrFail();
        Sanctum::actingAs($this->admin);

        $propietario = Propietario::first();

        // Vehículo 1 con mantenimientos
        $this->vehiculo1 = Vehiculo::firstOrCreate(
            ['placa' => 'P123-456'],
            [
                'marca' => 'Toyota',
                'modelo' => 'Corolla',
                'anio' => 2021,
                'id_propietario' => $propietario?->id_propietario,
            ]
        );

        $mecanico = Usuario::where('username', 'mecanico')->firstOrFail();

        Mantenimiento::create([
            'id_vehiculo' => $this->vehiculo1->id_vehiculo,
            'fecha_servicio' => '2026-10-15',
            'tipo_servicio' => 'Alineación y balanceo',
            'descripcion_falla' => 'Vibración en el volante a alta velocidad',
            'estado' => 'Completado',
            'costo_mano_obra' => 45.00,
            'id_usuario_encargado' => $mecanico->id_usuario,
        ]);

        // Vehículo 2 con otros mantenimientos
        $this->vehiculo2 = Vehiculo::create([
            'placa' => 'P987-654',
            'marca' => 'Honda',
            'modelo' => 'Civic',
            'anio' => 2022,
            'id_propietario' => $propietario?->id_propietario,
        ]);

        Mantenimiento::create([
            'id_vehiculo' => $this->vehiculo2->id_vehiculo,
            'fecha_servicio' => '2026-10-20',
            'tipo_servicio' => 'Cambio de pastillas de freno',
            'descripcion_falla' => 'Ruido metálico al frenar',
            'estado' => 'Completado',
            'costo_mano_obra' => 35.00,
            'id_usuario_encargado' => $mecanico->id_usuario,
        ]);
    }

    public function test_filtra_reporte_exitosamente_y_muestra_solo_registros_de_la_placa(): void
    {
        $response = $this->getJson('/api/reportes/placa?placa=P123-456');

        $response->assertOk();
        $response->assertJsonPath('vehiculo.placa', 'P123-456');

        // Debe contener únicamente los registros asociados a P123-456
        $mantenimientos = $response->json('mantenimientos');
        $this->assertNotEmpty($mantenimientos);

        foreach ($mantenimientos as $item) {
            $this->assertEquals($this->vehiculo1->id_vehiculo, $item['id_vehiculo']);
            $this->assertNotEquals('Ruido metálico al frenar', $item['descripcion_falla']);

            // Criterio: El reporte debe mostrar fecha, mantenimiento, mecánico responsable, descripción
            $this->assertArrayHasKey('fecha_servicio', $item);
            $this->assertArrayHasKey('tipo_servicio', $item);
            $this->assertArrayHasKey('descripcion_falla', $item);
            $this->assertArrayHasKey('encargado', $item);
            $this->assertEquals('mecanico', $item['encargado']['username']);
        }
    }

    public function test_valida_formato_invalido_de_placa_antes_de_consultar(): void
    {
        // Placas con formatos inválidos
        $formatosInvalidos = ['123-456', 'INVALIDA', 'XYZ-123456', 'P12', 'P12345678'];

        foreach ($formatosInvalidos as $placa) {
            $response = $this->getJson('/api/reportes/placa?placa='.$placa);
            $response->assertUnprocessable();
            $response->assertJsonValidationErrors('placa');
        }
    }

    public function test_muestra_mensaje_informativo_si_placa_no_posee_mantenimientos(): void
    {
        $vehiculoSinMantenimiento = Vehiculo::create([
            'placa' => 'P444-555',
            'marca' => 'Nissan',
            'modelo' => 'Sentra',
            'anio' => 2020,
        ]);

        $response = $this->getJson('/api/reportes/placa?placa='.$vehiculoSinMantenimiento->placa);

        $response->assertNotFound();
        $response->assertJsonPath('message', 'No existen mantenimientos para esta placa.');
    }

    public function test_muestra_mensaje_informativo_si_placa_no_existe(): void
    {
        $response = $this->getJson('/api/reportes/placa?placa=P000-000');

        $response->assertNotFound();
        $response->assertJsonPath('message', 'La placa ingresada no existe.');
    }
}

