@extends('layouts.app')

@section('title', 'Listado de Vehículos - SGA')

@section('content')

    <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <p class="text-slate-400 text-sm">Listado de todos los vehículos registrados en el taller.</p>
        </div>

        <a href="{{ route('vehiculos.create') }}"
            class="bg-sky-500 hover:bg-sky-600 transition text-slate-900 px-4 py-2 rounded-lg font-semibold text-sm flex items-center gap-2 shadow-lg shadow-sky-500/10">
            <i class="fa-solid fa-plus text-xs"></i> Nuevo Vehículo
        </a>
    </div>

    <div class="bg-[#1e293b] rounded-xl border border-slate-800 overflow-hidden shadow-xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="bg-slate-900/50 border-b border-slate-800">
                        <th class="px-6 py-3 text-slate-400 font-semibold text-xs uppercase tracking-wider">Placa</th>
                        <th class="px-6 py-3 text-slate-400 font-semibold text-xs uppercase tracking-wider">Marca</th>
                        <th class="px-6 py-3 text-slate-400 font-semibold text-xs uppercase tracking-wider">Modelo</th>
                        <th class="px-6 py-3 text-slate-400 font-semibold text-xs uppercase tracking-wider">Año</th>
                        <th class="px-6 py-3 text-slate-400 font-semibold text-xs uppercase tracking-wider">Propietario</th>
                        <th class="px-6 py-3 text-slate-400 font-semibold text-xs uppercase tracking-wider text-right">
                            Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($vehiculos as $vehiculo)
                        <tr class="hover:bg-slate-800/30 transition-colors">
                            <td class="px-6 py-4">
                                <span
                                    class="bg-slate-900 text-sky-400 font-mono text-xs px-2.5 py-1 rounded border border-slate-800">
                                    {{ $vehiculo->placa }}
                                </span>
                            </td>
                            <td class="px-6 py-4 font-medium text-white">{{ $vehiculo->marca }}</td>
                            <td class="px-6 py-4 text-slate-300">{{ $vehiculo->modelo }}</td>
                            <td class="px-6 py-4 font-mono text-slate-400 text-xs">{{ $vehiculo->anio }}</td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <i class="fa-solid fa-user text-xs text-slate-500"></i>
                                    <span class="text-slate-300">
                                        {{ $vehiculo->propietario->nombre ?? 'Sin propietario' }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right space-x-2 whitespace-nowrap">
                                <a href="{{ route('vehiculos.show', $vehiculo->id_vehiculo ?? $vehiculo->id) }}"
                                    class="inline-block px-2.5 py-1 bg-slate-800 hover:bg-sky-500/20 text-sky-400 rounded text-xs transition">
                                    <i class="fa-solid fa-eye"></i> Ver
                                </a>
                                <a href="{{ route('vehiculos.edit', $vehiculo->id_vehiculo ?? $vehiculo->id) }}"
                                    class="inline-block px-2.5 py-1 bg-slate-800 hover:bg-amber-500/20 text-amber-400 rounded text-xs transition">
                                    <i class="fa-solid fa-pen"></i> Editar
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                                <i class="fa-solid fa-car-burst text-3xl mb-3 block text-slate-600"></i>
                                No hay vehículos registrados en el sistema.
                            </td>
                        </tr>
                    @endempty
            </tbody>
        </table>
    </div>
</div>

@if (method_exists($vehiculos, 'hasPages') && $vehiculos->hasPages())
    <div class="px-6 py-3 border-t border-slate-800 bg-slate-900/20">
        {{ $vehiculos->links() }}
    </div>
@endif

@endsection
