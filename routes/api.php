<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\HistorialVehiculoController;
use App\Http\Controllers\Api\MantenimientoController;
use App\Http\Controllers\Api\RepuestoController;
use App\Http\Controllers\Api\RolPermisoController;
use App\Http\Controllers\Api\UsuarioController;
use App\Http\Controllers\Api\VehiculoController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::apiResource('vehiculos', VehiculoController::class)->except(['destroy']);
    Route::get('/vehiculos-buscar', [VehiculoController::class, 'buscar']);
    Route::get('/reportes/placa', [VehiculoController::class, 'reportePorPlaca']);

    Route::apiResource('mantenimientos', MantenimientoController::class)->except(['show']);
    Route::get('/historial-vehiculo', HistorialVehiculoController::class);

    Route::get('/repuestos', [RepuestoController::class, 'index']);
    Route::post('/repuestos/cargar-pdf', [RepuestoController::class, 'storeFromPdf']);
    Route::get('/alertas', [RepuestoController::class, 'alertas']);
    Route::post('/notificaciones/enviar', [RepuestoController::class, 'enviarNotificacion']);

    Route::middleware('solo.admin')->group(function () {
        Route::apiResource('usuarios', UsuarioController::class)->except(['show']);
        Route::get('/roles-permisos', [RolPermisoController::class, 'index']);
        Route::post('/roles', [RolPermisoController::class, 'storeRol']);
        Route::delete('/roles/{id}', [RolPermisoController::class, 'destroyRol']);
        Route::post('/permisos', [RolPermisoController::class, 'storePermiso']);
        Route::delete('/permisos/{id}', [RolPermisoController::class, 'destroyPermiso']);
        Route::put('/roles/{id}/permisos', [RolPermisoController::class, 'asignarPermisos']);
    });
});
