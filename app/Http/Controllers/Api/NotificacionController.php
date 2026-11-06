<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Notificacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class NotificacionController extends Controller
{
    /**
     * Obtener los clientes registrados para seleccionar
     * el destinatario de la notificación.
     */
    public function clientes(): JsonResponse
    {
        $clientes = Cliente::with('vehiculos')
            ->whereNotNull('correo_electronico')
            ->where('correo_electronico', '!=', '')
            ->orderBy('nombre')
            ->orderBy('apellido')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $clientes
        ]);
    }

    /**
     * Enviar un recordatorio manual por correo electrónico.
     */
    public function enviar(Request $request): JsonResponse
    {
        // Comprobar que el usuario tenga un rol autorizado.
        $usuario = $request->user();
        $rol = $usuario->rol?->nombre_rol;

        if (!in_array($rol, ['admin', 'mecanico'])) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para enviar notificaciones.'
            ], 403);
        }

        // Validar los datos recibidos.
        $datos = $request->validate([
            'id_cliente' => [
                'required',
                'integer',
                'exists:clientes,id'
            ],
            'asunto' => [
                'required',
                'string',
                'max:255'
            ],
            'mensaje' => [
                'required',
                'string'
            ]
        ]);

        // Buscar el cliente seleccionado.
        $cliente = Cliente::findOrFail($datos['id_cliente']);

        // Comprobar que tenga correo registrado.
        if (
            empty($cliente->correo_electronico) ||
            !filter_var($cliente->correo_electronico, FILTER_VALIDATE_EMAIL)
        ) {
            return response()->json([
                'success' => false,
                'message' => 'El cliente no tiene un correo electrónico válido.'
            ], 422);
        }

        try {
            // Enviar el correo electrónico.
            Mail::raw($datos['mensaje'], function ($correo) use ($cliente, $datos) {
                $correo->to($cliente->correo_electronico)
                    ->subject($datos['asunto']);
            });

            // Guardar el registro de la notificación enviada.
            $notificacion = Notificacion::create([
                'destinatario' => $cliente->correo_electronico,
                'asunto' => $datos['asunto'],
                'mensaje' => $datos['mensaje'],
                'tipo_envio' => 'MANUAL',
                'fecha_envio' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Notificación enviada correctamente.',
                'data' => $notificacion
            ], 201);

        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'No se pudo enviar la notificación.',
                'error' => config('app.debug')
                    ? $e->getMessage()
                    : null
            ], 500);
        }
    }
}