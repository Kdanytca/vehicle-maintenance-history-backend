<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClienteController extends Controller
{
    /**
     * Obtener todos los clientes registrados.
     */
    public function index(): JsonResponse
    {
        $clientes = Cliente::with('vehiculos')
            ->orderBy('nombre')
            ->orderBy('apellido')
            ->get();

        return response()->json($clientes);
    }

    /**
     * Registrar un cliente en la base de datos.
     */
    public function store(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'nombre' => 'required|string|max:255',
            'apellido' => 'required|string|max:255',
            'dui' => 'required|string|max:10|unique:clientes,dui',
            'telefono' => 'required|string|max:15',
            'correo_electronico' => 'required|email|max:255|unique:clientes,correo_electronico',
        ]);

        $cliente = Cliente::create($datos);

        return response()->json([
            'success' => true,
            'message' => 'Cliente registrado correctamente.',
            'cliente' => $cliente
        ], 201);
    }

    /**
     * Actualizar un cliente.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $cliente = Cliente::findOrFail($id);

        $datos = $request->validate([
            'nombre' => 'required|string|max:255',
            'apellido' => 'required|string|max:255',
            'dui' => [
                'required',
                'string',
                'max:10',
                Rule::unique('clientes', 'dui')->ignore($cliente->id),
            ],
            'telefono' => 'required|string|max:15',
            'correo_electronico' => [
                'required',
                'email',
                'max:255',
                Rule::unique('clientes', 'correo_electronico')->ignore($cliente->id),
            ],
        ]);

        $cliente->update($datos);

        return response()->json([
            'success' => true,
            'message' => 'Cliente actualizado correctamente.',
            'cliente' => $cliente
        ]);
    }

    /**
     * Eliminar un cliente.
     */
    public function destroy(int $id): JsonResponse
    {
        $cliente = Cliente::findOrFail($id);
        $cliente->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cliente eliminado correctamente.'
        ]);
    }
}