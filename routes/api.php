<?php

use App\Http\Controllers\Api\AlertaGarantiaController;
use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClienteController;
use App\Http\Controllers\Api\ConfiguracionAlertaGarantiaController;
use App\Http\Controllers\Api\EstadisticaController;
use App\Http\Controllers\Api\HistorialVehiculoController;
use App\Http\Controllers\Api\MantenimientoController;
use App\Http\Controllers\Api\NotificacionController;
use App\Http\Controllers\Api\RecepcionController;
use App\Http\Controllers\Api\ReportePdfController;
use App\Http\Controllers\Api\RepuestoController;
use App\Http\Controllers\Api\RolPermisoController;
use App\Http\Controllers\Api\UsuarioController;
use App\Http\Controllers\Api\VehiculoController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Gestión de clientes
    Route::apiResource('clientes', ClienteController::class)
        ->only(['index', 'store', 'update', 'destroy']);

    // Vehículos
    Route::apiResource('vehiculos', VehiculoController::class);
    Route::get('/vehiculos-buscar', [VehiculoController::class, 'buscar']);

    // G2DS-16: recepción del vehículo
    Route::get('/vehiculos/{vehiculo}/recepciones', [RecepcionController::class, 'index']);
    Route::post('/vehiculos/{vehiculo}/recepciones', [RecepcionController::class, 'store']);
    Route::put('/vehiculos/{vehiculo}/recepciones/{recepcion}', [RecepcionController::class, 'update'])
        ->middleware('solo.admin');

    Route::get('/reportes/placa', [VehiculoController::class, 'reportePorPlaca']);
    Route::get('/reportes/placa/pdf', ReportePdfController::class)
        ->middleware('solo.admin');

    // Mantenimientos e historial
    Route::apiResource('mantenimientos', MantenimientoController::class)
        ->except(['show']);

    Route::get('/historial-vehiculo', HistorialVehiculoController::class);

    // Estadísticas
    Route::get('/estadisticas/mantenimientos', [EstadisticaController::class, 'index']);

    // Repuestos y alertas
    Route::get('/repuestos', [RepuestoController::class, 'index']);
    Route::post('/repuestos/cargar-pdf', [RepuestoController::class, 'storeFromPdf']);
    Route::get('/alertas', [RepuestoController::class, 'alertas']);

    // Alertas de garantía
    Route::get('/alertas-garantia', [AlertaGarantiaController::class, 'index']);

    Route::patch(
        '/alertas-garantia/{alerta}/atender',
        [AlertaGarantiaController::class, 'atender']
    )->middleware('solo.admin');

    // Recordatorios manuales por correo
    Route::get('/clientes-notificacion', [NotificacionController::class, 'clientes']);
    Route::post('/notificaciones/enviar', [NotificacionController::class, 'enviar']);

    // Administración
    Route::middleware('solo.admin')->group(function () {

        Route::get(
            '/configuracion-alertas-garantia',
            [ConfiguracionAlertaGarantiaController::class, 'show']
        );

        Route::put(
            '/configuracion-alertas-garantia',
            [ConfiguracionAlertaGarantiaController::class, 'update']
        );

        Route::get('/audit-logs', [AuditLogController::class, 'index']);

        Route::apiResource('usuarios', UsuarioController::class)
            ->except(['show']);

        // Desbloqueo manual de usuarios
        Route::patch(
            '/usuarios/{usuario}/desbloquear',
            [UsuarioController::class, 'desbloquear']
        );

        Route::get('/roles-permisos', [RolPermisoController::class, 'index']);
        Route::post('/roles', [RolPermisoController::class, 'storeRol']);
        Route::delete('/roles/{id}', [RolPermisoController::class, 'destroyRol']);
        Route::post('/permisos', [RolPermisoController::class, 'storePermiso']);
        Route::delete('/permisos/{id}', [RolPermisoController::class, 'destroyPermiso']);

        Route::put(
            '/roles/{id}/permisos',
            [RolPermisoController::class, 'asignarPermisos']
        );
    });
});