@extends('layouts.app')

@section('title', 'Crear Usuario - SGA')

@section('content')
    <div class="flex flex-col items-center justify-center min-h-[80vh] px-4">
        <div class="w-full max-w-2xl">

            <div class="mb-6 text-center sm:text-left flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center justify-center sm:justify-start gap-3">
                    <div
                        class="w-10 h-10 bg-sky-500/10 border border-sky-500/20 rounded-xl flex items-center justify-center">
                        <i class="fa-solid fa-user-plus text-sky-400 text-sm"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-white">Crear Nuevo Usuario</h1>
                        <p class="text-slate-400 text-xs mt-0.5">Ingresa las credenciales y define el rol de acceso al
                            sistema.</p>
                    </div>
                </div>

                <a href="{{ route('usuarios.index') }}"
                    class="text-slate-400 hover:text-sky-400 transition text-sm self-center sm:self-auto flex items-center gap-1.5">
                    <i class="fa-solid fa-arrow-left text-xs"></i> Volver al Listado
                </a>
            </div>

            @if ($errors->any())
                <div class="bg-rose-500/10 border border-rose-500/20 text-rose-400 px-4 py-3 rounded-xl mb-6 shadow-md">
                    <div class="flex items-center gap-2 mb-2 text-sm">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <span class="font-semibold">Por favor corrige los siguientes errores:</span>
                    </div>
                    <ul class="list-disc list-inside text-xs text-rose-300/90 pl-2 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-[#1e293b] rounded-xl border border-slate-800 shadow-xl overflow-hidden">
                <form method="POST" action="{{ route('usuarios.store') }}" class="p-6 space-y-5">
                    @csrf

                    <div>
                        <label for="username"
                            class="block text-xs font-semibold text-slate-400 mb-1.5 uppercase tracking-wider">
                            <i class="fa-solid fa-user mr-1 text-slate-500"></i> Nombre de Usuario *
                        </label>
                        <input type="text" name="username" id="username" value="{{ old('username') }}" required
                            autocomplete="off" placeholder="Ej: operez"
                            class="w-full bg-slate-900 border border-slate-800 rounded-lg px-4 py-2.5 text-sm text-slate-200 placeholder-slate-600 focus:outline-none focus:border-sky-500 transition">
                        <p class="text-[11px] text-slate-500 mt-1.5">Mínimo 3 caracteres, único en el sistema.</p>
                    </div>

                    <div>
                        <label for="password"
                            class="block text-xs font-semibold text-slate-400 mb-1.5 uppercase tracking-wider">
                            <i class="fa-solid fa-lock mr-1 text-slate-500"></i> Contraseña *
                        </label>
                        <input type="password" name="password" id="password" required placeholder="••••••••"
                            class="w-full bg-slate-900 border border-slate-800 rounded-lg px-4 py-2.5 text-sm text-slate-200 placeholder-slate-600 focus:outline-none focus:border-sky-500 transition">
                        <p class="text-[11px] text-slate-500 mt-1.5">Mínimo 6 caracteres.</p>
                    </div>

                    <div>
                        <label for="password_confirmation"
                            class="block text-xs font-semibold text-slate-400 mb-1.5 uppercase tracking-wider">
                            <i class="fa-solid fa-lock mr-1 text-slate-500"></i> Confirmar Contraseña *
                        </label>
                        <input type="password" name="password_confirmation" id="password_confirmation" required
                            placeholder="••••••••"
                            class="w-full bg-slate-900 border border-slate-800 rounded-lg px-4 py-2.5 text-sm text-slate-200 placeholder-slate-600 focus:outline-none focus:border-sky-500 transition">
                    </div>

                    <div>
                        <label for="id_role"
                            class="block text-xs font-semibold text-slate-400 mb-1.5 uppercase tracking-wider">
                            <i class="fa-solid fa-briefcase mr-1 text-slate-500"></i> Rol de Asignación *
                        </label>
                        <select name="id_rol" id="id_role" required
                            class="w-full bg-slate-900 border border-slate-800 rounded-lg px-4 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-sky-500 transition appearance-none cursor-pointer">
                            <option value="" class="text-slate-600">Seleccione un rol...</option>
                            @foreach ($roles as $rol)
                                <option value="{{ $rol->id_rol }}" {{ old('id_rol') == $rol->id_rol ? 'selected' : '' }}>
                                    {{ ucfirst($rol->nombre_rol) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-400 mb-2 uppercase tracking-wider">
                            <i class="fa-solid fa-circle-info mr-1 text-slate-500"></i> Estado Inicial del Usuario
                        </label>
                        <div
                            class="flex items-center gap-6 bg-slate-900/40 border border-slate-800/60 p-3 rounded-lg w-fit">
                            <label class="flex items-center gap-2 cursor-pointer select-none">
                                <input type="radio" name="estado_activo" value="1"
                                    {{ old('estado_activo', '1') == '1' ? 'checked' : '' }} class="accent-sky-500 h-4 w-4">
                                <span class="text-sm text-slate-300 font-medium">Activo</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer select-none">
                                <input type="radio" name="estado_activo" value="0"
                                    {{ old('estado_activo') == '0' ? 'checked' : '' }} class="accent-rose-500 h-4 w-4">
                                <span class="text-sm text-slate-300 font-medium">Inactivo</span>
                            </label>
                        </div>
                    </div>

                    <div class="flex gap-3 pt-4 border-t border-slate-800/60">
                        <a href="{{ route('usuarios.index') }}"
                            class="px-5 bg-slate-800 hover:bg-slate-700 transition text-slate-300 font-semibold py-2.5 rounded-lg text-sm text-center flex-1 sm:flex-none">
                            Cancelar
                        </a>
                        <button type="submit"
                            class="flex-1 bg-sky-500 hover:bg-sky-600 transition text-slate-900 font-bold py-2.5 rounded-lg text-sm flex items-center justify-center gap-2 shadow-lg shadow-sky-500/10 cursor-pointer">
                            <i class="fa-solid fa-floppy-disk text-xs"></i> Crear Usuario
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
@endsection
