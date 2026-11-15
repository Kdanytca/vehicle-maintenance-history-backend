<?php

namespace Tests\Feature;

use App\Models\AlertaGarantia;
use App\Models\Factura;
use App\Models\Propietario;
use App\Models\Repuesto;
use App\Models\Usuario;
use App\Models\Vehiculo;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GarantiasVencidasPanelTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $admin;
    private Propietario $propietario1;
    private Propietario $propietario2;
    private Vehiculo $vehiculo1;
    private Vehiculo $vehiculo2;
    private Repuesto $repuestoVencido1;
    private Repuesto $repuestoVencido2;
    private Repuesto $repuestoPorVencer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->admin = Usuario::where('username', 'admin')->firstOrFail();
        Sanctum::actingAs($this->admin);

        // Fijamos la fecha actual en 2026-11-08
        $this->travelTo(now()->setDate(2026, 11, 8)->startOfDay());

        // Propietario 1 y Vehículo 1
        $this->propietario1 = Propietario::firstOrCreate(
            ['documento_identidad' => '05555555-5'],
            ['nombre' => 'Ana Hernandez', 'telefono' => '7111-2222', 'correo' => 'ana@example.com']
        );
        $this->vehiculo1 = Vehiculo::create([
            'placa' => 'P100-200',
            'marca' => 'Toyota',
            'modelo' => 'Yaris',
            'anio' => 2020,
            'id_propietario' => $this->propietario1->id_propietario,
        ]);
        $factura1 = Factura::create([
            'numero_factura' => 'FAC-TEST-001',
            'fecha_emision' => '2026-01-10',
            'monto_total' => 120.00,
            'ruta_pdf_almacenamiento' => 'facturas/test-001.pdf',
            'id_vehiculo' => $this->vehiculo1->id_vehiculo,
        ]);

        // Repuesto 1: vencido hace 5 días (2026-11-03)
        $this->repuestoVencido1 = Repuesto::create([
            'nombre_pieza' => 'Bomba de Agua GMB',
            'codigo_pieza' => 'BOM-001',
            'costo_unitario' => 65.00,
            'id_factura' => $factura1->id_factura,
            'garantia_inicio' => '2026-05-03',
            'garantia_fin' => '2026-11-03',
        ]);

        // Propietario 2 y Vehículo 2
        $this->propietario2 = Propietario::create([
            'nombre' => 'Roberto Gomez',
            'documento_identidad' => '06666666-6',
            'telefono' => '7333-4444',
            'correo' => 'roberto@example.com',
        ]);
        $this->vehiculo2 = Vehiculo::create([
            'placa' => 'P300-400',
            'marca' => 'Nissan',
            'modelo' => 'Sentra',
            'anio' => 2019,
            'id_propietario' => $this->propietario2->id_propietario,
        ]);
        $factura2 = Factura::create([
            'numero_factura' => 'FAC-TEST-002',
            'fecha_emision' => '2026-02-15',
            'monto_total' => 85.00,
            'ruta_pdf_almacenamiento' => 'facturas/test-002.pdf',
            'id_vehiculo' => $this->vehiculo2->id_vehiculo,
        ]);

        // Repuesto 2: vencido hace 20 días (2026-10-19)
        $this->repuestoVencido2 = Repuesto::create([
            'nombre_pieza' => 'Alternador Bosch',
            'codigo_pieza' => 'ALT-002',
            'costo_unitario' => 150.00,
            'id_factura' => $factura2->id_factura,
            'garantia_inicio' => '2026-04-19',
            'garantia_fin' => '2026-10-19',
        ]);

        // Repuesto 3: POR VENCER (en 10 días, 2026-11-18) - NO debe aparecer en panel vencidas
        $this->repuestoPorVencer = Repuesto::create([
            'nombre_pieza' => 'Batería Willard',
            'codigo_pieza' => 'BAT-003',
            'costo_unitario' => 90.00,
            'id_factura' => $factura1->id_factura,
            'garantia_inicio' => '2026-05-18',
            'garantia_fin' => '2026-11-18',
        ]);
    }

    public function test_solo_muestra_garantias_cuya_fecha_haya_superado_la_fecha_actual(): void
    {
        $response = $this->getJson('/api/garantias-vencidas')->assertOk();

        $garantias = $response->json('garantias');
        $this->assertNotEmpty($garantias);

        foreach ($garantias as $item) {
            // Debe ser menor o igual a la fecha actual (2026-11-08)
            $this->assertLessThanOrEqual('2026-11-08', $item['fecha_vencimiento']);
            $this->assertGreaterThan(0, $item['dias_vencidos']);

            // NO debe incluir la batería que vence el 2026-11-18
            $this->assertNotEquals('Batería Willard', $item['repuesto']['nombre_pieza']);
        }
    }

    public function test_cada_garantia_vencida_muestra_los_campos_requeridos(): void
    {
        $response = $this->getJson('/api/garantias-vencidas')->assertOk();

        $garantia = collect($response->json('garantias'))->firstWhere('repuesto.nombre_pieza', 'Bomba de Agua GMB');
        $this->assertNotNull($garantia);

        // Criterio 3: nombre del repuesto
        $this->assertEquals('Bomba de Agua GMB', $garantia['repuesto']['nombre_pieza']);

        // Criterio 3: vehículo asociado
        $vehiculo = $garantia['repuesto']['factura']['vehiculo'];
        $this->assertEquals('P100-200', $vehiculo['placa']);
        $this->assertEquals('Toyota', $vehiculo['marca']);

        // Criterio 3: propietario
        $this->assertEquals('Ana Hernandez', $vehiculo['propietario']['nombre']);

        // Criterio 3: fecha de vencimiento
        $this->assertEquals('2026-11-03', $garantia['fecha_vencimiento']);

        // Criterio 3: cantidad de días vencidos (del 2026-11-03 al 2026-11-08 son 5 días)
        $this->assertEquals(5, $garantia['dias_vencidos']);
    }

    public function test_administrador_puede_marcar_garantia_como_gestionada_y_actualiza_estado(): void
    {
        $response = $this->getJson('/api/garantias-vencidas')->assertOk();
        $id = $response->json('garantias.0.id');

        // Criterio 4: El administrador debe poder marcar una garantía como "Gestionada"
        $patchResponse = $this->patchJson("/api/alertas-garantia/{$id}/gestionar")->assertOk();
        $patchResponse->assertJsonPath('alerta.estado', 'Gestionada');
        $this->assertNotNull($patchResponse->json('alerta.atendida_at'));

        // Criterio 5: El sistema actualiza automáticamente el estado de la garantía
        $check = $this->getJson('/api/garantias-vencidas?estado=gestionadas')->assertOk();
        $itemGestionado = collect($check->json('garantias'))->firstWhere('id', $id);
        $this->assertNotNull($itemGestionado);
        $this->assertEquals('Gestionada', $itemGestionado['estado']);
        $this->assertEquals('admin', $itemGestionado['atendida_por']['username']);
    }

    public function test_permite_filtrar_resultados_por_rango_de_fechas(): void
    {
        // Filtrar rango donde solo cae repuestoVencido2 (2026-10-19)
        $response = $this->getJson('/api/garantias-vencidas?fecha_desde=2026-10-01&fecha_hasta=2026-10-31')->assertOk();
        $nombres = collect($response->json('garantias'))->pluck('repuesto.nombre_pieza')->all();

        $this->assertContains('Alternador Bosch', $nombres);
        $this->assertNotContains('Bomba de Agua GMB', $nombres);
    }

    public function test_permite_filtrar_resultados_por_propietario(): void
    {
        $response = $this->getJson('/api/garantias-vencidas?propietario=Roberto')->assertOk();
        $garantias = $response->json('garantias');

        $this->assertCount(1, $garantias);
        $this->assertEquals('Alternador Bosch', $garantias[0]['repuesto']['nombre_pieza']);
        $this->assertEquals('Roberto Gomez', $garantias[0]['repuesto']['factura']['vehiculo']['propietario']['nombre']);
    }
}
