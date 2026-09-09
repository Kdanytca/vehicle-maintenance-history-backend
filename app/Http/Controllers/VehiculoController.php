<?php

namespace App\Http\Controllers;

use App\Models\Vehiculo;
use App\Models\Propietario;
use App\Models\Mantenimiento;
use Illuminate\Http\Request;

class VehiculoController extends Controller
{
    public function index()
    {
        $vehiculos = Vehiculo::with('propietario')->get();
        return view('vehiculos.index', compact('vehiculos'));
    }

    public function create()
    {
        return view('vehiculos.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'placa' => 'required|unique:vehiculos,placa',
            'marca' => 'required',
            'modelo' => 'required',
            'anio' => 'required|numeric',
            'propietario' => 'required',
        ]);

        $propietario = Propietario::create([
            'nombre' => $request->propietario,
            'documento_identidad' => 'N/A',
            'telefono' => 'N/A',
            'correo' => 'N/A'
        ]);

        Vehiculo::create([
            'placa' => $request->placa,
            'marca' => $request->marca,
            'modelo' => $request->modelo,
            'anio' => $request->anio,
            'id_propietario' => $propietario->id_propietario
        ]);

        return redirect()
            ->route('vehiculos.index')
            ->with('success', 'Vehículo registrado correctamente.');
    }

    public function show($id)
    {
        $vehiculo = Vehiculo::with('propietario')->where('id_vehiculo', $id)->firstOrFail();

        // Opcional: Traer sus mantenimientos asociados para enriquecer la vista
        $mantenimientos = Mantenimiento::where('id_vehiculo', $id)
            ->orderBy('fecha_servicio', 'desc')
            ->get();

        return view('vehiculos.show', compact('vehiculo', 'mantenimientos'));
    }

    public function edit($id)
    {
        $vehiculo = Vehiculo::with('propietario')->where('id_vehiculo', $id)->firstOrFail();
        return view('vehiculos.edit', compact('vehiculo'));
    }

    public function update(Request $request, $id)
    {
        $vehiculo = Vehiculo::where('id_vehiculo', $id)->firstOrFail();

        $request->validate([
            'placa' => 'required|unique:vehiculos,placa,' . $vehiculo->id_vehiculo . ',id_vehiculo',
            'marca' => 'required',
            'modelo' => 'required',
            'anio' => 'required|numeric',
            'propietario' => 'required',
        ]);

        // Actualizar datos del vehículo
        $vehiculo->update([
            'placa' => $request->placa,
            'marca' => $request->marca,
            'modelo' => $request->modelo,
            'anio' => $request->anio,
        ]);

        // Actualizar el nombre del propietario asociado
        if ($vehiculo->propietario) {
            $vehiculo->propietario->update([
                'nombre' => $request->propietario
            ]);
        }

        return redirect()
            ->route('vehiculos.index')
            ->with('success', 'Vehículo actualizado correctamente.');
    }

    public function busqueda()
    {
        return view('vehiculos.busqueda');
    }

    public function buscar(Request $request)
    {
        $request->validate([
            'termino' => 'required'
        ]);

        $termino = $request->termino;

        $vehiculos = Vehiculo::where('placa', 'like', '%' . $termino . '%')
            ->orWhereIn('id_propietario', function ($query) use ($termino) {
                $query->select('id_propietario')
                    ->from('propietarios')
                    ->where('nombre', 'like', '%' . $termino . '%');
            })
            ->get();

        return view('vehiculos.busqueda', compact('vehiculos'));
    }

    public function formReporte()
    {
        return view('vehiculos.reporte');
    }

    public function reportePorPlaca(Request $request)
    {
        $request->validate([
            'placa' => 'required'
        ]);

        $placa = trim($request->placa);

        $vehiculo = Vehiculo::where('placa', $placa)->first();

        if (!$vehiculo) {
            return view('vehiculos.reporte', [
                'mensaje' => 'La placa ingresada no existe.'
            ]);
        }

        $mantenimientos = Mantenimiento::where('id_vehiculo', $vehiculo->id_vehiculo)
            ->orderBy('fecha_servicio', 'asc')
            ->get();

        if ($mantenimientos->isEmpty()) {
            return view('vehiculos.reporte', [
                'mensaje' => 'No existen mantenimientos para esta placa.'
            ]);
        }

        return view('vehiculos.reporte', compact('vehiculo', 'mantenimientos'));
    }
}
