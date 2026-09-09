<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use App\Models\Mantenimiento;
use App\Models\Vehiculo;
use Illuminate\Http\Request;

class MantenimientoController extends Controller
{
    public function index()
    {
        $mantenimientos = Mantenimiento::with(['vehiculo', 'encargado'])->get();
        return view('mantenimientos.index', compact('mantenimientos'));
    }

    public function create()
    {
        $usuarios = Usuario::all();
        $usuarioActivoId = auth()->id();

        $vehiculos = Vehiculo::all();

        return view('mantenimientos.create', compact('usuarios', 'usuarioActivoId', 'vehiculos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'fecha_servicio' => 'required|date',
            'descripcion_falla' => 'required|string',
            'estado' => 'required|string',
            'costo_mano_obra' => 'nullable|numeric',
            'id_vehiculo' => 'required|exists:vehiculos,id_vehiculo',
            'id_usuario_encargado' => 'required|exists:usuarios,id_usuario' // Validar que el encargado exista
        ]);

        Mantenimiento::create($request->all());

        return redirect()->route('mantenimientos.index')
            ->with('success', 'Mantenimiento registrado exitosamente.');
    }

    // Mostrar formulario de editar
    public function edit($id)
    {
        $mantenimiento = Mantenimiento::findOrFail($id);
        $vehiculos = Vehiculo::all();

        $usuarios = Usuario::all();
        $usuarioActivoId = $mantenimiento->id_usuario_encargado;

        return view('mantenimientos.edit', compact('mantenimiento', 'vehiculos', 'usuarios', 'usuarioActivoId'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'fecha_servicio' => 'required|date',
            'descripcion_falla' => 'required|string',
            'estado' => 'required|string',
            'costo_mano_obra' => 'nullable|numeric',
            'id_vehiculo' => 'required|exists:vehiculos,id_vehiculo',
            'id_usuario_encargado' => 'required|exists:usuarios,id_usuario' // Agregado
        ]);

        $mantenimiento = Mantenimiento::findOrFail($id);
        $mantenimiento->update($request->all());

        return redirect()->route('mantenimientos.index')
            ->with('success', 'Mantenimiento actualizado exitosamente.');
    }

    public function destroy($id)
    {
        $mantenimiento = Mantenimiento::findOrFail($id);

        $mantenimiento->update(['estado' => 'Cancelado']);

        return redirect()->route('mantenimientos.index')
            ->with('success', 'Mantenimiento cancelado exitosamente.');
    }
}
