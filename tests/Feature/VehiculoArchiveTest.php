<?php

namespace Tests\Feature;

use App\Models\HistorialCambio;
use App\Models\Mantenimiento;
use App\Models\Usuario;
use App\Models\Vehiculo;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VehiculoArchiveTest extends TestCase
{
    use RefreshDatabase;

    private Vehiculo $vehiculo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        Sanctum::actingAs(Usuario::where('username', 'admin')->firstOrFail());
        $this->vehiculo = Vehiculo::firstOrFail();
    }

    private function archive(array $data = [])
    {
        return $this->deleteJson('/api/vehiculos/'.$this->vehiculo->id_vehiculo, $data + ['confirmar_eliminacion' => true]);
    }

    public function test_archive_preserves_records_costs_and_cancels_only_active_jobs(): void
    {
        $job = Mantenimiento::firstOrFail();
        $job->update(['estado' => 'Pendiente', 'costo_mano_obra' => 120.50]);
        foreach (['En Proceso', 'Completado', 'Cancelado'] as $estado) {
            $copy = $job->replicate();
            $copy->estado = $estado;
            $copy->save();
        }
        $invoices = DB::table('facturas')->get()->toJson();
        $parts = DB::table('repuestos')->get()->toJson();

        $this->archive()->assertOk();

        $this->assertDatabaseCount('vehiculos', 1);
        $this->assertSoftDeleted('vehiculos', ['id_vehiculo' => $this->vehiculo->id_vehiculo]);
        $this->assertDatabaseCount('mantenimientos', 4);
        $this->assertSame(['Cancelado', 'Cancelado', 'Completado', 'Cancelado'], Mantenimiento::orderBy('id_mantenimiento')->pluck('estado')->all());
        $this->assertEquals(482, Mantenimiento::sum('costo_mano_obra'));
        $this->assertSame($invoices, DB::table('facturas')->get()->toJson());
        $this->assertSame($parts, DB::table('repuestos')->get()->toJson());
        $this->assertDatabaseHas('historial_cambios', [
            'tipo_evento' => 'VEHICULO_ARCHIVADO',
            'id_usuario' => Usuario::where('username', 'admin')->value('id_usuario'),
        ]);
        $this->assertStringContainsString($this->vehiculo->placa, HistorialCambio::where('tipo_evento', 'VEHICULO_ARCHIVADO')->firstOrFail()->descripcion_evento);
    }

    public function test_archive_without_jobs_keeps_the_vehicle(): void
    {
        $this->vehiculo = Vehiculo::create(['placa' => 'P999-999', 'marca' => 'Test', 'modelo' => 'Test', 'anio' => 2025]);
        $this->archive()->assertOk();
        $this->assertSoftDeleted('vehiculos', ['id_vehiculo' => $this->vehiculo->id_vehiculo]);
    }

    public function test_preview_never_writes_even_when_confirmation_is_sent(): void
    {
        Mantenimiento::query()->update(['estado' => 'Pendiente']);
        $before = DB::table('mantenimientos')->get()->toJson();
        $this->archive(['solo_validar' => true, 'cancelar_pendientes' => true])->assertOk();
        $this->assertNotNull(Vehiculo::find($this->vehiculo->id_vehiculo));
        $this->assertSame($before, DB::table('mantenimientos')->get()->toJson());
        $this->assertDatabaseMissing('historial_cambios', ['tipo_evento' => 'VEHICULO_ARCHIVADO']);
    }

    public function test_unconfirmed_archive_does_not_write(): void
    {
        $this->deleteJson('/api/vehiculos/'.$this->vehiculo->id_vehiculo)->assertStatus(409);
        $this->assertNotNull(Vehiculo::find($this->vehiculo->id_vehiculo));
        $this->assertDatabaseCount('mantenimientos', 1);
    }

    public function test_audit_failure_rolls_back_cancellation_and_archive(): void
    {
        Mantenimiento::query()->update(['estado' => 'Pendiente']);
        DB::statement("CREATE TRIGGER reject_archive_audit BEFORE INSERT ON historial_cambios WHEN NEW.tipo_evento = 'VEHICULO_ARCHIVADO' BEGIN SELECT RAISE(ABORT, 'audit unavailable'); END");
        $this->archive(['cancelar_pendientes' => true])->assertStatus(500);
        $this->assertNotNull(Vehiculo::find($this->vehiculo->id_vehiculo));
        $this->assertDatabaseHas('mantenimientos', ['estado' => 'Pendiente']);
        $this->assertDatabaseCount('facturas', 1);
        $this->assertDatabaseCount('repuestos', 1);
    }

    public function test_archived_vehicle_keeps_history_reports_relations_and_totals(): void
    {
        Mantenimiento::query()->update(['costo_mano_obra' => 120.50]);
        DB::table('repuestos')->update(['costo_unitario' => 30.25, 'id_mantenimiento' => Mantenimiento::firstOrFail()->id_mantenimiento]);
        $this->archive()->assertOk();

        $this->getJson('/api/historial-vehiculo?id_vehiculo='.$this->vehiculo->id_vehiculo)
            ->assertOk()->assertJsonPath('vehiculo.placa', $this->vehiculo->placa)
            ->assertJsonPath('resumen.costo_total', 150.75)
            ->assertJsonCount(1, 'vehiculos')->assertJsonCount(1, 'mantenimientos');
        $this->getJson('/api/reportes/placa?placa='.urlencode($this->vehiculo->placa))
            ->assertOk()->assertJsonPath('vehiculo.placa', $this->vehiculo->placa);
        $this->getJson('/api/vehiculos/'.$this->vehiculo->id_vehiculo)->assertOk();
        $this->getJson('/api/mantenimientos')->assertOk()
            ->assertJsonPath('mantenimientos.0.vehiculo.placa', $this->vehiculo->placa)
            ->assertJsonCount(0, 'vehiculos');
        $this->getJson('/api/repuestos')->assertOk()
            ->assertJsonPath('repuestos.0.factura.vehiculo.placa', $this->vehiculo->placa);
        $this->getJson('/api/estadisticas/mantenimientos')->assertOk()
            ->assertJsonPath('total_mantenimientos', 1)
            ->assertJsonPath('vehiculos_con_mas_servicios.0.placa', $this->vehiculo->placa);
        $this->getJson('/api/vehiculos')->assertOk()->assertExactJson([]);
        $this->getJson('/api/vehiculos-buscar?termino='.urlencode($this->vehiculo->marca))->assertOk()->assertExactJson([]);
    }

    public function test_archived_vehicle_cannot_receive_new_jobs_or_reactivate_existing_ones(): void
    {
        $job = Mantenimiento::firstOrFail();
        $data = $job->only(['fecha_servicio', 'descripcion_falla', 'costo_mano_obra', 'id_vehiculo', 'id_usuario_encargado']);
        $data['estado'] = 'Pendiente';
        $this->archive()->assertOk();
        $this->postJson('/api/mantenimientos', $data)->assertUnprocessable()->assertJsonValidationErrors('id_vehiculo');
        $this->putJson('/api/mantenimientos/'.$job->id_mantenimiento, $data)->assertUnprocessable();
        $active = Vehiculo::create(['placa' => 'P999-998', 'marca' => 'Test', 'modelo' => 'Test', 'anio' => 2025]);
        $data['id_vehiculo'] = $active->id_vehiculo;
        $this->putJson('/api/mantenimientos/'.$job->id_mantenimiento, $data)->assertUnprocessable();
        $this->assertDatabaseCount('mantenimientos', 1);
    }

    public function test_archived_vehicle_cannot_receive_new_invoice_uploads(): void
    {
        Storage::fake('public');
        $this->archive()->assertOk();
        $this->postJson('/api/repuestos/cargar-pdf', [
            'id_vehiculo' => $this->vehiculo->id_vehiculo,
            'comprobante_pdf' => UploadedFile::fake()->createWithContent('factura.pdf', "%PDF-1.4\n(FAC-999)\n%%EOF"),
            'cantidad_recibida' => 1,
            'costo_unitario' => 25,
        ])->assertUnprocessable();
        $this->assertDatabaseCount('facturas', 1);
        $this->assertDatabaseCount('repuestos', 1);
    }

    public function test_cancel_endpoint_cannot_change_completed_history_after_archive(): void
    {
        $job = Mantenimiento::firstOrFail();
        $job->update(['estado' => 'Completado']);
        $this->archive()->assertOk();
        $this->deleteJson('/api/mantenimientos/'.$job->id_mantenimiento)->assertUnprocessable();
        $this->assertDatabaseHas('mantenimientos', ['id_mantenimiento' => $job->id_mantenimiento, 'estado' => 'Completado']);
    }

    public function test_vehicle_edit_rolls_back_if_owner_update_fails(): void
    {
        DB::statement("CREATE TRIGGER reject_owner_update BEFORE UPDATE ON propietarios BEGIN SELECT RAISE(ABORT, 'owner unavailable'); END");
        $this->putJson('/api/vehiculos/'.$this->vehiculo->id_vehiculo, [
            'placa' => 'P999-123', 'marca' => 'Cambio', 'modelo' => 'Cambio',
            'anio' => 2025, 'propietario' => 'Cambio',
        ])->assertStatus(500);
        $this->assertDatabaseHas('vehiculos', ['id_vehiculo' => $this->vehiculo->id_vehiculo, 'placa' => $this->vehiculo->placa]);
    }

    public function test_job_cannot_be_moved_using_a_stale_vehicle_association(): void
    {
        $job = Mantenimiento::firstOrFail();
        $other = Vehiculo::create(['placa' => 'P999-997', 'marca' => 'Test', 'modelo' => 'Test', 'anio' => 2025]);
        $target = Vehiculo::create(['placa' => 'P999-996', 'marca' => 'Test', 'modelo' => 'Test', 'anio' => 2025]);
        // Simula un cambio confirmado por otra petición tras la primera lectura,
        // antes de que esta petición adquiera los bloqueos del vehículo.
        $changed = false;
        Mantenimiento::retrieved(function ($model) use (&$changed, $other) {
            if ($changed) {
                return;
            }
            $changed = true;
            DB::table('mantenimientos')->where('id_mantenimiento', $model->id_mantenimiento)
                ->update(['id_vehiculo' => $other->id_vehiculo, 'estado' => 'Cancelado']);
            DB::table('vehiculos')->where('id_vehiculo', $other->id_vehiculo)->update(['deleted_at' => now()]);
        });
        try {
            $this->putJson('/api/mantenimientos/'.$job->id_mantenimiento, [
                'fecha_servicio' => '2026-10-29', 'descripcion_falla' => 'Cambio',
                'estado' => 'Pendiente', 'costo_mano_obra' => 999,
                'id_vehiculo' => $target->id_vehiculo, 'id_usuario_encargado' => $job->id_usuario_encargado,
            ])->assertUnprocessable();
        } finally {
            Mantenimiento::flushEventListeners();
        }
    }
}
