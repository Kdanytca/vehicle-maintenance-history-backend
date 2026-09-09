@extends('layouts.app')

@section('title', 'Detalle de Vehículo - SGA')

@section('content')

    <div class="mb-6 flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-white flex items-center gap-2">
                <i class="fa-solid fa-circle-info bg-sky-500/10 text-sky-400 p-2 rounded-lg text-sm"></i>
                Ficha Técnica del Vehículo
            </h2>
            <p class="text-slate-400 text-sm mt-1">Información detallada e historial de la unidad.</p>
        </div>
        <a href="{{ route('vehiculos.index') }}"
            class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-lg text-sm font-semibold transition">
            Volver al listado
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="bg-[#1e293b] rounded-xl border border-slate-800 p-6 space-y-4 shadow-xl">
            <div class="text-center pb-4 border-b border-slate-800">
                <span
                    class="bg-slate-900 text-sky-400 font-mono text-sm px-4 py-1.5 rounded-lg border border-slate-700 tracking-wider">
                    {{ $vehiculo->placa }}
                </span>
                <h3 class="text-lg font-bold text-white mt-4">{{ $vehiculo->marca }} {{ $vehiculo->modelo }}</h3>
                <p class="text-xs text-slate-500 uppercase tracking-widest mt-1">Año {{ $vehiculo->anio }}</p>
            </div>

            <div class="space-y-3 pt-2">
                <div>
                    <span class="text-xs text-slate-500 block uppercase font-bold tracking-wider">Propietario</span>
                    <span class="text-sm text-slate-200 font-medium flex items-center gap-2 mt-1">
                        <i class="fa-solid fa-user text-slate-600 text-xs"></i>
                        {{ $vehiculo->propietario->nombre ?? 'Sin propietario' }}
                    </span>
                </div>
            </div>

            <div class="pt-4 border-t border-slate-800">
                <a href="{{ route('vehiculos.edit', $vehiculo->id_vehiculo) }}"
                    class="w-full text-center block px-4 py-2 bg-amber-500/10 hover:bg-amber-500 text-amber-400 hover:text-slate-900 font-semibold rounded-lg text-sm transition">
                    <i class="fa-solid fa-pen text-xs mr-1"></i> Modificar Datos
                </a>
            </div>
        </div>

        <div class="lg:col-span-2 bg-[#1e293b] rounded-xl border border-slate-800 p-6 shadow-xl">
            <h4 class="text-sm font-bold text-slate-400 uppercase tracking-wider mb-4 flex items-center gap-2">
                <i class="fa-solid fa-wrench text-sky-400"></i> Historial Reciente de Mantenimientos
            </h4>

            <div class="space-y-3 max-h-[350px] overflow-y-auto pr-2">
                @forelse($mantenimientos as $mantenimiento)
                    <div
                        class="p-4 bg-slate-900/40 rounded-xl border border-slate-800/80 flex justify-between items-center hover:border-slate-700 transition">
                        <div>
                            <p class="text-sm font-semibold text-white">{{ $mantenimiento->descripcion }}</p>
                            <span
                                class="text-xs text-slate-500 font-mono block mt-1">{{ \Carbon\Carbon::parse($mantenimiento->fecha_servicio)->format('d/m/Y') }}</span>
                        </div>
                        <span
                            class="px-2.5 py-1 rounded bg-sky-500/10 text-sky-400 font-mono text-xs border border-sky-500/20">
                            ${{ number_format($mantenimiento->costo, 2) }}
                        </span>
                    </div>
                @empty
                    <div class="text-center py-12 text-slate-600 text-sm">
                        <i class="fa-solid fa-folder-open text-2xl mb-2 block text-slate-700"></i>
                        Este vehículo aún no cuenta con mantenimientos registrados.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

@endsection
