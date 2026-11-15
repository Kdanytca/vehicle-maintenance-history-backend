<?php

namespace Tests\Feature;

use App\Models\Configuracion;
use App\Models\Repuesto;
use App\Models\Usuario;
use App\Models\Vehiculo;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GarantiaAlertasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        Sanctum::actingAs(Usuario::where('username', 'admin')->firstOrFail());
        $this->travelTo(now()->setDate(2026, 11, 4)->startOfDay());
    }

    public function test_generates_only_due_alerts_without_duplicates(): void
    {
        foreach (['2026-11-03', '2026-11-04', '2026-11-19', '2026-11-20'] as $fin) {
            Repuesto::create(['nombre_pieza' => 'Filtro', 'costo_unitario' => 10,
                'garantia_inicio' => '2026-01-01', 'garantia_fin' => $fin]);
        }
        Repuesto::create(['nombre_pieza' => 'Futura', 'costo_unitario' => 10,
            'garantia_inicio' => '2026-11-10', 'garantia_fin' => '2026-11-20']);
        $this->getJson('/api/alertas-garantia')->assertOk()->assertJsonCount(3);
        $this->getJson('/api/alertas-garantia')->assertOk()->assertJsonCount(3);
        $this->assertDatabaseCount('alertas_garantia', 3);
        $this->assertDatabaseHas('alertas_garantia', ['tipo' => 'por_vencer', 'fecha_vencimiento' => '2026-11-19']);
    }

    public function test_alert_threshold_defaults_to_15_days_and_can_be_updated(): void
    {
        Configuracion::where('clave', Configuracion::CLAVE_DIAS_ALERTA_GARANTIA)->delete();
        $this->getJson('/api/configuracion-alertas-garantia')->assertOk()
            ->assertJsonPath('dias_previos', 15);
        $this->assertDatabaseHas('configuraciones', ['clave' => 'dias_alerta_garantia', 'valor' => 15]);

        $this->putJson('/api/configuracion-alertas-garantia', ['dias_previos' => 0])->assertUnprocessable();
        $this->putJson('/api/configuracion-alertas-garantia', ['dias_previos' => 8])->assertOk()
            ->assertJsonPath('dias_previos', 8);
        $this->getJson('/api/configuracion-alertas-garantia')->assertOk()
            ->assertJsonPath('dias_previos', 8);
    }

    public function test_alert_generation_uses_the_configured_threshold(): void
    {
        foreach (['2026-11-09', '2026-11-10'] as $fin) {
            Repuesto::create(['nombre_pieza' => 'Filtro', 'costo_unitario' => 10,
                'garantia_inicio' => '2026-01-01', 'garantia_fin' => $fin]);
        }

        $this->getJson('/api/alertas-garantia')->assertOk()->assertJsonCount(2);
        $this->putJson('/api/configuracion-alertas-garantia', ['dias_previos' => 5])->assertOk();
        $this->getJson('/api/alertas-garantia')->assertOk()->assertJsonCount(1)
            ->assertJsonPath('0.fecha_vencimiento', '2026-11-09');
        $this->assertDatabaseCount('alertas_garantia', 1);
    }

    public function test_attention_is_persistent_audited_and_idempotent_and_expiry_is_a_new_alert(): void
    {
        Repuesto::firstOrFail()->update(['garantia_inicio' => '2026-01-01', 'garantia_fin' => '2026-11-05']);
        $id = $this->getJson('/api/alertas-garantia')->assertOk()->json('0.id');
        $first = $this->patchJson("/api/alertas-garantia/$id/atender")->assertOk()
            ->assertJsonPath('message', 'Alerta marcada como atendida.')->json('alerta.atendida_at');
        $this->getJson('/api/alertas-garantia')->assertOk()->assertJsonCount(0);
        $this->travel(1)->hours();
        $this->patchJson("/api/alertas-garantia/$id/atender")->assertOk()->assertJsonPath('alerta.atendida_at', $first);
        $this->getJson('/api/alertas-garantia?estado=atendidas')->assertOk()->assertJsonCount(1)
            ->assertJsonPath('0.atendida_por.username', 'admin');
        $this->travel(1)->days();
        $this->getJson('/api/alertas-garantia')->assertOk()->assertJsonCount(1)->assertJsonPath('0.tipo', 'vencida');
        $this->assertDatabaseCount('alertas_garantia', 2);
    }

    public function test_only_admin_can_attend(): void
    {
        Repuesto::firstOrFail()->update(['garantia_inicio' => '2026-01-01', 'garantia_fin' => '2026-11-04']);
        $id = $this->getJson('/api/alertas-garantia')->json('0.id');
        Sanctum::actingAs(Usuario::whereHas('rol', fn ($q) => $q->where('nombre_rol', 'mecanico'))->firstOrFail());
        $this->patchJson("/api/alertas-garantia/$id/atender")->assertForbidden();
        $this->assertDatabaseHas('alertas_garantia', ['id' => $id, 'atendida_at' => null]);
    }

    public function test_only_admin_can_view_or_update_warranty_alert_configuration(): void
    {
        Sanctum::actingAs(Usuario::whereHas('rol', fn ($q) => $q->where('nombre_rol', 'mecanico'))->firstOrFail());

        $this->getJson('/api/configuracion-alertas-garantia')->assertForbidden();
        $this->putJson('/api/configuracion-alertas-garantia', ['dias_previos' => 7])->assertForbidden();
        $this->assertDatabaseHas('configuraciones', ['clave' => 'dias_alerta_garantia', 'valor' => 15]);
    }

    public function test_upload_persists_warranty_and_rejects_invalid_or_incomplete_dates(): void
    {
        Storage::fake('public');
        $data = ['id_vehiculo' => Vehiculo::firstOrFail()->id_vehiculo, 'cantidad_recibida' => 1,
            'costo_unitario' => 10, 'comprobante_pdf' => UploadedFile::fake()->createWithContent('factura.pdf', "%PDF-1.4\n(FAC-123)\n%%EOF"),
            'garantia_inicio' => '2026-01-01', 'garantia_fin' => '2026-11-05'];
        $this->postJson('/api/repuestos/cargar-pdf', $data)->assertCreated()
            ->assertJsonPath('data.garantia_fin', '2026-11-05');
        $this->postJson('/api/repuestos/cargar-pdf', array_replace($data, ['garantia_fin' => '2025-01-01']))->assertUnprocessable();
        $this->postJson('/api/repuestos/cargar-pdf', array_replace($data, ['garantia_fin' => null]))->assertUnprocessable();
        $this->postJson('/api/repuestos/cargar-pdf', array_replace($data, [
            'comprobante_pdf' => UploadedFile::fake()->createWithContent('factura-sin-garantia.pdf', "%PDF-1.4\n(FAC-124)\n%%EOF"),
            'garantia_inicio' => null,
            'garantia_fin' => null,
        ]))->assertCreated();
    }

    public function test_duplicate_invoice_number_is_rejected_before_saving_file_or_records(): void
    {
        Storage::fake('public');
        $data = [
            'id_vehiculo' => Vehiculo::firstOrFail()->id_vehiculo,
            'cantidad_recibida' => 1,
            'costo_unitario' => 10,
            'comprobante_pdf' => UploadedFile::fake()->createWithContent('factura.pdf', "%PDF-1.4\n(FAC-456)\n%%EOF"),
        ];

        $this->postJson('/api/repuestos/cargar-pdf', $data)->assertCreated();
        $this->postJson('/api/repuestos/cargar-pdf', array_replace($data, [
            'comprobante_pdf' => UploadedFile::fake()->createWithContent('factura-duplicada.pdf', "%PDF-1.4\n(FAC-456)\n%%EOF"),
        ]))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'El número de factura FAC-456 ya fue registrado previamente.')
            ->assertJsonValidationErrors('numero_factura');

        $this->assertDatabaseCount('facturas', 2);
        $this->assertDatabaseCount('repuestos', 2);
        $this->assertCount(1, Storage::disk('public')->allFiles('facturas'));
    }

    public function test_invoice_number_can_be_provided_when_pdf_text_cannot_be_extracted(): void
    {
        Storage::fake('public');
        $this->postJson('/api/repuestos/cargar-pdf', [
            'id_vehiculo' => Vehiculo::firstOrFail()->id_vehiculo,
            'cantidad_recibida' => 1,
            'costo_unitario' => 10,
            'numero_factura' => 'fac-manual-001',
            'comprobante_pdf' => UploadedFile::fake()->create('factura.pdf', 10, 'application/pdf'),
        ])->assertCreated()->assertJsonPath('data.factura.numero_factura', 'FAC-MANUAL-001');
    }

    public function test_command_generates_alerts_and_guest_cannot_access_them(): void
    {
        Repuesto::firstOrFail()->update(['garantia_inicio' => '2026-01-01', 'garantia_fin' => '2026-11-04']);
        $this->artisan('garantias:generar-alertas')->assertSuccessful();
        $this->assertDatabaseCount('alertas_garantia', 1);
        $this->app['auth']->forgetGuards();
        $this->getJson('/api/alertas-garantia')->assertUnauthorized();
    }
}
