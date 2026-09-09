@extends('layouts.app')

@section('title', 'Listado de Mantenimientos - SGA')

@section('content')
    <div class="container mx-auto px-4 py-6 max-w-7xl">

        <div
            class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-[#0f172a] border border-slate-800/60 p-4 rounded-xl shadow-sm">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-sky-500/10 border border-sky-500/20 rounded-xl flex items-center justify-center">
                    <i class="fa-solid fa-wrench text-sky-400 text-sm"></i>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-white">Listado de Mantenimientos</h1>
                    <p class="text-slate-400 text-xs mt-0.5">Historial y órdenes de servicio técnico en el taller.</p>
                </div>
            </div>

            <a href="{{ route('mantenimientos.create') }}"
                class="bg-sky-500 hover:bg-sky-600 transition text-slate-900 px-4 py-2.5 rounded-lg font-bold text-xs uppercase tracking-wider flex items-center justify-center gap-2 shadow-md shadow-sky-500/10 self-start sm:self-auto whitespace-nowrap">
                <i class="fa-solid fa-plus"></i> Nuevo Mantenimiento
            </a>
        </div>

        @if (session('success'))
            <div
                class="bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 px-4 py-3 rounded-xl mb-6 flex items-center gap-2.5 text-sm shadow-sm">
                <i class="fa-solid fa-circle-check text-base"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        <div class="bg-[#1e293b] rounded-xl border border-slate-800 overflow-hidden shadow-xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="bg-slate-900/50 border-b border-slate-800 text-slate-400">
                            <th class="p-4 font-semibold text-xs uppercase tracking-wider">ID</th>
                            <th class="p-4 font-semibold text-xs uppercase tracking-wider">Fecha</th>
                            <th class="p-4 font-semibold text-xs uppercase tracking-wider">Descripción</th>
                            <th class="p-4 font-semibold text-xs uppercase tracking-wider">Estado</th>
                            <th class="p-4 font-semibold text-xs uppercase tracking-wider">Vehículo</th>
                            <th class="p-4 font-semibold text-xs uppercase tracking-wider">Encargado Interno</th>
                            <th class="p-4 font-semibold text-xs uppercase tracking-wider text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 text-slate-300">
                        @forelse($mantenimientos as $m)
                            <tr class="hover:bg-slate-800/30 transition-colors">
                                <td class="p-4 font-mono text-xs text-slate-400">{{ $m->id_mantenimiento }}</td>

                                <td class="p-4 whitespace-nowrap text-slate-200 font-mono text-xs">{{ $m->fecha_servicio }}
                                </td>

                                <td class="p-4 max-w-xs truncate text-slate-300" title="{{ $m->descripcion_falla }}">
                                    {{ $m->descripcion_falla }}
                                </td>

                                <td class="p-4 whitespace-nowrap">
                                    @php
                                        $estadoLower = strtolower(str_replace(' ', '_', $m->estado));
                                        $badgeClase = match ($estadoLower) {
                                            'pendiente' => 'bg-amber-500/10 text-amber-400 border-amber-500/20',
                                            'en_proceso' => 'bg-blue-500/10 text-blue-400 border-blue-500/20',
                                            'completado' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
                                            default => 'bg-rose-500/10 text-rose-400 border-rose-500/20',
                                        };
                                    @endphp
                                    <span
                                        class="text-xs px-2.5 py-0.5 rounded-full border {{ $badgeClase }} font-medium inline-flex items-center gap-1.5">
                                        <i class="fa-solid fa-circle text-[6px]"></i>
                                        {{ ucfirst(str_replace('_', ' ', $m->estado)) }}
                                    </span>
                                </td>

                                <td class="p-4 whitespace-nowrap font-mono text-xs font-bold text-sky-400">
                                    {{ $m->vehiculo->placa ?? 'N/A' }}</td>

                                <td class="p-4 whitespace-nowrap">
                                    @if ($m->encargado)
                                        <span class="inline-flex items-center gap-1.5 text-xs text-slate-300">
                                            <i class="fa-solid fa-user-gear text-[11px] text-slate-500"></i>
                                            {{ $m->encargado->username }}
                                        </span>
                                    @else
                                        <span class="text-xs text-slate-600 italic">Sin asignar</span>
                                    @endif
                                </td>

                                <td class="p-4 text-right space-x-1.5 whitespace-nowrap">
                                    <a href="{{ route('mantenimientos.edit', $m->id_mantenimiento) }}"
                                        class="inline-flex items-center justify-center p-2 bg-slate-800 hover:bg-sky-500/20 text-sky-400 rounded-lg text-xs font-medium border border-slate-700/60 transition"
                                        title="Editar mantenimiento">
                                        <i class="fa-solid fa-pen"></i>
                                    </a>
                                    <form action="{{ route('mantenimientos.destroy', $m->id_mantenimiento) }}"
                                        method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            onclick="return confirm('¿Está seguro de que desea cancelar esta orden de mantenimiento?')"
                                            class="inline-flex items-center justify-center p-2 bg-slate-800 hover:bg-rose-500/20 text-rose-400 rounded-lg text-xs font-medium border border-slate-700/60 transition cursor-pointer"
                                            title="Cancelar mantenimiento">
                                            <i class="fa-solid fa-ban"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-12 text-center text-slate-500">
                                    <i class="fa-solid fa-folder-open text-3xl mb-3 block text-slate-600"></i>
                                    <span class="text-sm font-medium">No se han registrado órdenes de mantenimiento en el
                                        sistema.</span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
