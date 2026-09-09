@extends('layouts.app')

@section('content')
    <div class="container mx-auto px-4 py-8 max-w-2xl">
        <h1 class="text-2xl font-bold text-white mb-6">Registrar Mantenimiento</h1>

        @if ($errors->any())
            <div class="bg-rose-500/10 border border-rose-500/20 text-rose-400 px-4 py-3 rounded-xl mb-4 text-sm">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('mantenimientos.store') }}" method="POST"
            class="bg-[#1e293b] rounded-xl border border-slate-800 p-6 space-y-4">
            @csrf

            <div>
                <label class="block text-xs font-semibold text-slate-400 mb-1">Vehículo *</label>
                <select name="id_vehiculo" required
                    class="w-full bg-slate-900 border border-slate-800 rounded-lg p-2 text-slate-200">
                    <option value="">Seleccione un vehículo...</option>
                    @foreach ($vehiculos as $v)
                        <option value="{{ $v->id_vehiculo }}" {{ old('id_vehiculo') == $v->id_vehiculo ? 'selected' : '' }}>
                            {{ $v->placa }} - {{ $v->marca }} {{ $v->modelo }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-400 mb-1">Fecha del servicio *</label>
                <input type="date" name="fecha_servicio" value="{{ old('fecha_servicio', date('Y-m-d')) }}" required
                    class="w-full bg-slate-900 border border-slate-800 rounded-lg p-2 text-slate-200 font-mono">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-400 mb-1">Encargado (Usuario del Sistema) *</label>
                <select name="id_usuario_encargado" required
                    class="w-full bg-slate-900 border border-slate-800 rounded-lg p-2 text-slate-200">
                    <option value="">Seleccione un encargado...</option>
                    @foreach ($usuarios as $user)
                        <option value="{{ $user->id_usuario }}"
                            {{ old('id_usuario_encargado', $usuarioActivoId) == $user->id_usuario ? 'selected' : '' }}>
                            {{ $user->username }} {{ $user->id_usuario === $usuarioActivoId ? '(Tú)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-400 mb-1">Descripción de la falla *</label>
                <textarea name="descripcion_falla" rows="3" required
                    class="w-full bg-slate-900 border border-slate-800 rounded-lg p-2 text-slate-200 resize-none">{{ old('descripcion_falla') }}</textarea>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-400 mb-1">Costo mano de obra</label>
                <input type="number" step="0.01" name="costo_mano_obra" value="{{ old('costo_mano_obra', '0.00') }}"
                    class="w-full bg-slate-900 border border-slate-800 rounded-lg p-2 text-slate-200 font-mono">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-400 mb-1">Estado *</label>
                <select name="estado" required
                    class="w-full bg-slate-900 border border-slate-800 rounded-lg p-2 text-slate-200">
                    <option value="Pendiente" {{ old('estado') == 'Pendiente' ? 'selected' : '' }}>Pendiente</option>
                    <option value="En Proceso" {{ old('estado') == 'En Proceso' ? 'selected' : '' }}>En Proceso</option>
                    <option value="Completado" {{ old('estado') == 'Completado' ? 'selected' : '' }}>Completado</option>
                    <option value="Cancelado" {{ old('estado') == 'Cancelado' ? 'selected' : '' }}>Cancelado</option>
                </select>
            </div>

            <div class="flex gap-3 pt-4">
                <button type="submit"
                    class="bg-sky-500 hover:bg-sky-600 text-slate-900 px-4 py-2 rounded-lg font-bold text-sm transition">Guardar</button>
                <a href="{{ route('mantenimientos.index') }}"
                    class="bg-slate-800 hover:bg-slate-700 text-slate-300 px-4 py-2 rounded-lg text-sm transition text-center">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
