<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Mantenimiento;
use App\Models\Propietario;
use App\Models\Vehiculo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VehiculoController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Vehiculo::with('propietario')->latest('id_vehiculo')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'placa' => 'required|unique:vehiculos,placa',
            'marca' => 'required|string|max:100',
            'modelo' => 'required|string|max:100',
            'anio' => 'required|integer|min:1900|max:' . (now()->year + 1),
            'propietario' => 'required|string|max:150',
        ]);

        $propietario = Propietario::create([
            'nombre' => $data['propietario'],
            'documento_identidad' => 'N/A',
            'telefono' => 'N/A',
            'correo' => 'N/A',
        ]);

        $vehiculo = Vehiculo::create([
            'placa' => $data['placa'],
            'marca' => $data['marca'],
            'modelo' => $data['modelo'],
            'anio' => $data['anio'],
            'id_propietario' => $propietario->id_propietario,
        ]);

        return response()->json($vehiculo->load('propietario'), 201);
    }

    public function show(int $vehiculo): JsonResponse
    {
        $vehiculo = Vehiculo::with('propietario')->findOrFail($vehiculo);
        $mantenimientos = Mantenimiento::where('id_vehiculo', $vehiculo->id_vehiculo)->orderByDesc('fecha_servicio')->get();

        return response()->json(compact('vehiculo', 'mantenimientos'));
    }

    public function update(Request $request, int $vehiculo): JsonResponse
    {
        $vehiculo = Vehiculo::with('propietario')->findOrFail($vehiculo);

        $data = $request->validate([
            'placa' => 'required|unique:vehiculos,placa,' . $vehiculo->id_vehiculo . ',id_vehiculo',
            'marca' => 'required|string|max:100',
            'modelo' => 'required|string|max:100',
            'anio' => 'required|integer|min:1900|max:' . (now()->year + 1),
            'propietario' => 'required|string|max:150',
        ]);

        $vehiculo->update($request->only('placa', 'marca', 'modelo', 'anio'));
        $vehiculo->propietario?->update(['nombre' => $data['propietario']]);

        return response()->json($vehiculo->refresh()->load('propietario'));
    }

    public function buscar(Request $request): JsonResponse
    {
        $data = $request->validate(['termino' => 'required|string']);

        $termino = strtolower($data['termino']);

        $vehiculos = Vehiculo::with('propietario')
            ->whereRaw('LOWER(placa) LIKE ?', ['%' . $termino . '%'])
            ->orWhereHas('propietario', fn ($query) => $query->whereRaw('LOWER(nombre) LIKE ?', ['%' . $termino . '%']))
            ->get();

        return response()->json($vehiculos);
    }

    public function reportePorPlaca(Request $request): JsonResponse
    {
        $data = $request->validate(['placa' => 'required|string']);
        $vehiculo = Vehiculo::with('propietario')->where('placa', trim($data['placa']))->first();

        if (! $vehiculo) {
            return response()->json(['message' => 'La placa ingresada no existe.'], 404);
        }

        $mantenimientos = Mantenimiento::with('encargado')->where('id_vehiculo', $vehiculo->id_vehiculo)->orderBy('fecha_servicio')->get();

        if ($mantenimientos->isEmpty()) {
            return response()->json(['message' => 'No existen mantenimientos para esta placa.', 'vehiculo' => $vehiculo], 404);
        }

        return response()->json(compact('vehiculo', 'mantenimientos'));
    }
}
