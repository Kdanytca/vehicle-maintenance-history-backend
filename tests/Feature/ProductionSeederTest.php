<?php

namespace Tests\Feature;

use App\Models\Permiso;
use App\Models\Rol;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\ProductionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProductionSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_all_seed_data_without_duplicates_on_repeated_runs(): void
    {
        $this->seed(ProductionSeeder::class);
        $this->seed(ProductionSeeder::class);

        $this->assertDatabaseCount('roles', 2);
        $this->assertDatabaseCount('permisos', 5);
        $this->assertDatabaseCount('roles_permisos', 8);
        $this->assertDatabaseCount('usuarios', 3);
        $this->assertDatabaseHas('usuarios', ['username' => 'admin', 'estado_activo' => true]);
        $this->assertDatabaseHas('usuarios', ['username' => 'mecanico', 'estado_activo' => true]);
        $this->assertDatabaseHas('usuarios', ['username' => 'suspendido', 'estado_activo' => false]);
        foreach (['propietarios', 'vehiculos', 'mantenimientos', 'facturas', 'repuestos', 'notificaciones', 'historial_cambios'] as $table) {
            $this->assertDatabaseCount($table, 1);
        }
    }

    public function test_it_adds_missing_data_without_overwriting_existing_data(): void
    {
        $this->seed(DatabaseSeeder::class);
        DB::table('vehiculos')->update(['marca' => 'Marca editada']);
        DB::table('mantenimientos')->update(['estado' => 'Finalizado', 'costo_mano_obra' => 120]);
        DB::table('usuarios')->where('username', 'admin')->update([
            'password_hash' => 'existing-custom-password-hash',
            'estado_activo' => false,
        ]);
        $admin = Rol::where('nombre_rol', 'admin')->firstOrFail();
        $admin->update(['descripcion' => 'Descripcion personalizada']);
        $extra = Permiso::create(['nombre_permiso' => 'permiso_personalizado', 'descripcion' => 'Extra']);
        $admin->permisos()->attach($extra);
        $permiso = Permiso::where('nombre_permiso', 'gestionar_vehiculos')->firstOrFail();
        $permiso->update(['descripcion' => 'Permiso personalizado']);
        Permiso::where('nombre_permiso', 'gestionar_repuestos')->delete();

        $tables = ['usuarios', 'propietarios', 'vehiculos', 'mantenimientos', 'facturas', 'repuestos', 'notificaciones', 'historial_cambios'];
        $before = [];
        foreach ($tables as $table) {
            $before[$table] = DB::table($table)->get()->toJson();
        }

        $this->seed(ProductionSeeder::class);
        $this->seed(ProductionSeeder::class);

        foreach ($tables as $table) {
            $this->assertSame($before[$table], DB::table($table)->get()->toJson(), $table);
        }
        $this->assertSame('Descripcion personalizada', $admin->fresh()->descripcion);
        $this->assertSame('Permiso personalizado', $permiso->fresh()->descripcion);
        $this->assertTrue($admin->permisos()->where('permisos.id_permiso', $extra->id_permiso)->exists());
        $this->assertDatabaseCount('permisos', 6);
        $this->assertDatabaseCount('roles_permisos', 9);
    }

    public function test_it_preserves_the_history_entry_created_by_neon_sql(): void
    {
        $this->seed(DatabaseSeeder::class);
        DB::table('historial_cambios')->update([
            'descripcion_evento' => 'Datos de demostracion cargados para el vehiculo P-123456 desde Neon SQL.',
        ]);

        $this->seed(ProductionSeeder::class);
        $this->seed(ProductionSeeder::class);

        $this->assertDatabaseCount('historial_cambios', 1);
        $this->assertDatabaseHas('historial_cambios', [
            'descripcion_evento' => 'Datos de demostracion cargados para el vehiculo P-123456 desde Neon SQL.',
        ]);
    }
}
