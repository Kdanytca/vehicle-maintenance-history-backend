<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Mantenimiento;
use App\Models\Usuario;
use App\Models\Vehiculo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MantenimientoController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'mantenimientos' => Mantenimiento::with(['vehiculo', 'encargado'])->latest('id_mantenimiento')->get(),
            'vehiculos' => Vehiculo::all(),
            'usuarios' => Usuario::with('rol')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $mantenimiento = Mantenimiento::create($this->validated($request));

        return response()->json($mantenimiento->load(['vehiculo', 'encargado']), 201);
    }

    public function update(Request $request, int $mantenimiento): JsonResponse
    {
        $mantenimiento = Mantenimiento::findOrFail($mantenimiento);
        $mantenimiento->update($this->validated($request));

        return response()->json($mantenimiento->load(['vehiculo', 'encargado']));
    }

    public function destroy(int $mantenimiento): JsonResponse
    {
        $mantenimiento = Mantenimiento::findOrFail($mantenimiento);
        $mantenimiento->update(['estado' => 'Cancelado']);

        return response()->json($mantenimiento);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'fecha_servicio' => 'required|date',
            'descripcion_falla' => 'required|string',
            'estado' => 'required|string',
            'costo_mano_obra' => 'nullable|numeric',
            'id_vehiculo' => 'required|exists:vehiculos,id_vehiculo',
            'id_usuario_encargado' => 'required|exists:usuarios,id_usuario',
        ]);
    }
}
