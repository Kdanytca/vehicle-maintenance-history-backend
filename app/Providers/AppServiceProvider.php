<?php

namespace App\Providers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Audita automáticamente las escrituras de los modelos propios de la aplicación.
        Event::listen('eloquent.created: *', function (string $event, array $models): void {
            if (($models[0] ?? null) instanceof Model) {
                $this->registrarCambio($models[0], 'crear', 'Registro creado');
            }
        });

        Event::listen('eloquent.updated: *', function (string $event, array $models): void {
            if (! ($models[0] ?? null) instanceof Model) {
                return;
            }

            $model = $models[0];
            $campos = array_diff(array_keys($model->getChanges()), ['updated_at']);
            $descripcion = 'Registro actualizado';

            if ($campos !== []) {
                $descripcion .= '. Campos modificados: '.implode(', ', $campos);
            }

            $this->registrarCambio($model, 'editar', $descripcion);
        });

        Event::listen('eloquent.deleted: *', function (string $event, array $models): void {
            if (($models[0] ?? null) instanceof Model) {
                $this->registrarCambio($models[0], 'eliminar', 'Registro eliminado');
            }
        });
    }

    private function registrarCambio(Model $model, string $accion, string $descripcion): void
    {
        // Evita registrar la propia auditoría y no genera entradas sin usuario autenticado.
        if ($model instanceof AuditLog || ! Auth::check()) {
            return;
        }

        $modulo = class_basename($model);
        $identificador = $model->getKey();

        AuditLog::registrar(
            $accion,
            $modulo,
            "{$descripcion}: {$modulo} #{$identificador}."
        );
    }
}
