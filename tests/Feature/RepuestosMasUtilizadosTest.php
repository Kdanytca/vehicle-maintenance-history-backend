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

class RepuestosMasUtilizadosTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $admin;
    private Usuario $mecanico;
    private Vehiculo $vehiculo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->admin = Usuario::where('username', 'admin')->firstOrFail();
        $this->mecanico = Usuario::where('username', 'mecanico')->firstOrFail();

        $propietario = Propietario::firstOrCreate(
            ['documento_identidad' => '08888888-8'],
            ['nombre' => 'Fernando Palomo', 'telefono' => '7123-4567', 'correo' => 'fernando@example.com']
        );

        $this->vehiculo = Vehiculo::create([
            'placa' => 'P999-111',
            'marca' => 'Toyota',
            'modelo' => 'Hilux',
            'anio' => 2023,
            'id_propietario' => $propietario->id_propietario,
        ]);
    }

    public function test_solo_administradores_pueden_acceder(): void
    {
        // 1. Invitado no autenticado -> 401
        $this->getJson('/api/repuestos/mas-utilizados')->assertUnauthorized();

        // 2. Mecánico (no admin) -> 403
        Sanctum::actingAs($this->mecanico);
        $this->getJson('/api/repuestos/mas-utilizados')->assertForbidden();

        // 3. Administrador -> 200
        Sanctum::actingAs($this->admin);
        $this->getJson('/api/repuestos/mas-utilizados')->assertOk();
    }

    public function test_muestra_los_cinco_repuestos_mas_utilizados_ordenados_de_mayor_a_menor(): void
    {
        Sanctum::actingAs($this->admin);

        // Eliminamos repuestos previos para prueba limpia
        Repuesto::query()->delete();

        // Creamos 7 repuestos con diferentes frecuencias de uso
        $piezasConfig = [
            ['nombre' => 'Filtro de Aceite Sintético', 'veces' => 12, 'proveedor' => 'Bosch'],
            ['nombre' => 'Pastillas de Freno Cerámicas', 'veces' => 9, 'proveedor' => 'Brembo'],
            ['nombre' => 'Bujías de Iridio', 'veces' => 7, 'proveedor' => 'NGK'],
            ['nombre' => 'Líquido de Frenos DOT 4', 'veces' => 5, 'proveedor' => 'Motul'],
            ['nombre' => 'Bomba de Agua', 'veces' => 3, 'proveedor' => 'GMB'],
            ['nombre' => 'Amortiguador Trasero', 'veces' => 2, 'proveedor' => 'Monroe'],
            ['nombre' => 'Filtro de Aire Motor', 'veces' => 1, 'proveedor' => 'Mann Filter'],
        ];

        foreach ($piezasConfig as $cfg) {
            for ($i = 0; $i < $cfg['veces']; $i++) {
                Repuesto::create([
                    'nombre_pieza' => $cfg['nombre'],
                    'codigo_pieza' => 'SKU-' . substr(md5($cfg['nombre'] . $i), 0, 8),
                    'proveedor' => $cfg['proveedor'],
                    'costo_unitario' => 25.00,
                ]);
            }
        }

        $response = $this->getJson('/api/repuestos/mas-utilizados')->assertOk();

        $repuestos = $response->json('repuestos');

        // Criterio 1: Debe mostrar exactamente cinco repuestos
        $this->assertCount(5, $repuestos);

        // Criterio 2: Ordenados desde el más utilizado hasta el menos utilizado
        $this->assertEquals('Filtro de Aceite Sintético', $repuestos[0]['nombre_repuesto']);
        $this->assertEquals(12, $repuestos[0]['cantidad_utilizado']);
        $this->assertEquals('Bosch', $repuestos[0]['proveedor']);

        $this->assertEquals('Pastillas de Freno Cerámicas', $repuestos[1]['nombre_repuesto']);
        $this->assertEquals(9, $repuestos[1]['cantidad_utilizado']);
        $this->assertEquals('Brembo', $repuestos[1]['proveedor']);

        $this->assertEquals('Bujías de Iridio', $repuestos[2]['nombre_repuesto']);
        $this->assertEquals(7, $repuestos[2]['cantidad_utilizado']);
        $this->assertEquals('NGK', $repuestos[2]['proveedor']);

        $this->assertEquals('Líquido de Frenos DOT 4', $repuestos[3]['nombre_repuesto']);
        $this->assertEquals(5, $repuestos[3]['cantidad_utilizado']);
        $this->assertEquals('Motul', $repuestos[3]['proveedor']);

        $this->assertEquals('Bomba de Agua', $repuestos[4]['nombre_repuesto']);
        $this->assertEquals(3, $repuestos[4]['cantidad_utilizado']);
        $this->assertEquals('GMB', $repuestos[4]['proveedor']);

        // Las piezas de menor uso (Amortiguador y Filtro de Aire) NO deben estar en el top 5
        $nombres = collect($repuestos)->pluck('nombre_repuesto')->all();
        $this->assertNotContains('Amortiguador Trasero', $nombres);
        $this->assertNotContains('Filtro de Aire Motor', $nombres);
    }

    public function test_cada_resultado_muestra_los_campos_requeridos(): void
    {
        Sanctum::actingAs($this->admin);

        Repuesto::query()->delete();

        Repuesto::create([
            'nombre_pieza' => 'Kit de Distribución Gates',
            'codigo_pieza' => 'KIT-909',
            'proveedor' => 'Gates Corporation',
            'costo_unitario' => 110.00,
        ]);

        $response = $this->getJson('/api/repuestos/mas-utilizados')->assertOk();
        $repuesto = $response->json('repuestos.0');

        // Criterio 3: nombre del repuesto, cantidad de veces utilizado, proveedor asociado
        $this->assertNotNull($repuesto);
        $this->assertArrayHasKey('nombre_repuesto', $repuesto);
        $this->assertArrayHasKey('cantidad_utilizado', $repuesto);
        $this->assertArrayHasKey('proveedor', $repuesto);

        $this->assertEquals('Kit de Distribución Gates', $repuesto['nombre_repuesto']);
        $this->assertEquals(1, $repuesto['cantidad_utilizado']);
        $this->assertEquals('Gates Corporation', $repuesto['proveedor']);
    }

    public function test_proveedor_no_especificado_cuando_es_nulo(): void
    {
        Sanctum::actingAs($this->admin);

        Repuesto::query()->delete();

        Repuesto::create([
            'nombre_pieza' => 'Tornillo Grado 8',
            'codigo_pieza' => 'TOR-001',
            'proveedor' => null,
            'costo_unitario' => 1.50,
        ]);

        $response = $this->getJson('/api/repuestos/mas-utilizados')->assertOk();
        $repuesto = $response->json('repuestos.0');

        $this->assertEquals('Tornillo Grado 8', $repuesto['nombre_repuesto']);
        $this->assertEquals('Proveedor no especificado', $repuesto['proveedor']);
    }

    public function test_retorna_lista_vacia_si_no_hay_repuestos(): void
    {
        Sanctum::actingAs($this->admin);

        Repuesto::query()->delete();

        $response = $this->getJson('/api/repuestos/mas-utilizados')->assertOk();

        $this->assertEquals(0, $response->json('total'));
        $this->assertEmpty($response->json('repuestos'));
    }
}

