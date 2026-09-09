@extends('layouts.app')

@section('content')
    <div class="p-6 max-w-6xl mx-auto">

        <!-- Selector de Vehículo -->
        <div class="bg-[#1e293b] rounded-xl border border-slate-800 p-5 mb-6 shadow-xl shadow-slate-950/20">
            <h2 class="text-sm font-semibold text-slate-300 uppercase tracking-wider mb-3 flex items-center gap-2">
                <i class="fa-solid fa-magnifying-glass text-sky-400"></i> Seleccionar Vehículo
            </h2>
            <form method="GET" action="{{ route('historial-vehiculo.index') }}" class="flex flex-col sm:flex-row gap-3">
                <select name="id_vehiculo" required
                    class="flex-1 bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-sky-500 transition">
                    <option value="">-- Seleccione un vehículo --</option>
                    @foreach ($vehiculos as $v)
                        <option value="{{ $v->id_vehiculo }}"
                            {{ isset($vehiculo) && $vehiculo->id_vehiculo == $v->id_vehiculo ? 'selected' : '' }}>
                            {{ $v->placa }} — {{ $v->marca }} {{ $v->modelo }} {{ $v->anio }}
                            ({{ $v->propietario->nombre ?? 'Sin propietario' }})
                        </option>
                    @endforeach
                </select>
                <button type="submit"
                    class="bg-sky-500 hover:bg-sky-600 text-slate-900 font-bold px-5 py-2 rounded-lg text-sm transition whitespace-nowrap cursor-pointer">
                    <i class="fa-solid fa-search mr-1"></i> Ver Historial
                </button>
            </form>
        </div>

        @if ($vehiculo)
            <!-- Información General del Vehículo Encontrado -->
            <div class="bg-[#1e293b] rounded-xl border border-slate-800 p-5 mb-6 shadow-lg shadow-slate-950/10">
                <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                    <div
                        class="w-12 h-12 bg-sky-500/10 border border-sky-500/20 rounded-xl flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-car text-sky-400 text-lg"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-lg font-bold text-white">{{ $vehiculo->placa }}</span>
                            <span
                                class="text-xs bg-sky-500/10 text-sky-400 border border-sky-500/20 px-2 py-0.5 rounded-full font-medium">
                                {{ $vehiculo->marca }} {{ $vehiculo->modelo }}
                            </span>
                        </div>
                        <p class="text-sm text-slate-400 mt-0.5">
                            Año: <span class="text-slate-200 font-mono">{{ $vehiculo->anio }}</span> &nbsp;|&nbsp;
                            Propietario: <span
                                class="text-slate-200 font-medium">{{ $vehiculo->propietario->nombre ?? 'N/A' }}</span>
                        </p>
                    </div>
                    <div
                        class="sm:ml-auto text-left sm:text-right bg-slate-900/40 p-3 rounded-lg border border-slate-800/60 sm:bg-transparent sm:p-0 sm:border-0">
                        <p class="text-2xl font-bold text-white font-mono leading-none">{{ $mantenimientos->count() }}</p>
                        <p class="text-xs text-slate-400 mt-1">mantenimiento(s) en total</p>
                    </div>
                </div>
            </div>

            <!-- Tabla: Mantenimientos Registrados -->
            <div class="bg-[#1e293b] rounded-xl border border-slate-800 overflow-hidden mb-6 shadow-lg shadow-slate-950/10">
                <div class="px-5 py-4 border-b border-slate-800 flex items-center gap-2 bg-slate-900/20">
                    <i class="fa-solid fa-wrench text-emerald-400"></i>
                    <h2 class="font-semibold text-white">Mantenimientos Registrados</h2>
                    <span
                        class="ml-auto text-xs bg-slate-800 px-2 py-0.5 border border-slate-700 rounded text-slate-400">{{ $mantenimientos->count() }}
                        registro(s)</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead>
                            <tr class="bg-slate-900/50 border-b border-slate-800 text-slate-400">
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider">Fecha</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider">Descripción</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider">Estado</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider">Costo M.O.</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60 text-slate-300">
                            @forelse($mantenimientos as $m)
                                <tr class="hover:bg-slate-800/30 transition-colors">
                                    <td class="px-4 py-3 font-mono text-xs text-slate-400">{{ $m->fecha_servicio }}</td>
                                    <td class="px-4 py-3 text-slate-200 max-w-xs truncate"
                                        title="{{ $m->descripcion_falla }}">{{ $m->descripcion_falla }}</td>
                                    <td class="px-4 py-3">
                                        @php
                                            $colores = [
                                                'completado' =>
                                                    'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
                                                'en_proceso' => 'bg-amber-500/10 text-amber-400 border-amber-500/20',
                                                'pendiente' => 'bg-slate-500/10 text-slate-400 border-slate-500/20',
                                                'cancelado' => 'bg-rose-500/10 text-rose-400 border-rose-500/20',
                                            ];
                                            $clase =
                                                $colores[$m->estado] ??
                                                'bg-slate-500/10 text-slate-400 border-slate-500/20';
                                        @endphp
                                        <span
                                            class="text-xs px-2.5 py-0.5 rounded-full border {{ $clase }} font-medium">
                                            {{ ucfirst(str_replace('_', ' ', $m->estado)) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-slate-300 font-mono text-xs font-semibold">
                                        {{ $m->costo_mano_obra ? '$' . number_format($m->costo_mano_obra, 2) : '—' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-12 text-center text-slate-500">
                                        <i class="fa-solid fa-folder-open text-3xl mb-2 block text-slate-600"></i>
                                        No hay mantenimientos registrados para este vehículo.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tabla: Historial de Auditoría -->
            <div class="bg-[#1e293b] rounded-xl border border-slate-800 overflow-hidden shadow-lg shadow-slate-950/10">
                <div class="px-5 py-4 border-b border-slate-800 flex items-center gap-2 bg-slate-900/20">
                    <i class="fa-solid fa-file-lines text-violet-400"></i>
                    <h2 class="font-semibold text-white">Historial de Auditoría</h2>
                    <span
                        class="ml-auto text-xs bg-slate-800 px-2 py-0.5 border border-slate-700 rounded text-slate-400">{{ $historial->count() }}
                        evento(s)</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead>
                            <tr class="bg-slate-900/50 border-b border-slate-800 text-slate-400">
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider">Fecha</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider">Tipo Evento</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider">Descripción</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider">Usuario</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider">IP</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60 text-slate-300">
                            @forelse($historial as $h)
                                <tr class="hover:bg-slate-800/30 transition-colors">
                                    <td class="px-4 py-3 font-mono text-xs text-slate-400 whitespace-nowrap">
                                        {{ $h->created_at->format('d/m/Y H:i') }}
                                    </td>
                                    <td class="px-4 py-3">
                                        @php
                                            $badge = match ($h->tipo_evento) {
                                                'AUDITORIA_CAMBIO' => 'bg-sky-500/10 text-sky-400 border-sky-500/20',
                                                'LOGIN_FALLIDO' => 'bg-rose-500/10 text-rose-400 border-rose-500/20',
                                                'LOG_SISTEMA' => 'bg-slate-500/10 text-slate-400 border-slate-500/20',
                                                default => 'bg-violet-500/10 text-violet-400 border-violet-500/20',
                                            };
                                        @endphp
                                        <span
                                            class="text-xs px-2 py-0.5 rounded border {{ $badge }} font-medium font-mono">
                                            {{ $h->tipo_evento }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-slate-200 max-w-xs sm:max-w-sm truncate"
                                        title="{{ $h->descripcion_evento }}">{{ $h->descripcion_evento }}</td>
                                    <td class="px-4 py-3 text-slate-400 text-xs font-medium">
                                        {{ $h->usuario->username ?? '—' }}</td>
                                    <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ $h->direccion_ip ?? '—' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-12 text-center text-slate-500">
                                        <i class="fa-solid fa-circle-info text-3xl mb-2 block text-slate-600"></i>
                                        No hay eventos de auditoría vinculados a este vehículo.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <!-- Estado Inicial / Vacío -->
            <div class="bg-[#1e293b] rounded-xl border border-slate-800 p-16 text-center shadow-xl shadow-slate-950/10">
                <i class="fa-solid fa-car-side text-5xl text-slate-700 mb-4 block"></i>
                <p class="text-slate-400 font-medium">Selecciona un vehículo para ver su historial completo.</p>
                <p class="text-slate-600 text-sm mt-1">Verás sus mantenimientos y eventos de auditoría registrados.</p>
            </div>
        @endif

    </div>
@endsection
