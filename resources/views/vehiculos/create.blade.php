@extends('layouts.app')

@section('title', 'Registrar Vehículo - SGA')

@section('content')

    <div class="flex flex-col items-center justify-center min-h-[80vh] px-4">

        <div class="w-full max-w-3xl">

            <div class="mb-6 text-center sm:text-left">
                <h2 class="text-xl font-bold text-white flex items-center justify-center sm:justify-start gap-2">
                    <i class="fa-solid fa-car bg-sky-500/10 text-sky-400 p-2 rounded-lg text-sm"></i>
                    Registrar Nuevo Vehículo
                </h2>
                <p class="text-slate-400 text-sm mt-1">Completa los campos para ingresar una nueva unidad al historial del
                    taller.</p>
            </div>

            <div class="bg-[#1e293b] rounded-xl border border-slate-800 shadow-xl overflow-hidden">
                <form action="{{ route('vehiculos.store') }}" method="POST" class="p-6 space-y-6">
                    @csrf

                    @if ($errors->any())
                        <div
                            class="bg-rose-500/10 border border-rose-500/20 text-rose-400 p-4 rounded-lg text-sm space-y-1">
                            <p class="font-semibold flex items-center gap-2">
                                <i class="fa-solid fa-triangle-exclamation"></i> Por favor corrige los siguientes errores:
                            </p>
                            <ul class="list-disc list-inside text-xs text-rose-300/90 pl-2">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                        <div>
                            <label for="placa"
                                class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Placa</label>
                            <input type="text" name="placa" id="placa" value="{{ old('placa') }}" required
                                placeholder="P123-456"
                                class="w-full bg-slate-900 border border-slate-800 rounded-lg px-4 py-2.5 text-white placeholder-slate-600 font-mono text-sm focus:outline-none focus:border-sky-500 transition">
                        </div>

                        <div>
                            <label for="marca"
                                class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Marca</label>
                            <input type="text" name="marca" id="marca" value="{{ old('marca') }}" required
                                placeholder="Ej: Toyota"
                                class="w-full bg-slate-900 border border-slate-800 rounded-lg px-4 py-2.5 text-white placeholder-slate-600 text-sm focus:outline-none focus:border-sky-500 transition">
                        </div>

                        <div>
                            <label for="modelo"
                                class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Modelo</label>
                            <input type="text" name="modelo" id="modelo" value="{{ old('modelo') }}" required
                                placeholder="Ej: Corolla"
                                class="w-full bg-slate-900 border border-slate-800 rounded-lg px-4 py-2.5 text-white placeholder-slate-600 text-sm focus:outline-none focus:border-sky-500 transition">
                        </div>

                        <div>
                            <label for="anio"
                                class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Año</label>
                            <input type="number" name="anio" id="anio" value="{{ old('anio') }}" required
                                placeholder="Ej: 2020" min="1900" max="{{ date('Y') + 1 }}"
                                class="w-full bg-slate-900 border border-slate-800 rounded-lg px-4 py-2.5 text-white placeholder-slate-600 font-mono text-sm focus:outline-none focus:border-sky-500 transition">
                        </div>

                        <div class="md:col-span-2">
                            <label for="propietario"
                                class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Nombre del
                                Propietario</label>
                            <input type="text" name="propietario" id="propietario" value="{{ old('propietario') }}"
                                required placeholder="Ej: Juan Pérez"
                                class="w-full bg-slate-900 border border-slate-800 rounded-lg px-4 py-2.5 text-white placeholder-slate-600 text-sm focus:outline-none focus:border-sky-500 transition">
                        </div>

                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-800/60">

                        <a href="{{ route('vehiculos.index') }}"
                            class="px-4 py-2 bg-slate-800 hover:bg-slate-700/80 text-slate-300 rounded-lg text-sm font-semibold transition flex items-center gap-2">
                            Cancelar
                        </a>

                        <button type="submit"
                            class="px-4 py-2 bg-sky-500 hover:bg-sky-600 text-slate-900 rounded-lg text-sm font-semibold transition flex items-center gap-2 shadow-lg shadow-sky-500/10">
                            <i class="fa-solid fa-floppy-disk text-xs"></i> Guardar Vehículo
                        </button>

                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection
