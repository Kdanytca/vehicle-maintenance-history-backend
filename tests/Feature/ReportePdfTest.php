<?php

namespace Tests\Feature;

use App\Models\Mantenimiento;
use App\Models\Usuario;
use App\Models\Vehiculo;
use App\Services\ReporteVehiculo;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReportePdfTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        Sanctum::actingAs(Usuario::where('username', 'admin')->firstOrFail());
    }

    public function test_exports_real_pdf_for_filtered_and_archived_vehicle(): void
    {
        $vehiculo = Vehiculo::firstOrFail();
        $vehiculo->delete();
        $response = $this->getJson('/api/reportes/placa/pdf?placa='.urlencode(' '.$vehiculo->placa.' '))
            ->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertStringContainsString('attachment;', $response->headers->get('content-disposition'));
        $this->assertGreaterThan(1000, strlen($response->getContent()));
    }

    public function test_template_contains_required_fields_and_only_selected_vehicle(): void
    {
        $vehiculo = Vehiculo::firstOrFail();
        $job = Mantenimiento::firstOrFail();
        $job->update(['descripcion_falla' => 'Revisión de frenos <script>alert(1)</script>']);
        $otro = Vehiculo::create(['placa' => 'OTRO-999', 'marca' => 'Otra', 'modelo' => 'Marca', 'anio' => 2025]);
        $copy = $job->replicate();
        $copy->id_vehiculo = $otro->id_vehiculo;
        $copy->descripcion_falla = 'NO INCLUIR ESTE MANTENIMIENTO';
        $copy->save();
        $data = app(ReporteVehiculo::class)->porPlaca($vehiculo->placa);
        $html = view('reportes.vehiculo-pdf', $data + ['generadoEn' => now()])->render();
        foreach ([$vehiculo->placa, $vehiculo->propietario->nombre, $job->fecha_servicio, $job->encargado->username, 'Revisión de frenos', 'Mecánico responsable'] as $text) {
            $this->assertStringContainsString($text, $html);
        }
        $this->assertStringNotContainsString('NO INCLUIR ESTE MANTENIMIENTO', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertCount(1, $data['mantenimientos']);
    }

    public function test_rejects_empty_unknown_and_missing_filters(): void
    {
        $vehiculo = Vehiculo::create(['placa' => 'VACIO-99', 'marca' => 'Test', 'modelo' => 'Test', 'anio' => 2025]);
        $this->getJson('/api/reportes/placa/pdf?placa='.$vehiculo->placa)->assertNotFound()->assertJsonPath('message', 'No existen mantenimientos para esta placa.');
        $this->getJson('/api/reportes/placa/pdf?placa=INEXISTENTE')->assertNotFound();
        $this->getJson('/api/reportes/placa/pdf')->assertUnprocessable();
    }

    public function test_export_requires_administrator(): void
    {
        Sanctum::actingAs(Usuario::whereHas('rol', fn ($q) => $q->where('nombre_rol', 'mecanico'))->firstOrFail());
        $this->getJson('/api/reportes/placa/pdf?placa=TEST')->assertForbidden();
        $this->app['auth']->forgetGuards();
        $this->getJson('/api/reportes/placa/pdf?placa=TEST')->assertUnauthorized();
    }
}
