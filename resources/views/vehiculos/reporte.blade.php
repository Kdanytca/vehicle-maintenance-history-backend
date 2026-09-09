@extends('layouts.app')

@section('content')
    <div class="p-6 max-w-6xl mx-auto">

        <div class="bg-[#1e293b] rounded-xl border border-slate-800 p-6 mb-6 shadow-xl shadow-slate-950/20">
            <h1 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
                <i class="fa-solid fa-file-invoice text-sky-400"></i> Reporte de Mantenimientos
            </h1>

            <form action="/reportes/placa/buscar" method="GET" class="flex flex-col sm:flex-row gap-4 items-end">
                <div class="flex-1 w-full">
                    <label class="block text-sm font-medium text-slate-300 mb-2">Número de placa</label>
                    <input type="text" name="placa" value="{{ request('placa') }}" required
                        class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-sky-500 transition uppercase tracking-wider">
                </div>

                <button type="submit"
                    class="w-full sm:w-auto bg-sky-500 hover:bg-sky-600 text-slate-900 font-bold px-6 py-2.5 rounded-lg text-sm transition whitespace-nowrap cursor-pointer flex items-center justify-center gap-2">
                    <i class="fa-solid fa-magnifying-glass"></i> Buscar
                </button>
            </form>
        </div>

        @if (isset($mensaje))
            <div class="bg-rose-500/10 border border-rose-500/20 p-4 rounded-xl mb-6 shadow-lg">
                <div class="flex items-center gap-3 text-rose-400 text-sm font-medium">
                    <i class="fa-solid fa-triangle-exclamation text-base"></i>
                    <span>{{ $mensaje }}</span>
                </div>
            </div>
        @endif

        @if (isset($vehiculo))
            <div class="bg-[#1e293b] rounded-xl border border-slate-800 p-5 mb-6 shadow-lg shadow-slate-950/10">
                <h2 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-car text-sky-400"></i> Información del Vehículo
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="bg-slate-900/40 border border-slate-800/80 p-4 rounded-lg">
                        <strong class="block text-xs text-sky-400 uppercase tracking-wider mb-1">Placa</strong>
                        <span class="text-sm font-mono font-bold text-white tracking-wide">{{ $vehiculo->placa }}</span>
                    </div>

                    <div class="bg-slate-900/40 border border-slate-800/80 p-4 rounded-lg">
                        <strong class="block text-xs text-sky-400 uppercase tracking-wider mb-1">Marca</strong>
                        <span class="text-sm font-medium text-slate-200">{{ $vehiculo->marca }}</span>
                    </div>

                    <div class="bg-slate-900/40 border border-slate-800/80 p-4 rounded-lg">
                        <strong class="block text-xs text-sky-400 uppercase tracking-wider mb-1">Modelo</strong>
                        <span class="text-sm font-medium text-slate-200">{{ $vehiculo->modelo }}</span>
                    </div>
                </div>
            </div>

            <div class="bg-[#1e293b] rounded-xl border border-slate-800 overflow-hidden shadow-lg shadow-slate-950/10">
                <div class="px-5 py-4 border-b border-slate-800 flex items-center gap-2 bg-slate-900/20">
                    <i class="fa-solid fa-wrench text-emerald-400"></i>
                    <h2 class="font-semibold text-white">Mantenimientos Registrados</h2>
                    <span class="ml-auto text-xs bg-slate-800 px-2 py-0.5 border border-slate-700 rounded text-slate-400">
                        {{ count($mantenimientos) }} registro(s)
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead>
                            <tr class="bg-slate-900/50 border-b border-slate-800 text-slate-400">
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-center">Fecha</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-center">Descripción
                                </th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-center">Estado</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-center">Costo</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-center">Encargado
                                    Interno</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60 text-slate-300">
                            @foreach ($mantenimientos as $mantenimiento)
                                <tr class="hover:bg-slate-800/30 transition-colors">
                                    <td class="px-4 py-3 font-mono text-xs text-slate-400 text-center whitespace-nowrap">
                                        {{ $mantenimiento->fecha_servicio }}
                                    </td>

                                    <td class="px-4 py-3 text-slate-200 text-center max-w-xs truncate"
                                        title="{{ $mantenimiento->descripcion_falla }}">
                                        {{ $mantenimiento->descripcion_falla }}
                                    </td>

                                    <td class="px-4 py-3 text-center whitespace-nowrap">
                                        @php
                                            $estadoKey = strtolower(str_replace(' ', '_', $mantenimiento->estado));

                                            $colores = [
                                                'completado' =>
                                                    'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
                                                'en_proceso' => 'bg-blue-500/10 text-blue-400 border-blue-500/20',
                                                'pendiente' => 'bg-amber-500/10 text-amber-400 border-amber-500/20',
                                                'cancelado' => 'bg-rose-500/10 text-rose-400 border-rose-500/20',
                                            ];

                                            $claseBadge =
                                                $colores[$estadoKey] ??
                                                'bg-slate-500/10 text-slate-400 border-slate-500/20';
                                        @endphp
                                        <span
                                            class="text-xs px-2.5 py-0.5 rounded-full border {{ $claseBadge }} font-medium">
                                            {{ ucfirst(str_replace('_', ' ', $mantenimiento->estado)) }}
                                        </span>
                                    </td>

                                    <td
                                        class="px-4 py-3 text-center text-slate-200 font-mono text-xs font-semibold whitespace-nowrap">
                                        ${{ $mantenimiento->costo_mano_obra }}
                                    </td>

                                    <td class="px-4 py-3 text-center whitespace-nowrap">
                                        @if ($mantenimiento->encargado)
                                            <span
                                                class="inline-flex items-center gap-1.5 text-xs font-medium bg-slate-800 text-slate-300 border border-slate-700 px-2.5 py-1 rounded-lg">
                                                <i class="fa-solid fa-user-gear text-[11px] text-slate-500"></i>
                                                {{ $mantenimiento->encargado->username }}
                                            </span>
                                        @else
                                            <span
                                                class="inline-flex items-center gap-1.5 text-xs font-medium bg-slate-800/40 text-slate-600 italic border border-slate-800 px-2.5 py-1 rounded-lg">
                                                Sin asignar
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            @if (!isset($mensaje))
                <div class="bg-[#1e293b] rounded-xl border border-slate-800 p-16 text-center shadow-xl shadow-slate-950/10">
                    <i class="fa-solid fa-magnifying-glass text-5xl text-slate-700 mb-4 block"></i>
                    <p class="text-slate-400 font-medium">Ingresa el número de una placa para generar el reporte.</p>
                    <p class="text-slate-600 text-sm mt-1">Se extraerá la información técnica junto con su histórico
                        operativo.</p>
                </div>
            @endif
        @endif

    </div>
@endsection
