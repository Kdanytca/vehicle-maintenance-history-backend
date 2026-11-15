<?php

namespace Tests\Feature;

use App\Models\Factura;
use App\Models\Mantenimiento;
use App\Models\Propietario;
use App\Models\Repuesto;
use App\Models\Usuario;
use App\Models\Vehiculo;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RepuestoFiltroGarantiaTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $admin;
    private Vehiculo $vehiculo1;
    private Vehiculo $vehiculo2;
    private Repuesto $repuestoVigente;
    private Repuesto $repuestoVencido;
    private Repuesto $repuestoSinGarantia;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->admin = Usuario::where('username', 'admin')->firstOrFail();
        Sanctum::actingAs($this->admin);

        // Fecha fija de prueba: 2026-11-08
        $this->travelTo(now()->setDate(2026, 11, 8)->startOfDay());

        $propietario = Propietario::firstOrCreate(
            ['documento_identidad' => '07777777-7'],
            ['nombre' => 'Carlos Rodriguez', 'telefono' => '7888-9999', 'correo' => 'carlos@example.com']
        );

        $this->vehiculo1 = Vehiculo::create([
            'placa' => 'P500-600',
            'marca' => 'Honda',
            'modelo' => 'Civic',
            'anio' => 2021,
            'id_propietario' => $propietario->id_propietario,
        ]);

        $this->vehiculo2 = Vehiculo::create([
            'placa' => 'P700-800',
            'marca' => 'Mazda',
            'modelo' => '3',
            'anio' => 2022,
            'id_propietario' => $propietario->id_propietario,
        ]);

        $factura1 = Factura::create([
            'numero_factura' => 'FAC-REP-001',
            'fecha_emision' => '2026-06-01',
            'monto_total' => 250.00,
            'ruta_pdf_almacenamiento' => 'facturas/rep-001.pdf',
            'id_vehiculo' => $this->vehiculo1->id_vehiculo,
        ]);

        $factura2 = Factura::create([
            'numero_factura' => 'FAC-REP-002',
            'fecha_emision' => '2026-02-01',
            'monto_total' => 180.00,
            'ruta_pdf_almacenamiento' => 'facturas/rep-002.pdf',
            'id_vehiculo' => $this->vehiculo2->id_vehiculo,
        ]);

        // Repuesto 1: Garantía vigente (vence el 2026-12-15, en el futuro respecto a 2026-11-08)
        $this->repuestoVigente = Repuesto::create([
            'nombre_pieza' => 'Amortiguador Delantero Monroe',
            'codigo_pieza' => 'AMOR-101',
            'costo_unitario' => 125.00,
            'id_factura' => $factura1->id_factura,
            'garantia_inicio' => '2026-06-15',
            'garantia_fin' => '2026-12-15',
        ]);

        // Repuesto 2: Garantía vencida (venció el 2026-10-31, en el pasado respecto a 2026-11-08)
        $this->repuestoVencido = Repuesto::create([
            'nombre_pieza' => 'Pastillas de Freno Brembo',
            'codigo_pieza' => 'FREN-202',
            'costo_unitario' => 90.00,
            'id_factura' => $factura2->id_factura,
            'garantia_inicio' => '2026-02-01',
            'garantia_fin' => '2026-10-31',
        ]);

        // Repuesto 3: Sin garantía registrada
        $this->repuestoSinGarantia = Repuesto::create([
            'nombre_pieza' => 'Abrazadera Metálica Genérica',
            'codigo_pieza' => 'ABR-303',
            'costo_unitario' => 5.00,
            'id_factura' => $factura1->id_factura,
            'garantia_inicio' => null,
            'garantia_fin' => null,
        ]);
    }

    public function test_filtro_garantia_vigente_muestra_unicamente_repuestos_con_garantia_vigente(): void
    {
        $response = $this->getJson('/api/repuestos?garantia=vigente')->assertOk();

        $repuestos = $response->json('repuestos');
        $nombres = collect($repuestos)->pluck('nombre_pieza')->all();

        $this->assertContains('Amortiguador Delantero Monroe', $nombres);
        $this->assertNotContains('Pastillas de Freno Brembo', $nombres);
        $this->assertNotContains('Abrazadera Metálica Genérica', $nombres);

        foreach ($repuestos as $repuesto) {
            $this->assertNotNull($repuesto['garantia_fin']);
            $this->assertGreaterThanOrEqual('2026-11-08', $repuesto['garantia_fin']);
            $this->assertEquals('Vigente', $repuesto['estado_garantia']);
        }
    }

    public function test_filtro_garantia_vencida_muestra_unicamente_repuestos_con_garantia_vencida(): void
    {
        $response = $this->getJson('/api/repuestos?garantia=vencida')->assertOk();

        $repuestos = $response->json('repuestos');
        $nombres = collect($repuestos)->pluck('nombre_pieza')->all();

        $this->assertContains('Pastillas de Freno Brembo', $nombres);
        $this->assertNotContains('Amortiguador Delantero Monroe', $nombres);
        $this->assertNotContains('Abrazadera Metálica Genérica', $nombres);

        foreach ($repuestos as $repuesto) {
            $this->assertNotNull($repuesto['garantia_fin']);
            $this->assertLessThan('2026-11-08', $repuesto['garantia_fin']);
            $this->assertEquals('Vencida', $repuesto['estado_garantia']);
        }
    }

    public function test_cada_resultado_muestra_los_campos_requeridos(): void
    {
        $response = $this->getJson('/api/repuestos?garantia=vigente')->assertOk();

        $repuesto = collect($response->json('repuestos'))->firstWhere('nombre_pieza', 'Amortiguador Delantero Monroe');
        $this->assertNotNull($repuesto);

        // Criterio 3: nombre del repuesto
        $this->assertEquals('Amortiguador Delantero Monroe', $repuesto['nombre_pieza']);

        // Criterio 3: vehículo asociado
        $this->assertNotNull($repuesto['vehiculo']);
        $this->assertEquals('P500-600', $repuesto['vehiculo']['placa']);
        $this->assertEquals('Honda', $repuesto['vehiculo']['marca']);
        $this->assertEquals('Civic', $repuesto['vehiculo']['modelo']);

        // Criterio 3: fecha de vencimiento
        $this->assertEquals('2026-12-15', $repuesto['garantia_fin']);

        // Criterio 3: estado
        $this->assertEquals('Vigente', $repuesto['estado_garantia']);
    }

    public function test_el_sistema_actualiza_automaticamente_el_estado_de_la_garantia_segun_la_fecha_actual(): void
    {
        // En 2026-11-08 el repuesto está Vigente
        $this->assertEquals('Vigente', $this->repuestoVigente->fresh()->estado_garantia);

        // Desplazamos la fecha actual hacia el 2027-01-01 (fecha posterior a garantia_fin: 2026-12-15)
        $this->travelTo(now()->setDate(2027, 1, 1)->startOfDay());

        // Debe actualizar automáticamente su estado a "Vencida"
        $this->assertEquals('Vencida', $this->repuestoVigente->fresh()->estado_garantia);

        // Y al consultar el endpoint con filtro vigente, ya no debe aparecer
        $responseVigentes = $this->getJson('/api/repuestos?garantia=vigente')->assertOk();
        $nombresVigentes = collect($responseVigentes->json('repuestos'))->pluck('nombre_pieza')->all();
        $this->assertNotContains('Amortiguador Delantero Monroe', $nombresVigentes);

        // Al consultar con filtro vencida, ahora debe aparecer
        $responseVencidas = $this->getJson('/api/repuestos?garantia=vencida')->assertOk();
        $nombresVencidas = collect($responseVencidas->json('repuestos'))->pluck('nombre_pieza')->all();
        $this->assertContains('Amortiguador Delantero Monroe', $nombresVencidas);
    }

    public function test_filtro_vencida_retorna_coleccion_vacia_si_no_hay_garantias_vencidas(): void
    {
        // Eliminamos los repuestos con garantía vencida
        Repuesto::whereNotNull('garantia_fin')->whereDate('garantia_fin', '<', '2026-11-08')->delete();

        $response = $this->getJson('/api/repuestos?garantia=vencida')->assertOk();

        $this->assertEquals(0, $response->json('total'));
        $this->assertEmpty($response->json('repuestos'));
        $this->assertEquals('vencida', $response->json('filtro_garantia'));
    }

    public function test_sin_filtro_retorna_todos_los_repuestos(): void
    {
        $response = $this->getJson('/api/repuestos')->assertOk();
        $repuestos = $response->json('repuestos');

        $nombres = collect($repuestos)->pluck('nombre_pieza')->all();
        $this->assertContains('Amortiguador Delantero Monroe', $nombres);
        $this->assertContains('Pastillas de Freno Brembo', $nombres);
        $this->assertContains('Abrazadera Metálica Genérica', $nombres);
    }
}

