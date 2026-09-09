@extends('layouts.app')

@section('title', 'Roles y Permisos - SGA')

@section('content')
    <div
        class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-[#0f172a] border border-slate-800/60 p-4 rounded-xl shadow-sm">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-violet-500/10 border border-violet-500/20 rounded-xl flex items-center justify-center">
                <i class="fa-solid fa-shield-halved text-violet-400 text-sm"></i>
            </div>
            <div>
                <h1 class="text-xl font-bold text-white">Configurar Roles y Permisos</h1>
                <p class="text-slate-400 text-xs mt-0.5">Administra los niveles de acceso y los privilegios de los usuarios
                    del sistema.</p>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div
            class="bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 px-4 py-3 rounded-xl mb-6 flex items-center gap-2.5 text-sm shadow-sm">
            <i class="fa-solid fa-circle-check text-base"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="bg-rose-500/10 border border-rose-500/20 text-rose-400 px-4 py-3 rounded-xl mb-6 shadow-sm">
            <div class="flex items-center gap-2 mb-1.5 text-sm">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span class="font-semibold">Errores detectados:</span>
            </div>
            <ul class="list-disc list-inside text-xs text-rose-300/90 pl-2 space-y-0.5">
                @foreach ($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <div class="space-y-6">

            <div class="bg-[#1e293b] rounded-xl border border-slate-800 p-5 shadow-xl">
                <h2 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-user-tag text-violet-400"></i> Nuevo Rol de Acceso
                </h2>
                <form method="POST" action="{{ route('roles-permisos.store-rol') }}" class="space-y-3">
                    @csrf
                    <div>
                        <label for="nombre_rol" class="text-xs text-slate-400 block mb-1">Nombre del rol *</label>
                        <input type="text" name="nombre_rol" id="nombre_rol" placeholder="Ej: supervisor" required
                            class="w-full bg-slate-900 border border-slate-800 rounded-lg px-3 py-2 text-sm text-white placeholder-slate-700 focus:outline-none focus:border-violet-500 transition">
                    </div>
                    <div>
                        <label for="descripcion_rol" class="text-xs text-slate-400 block mb-1">Descripción</label>
                        <input type="text" name="descripcion" id="descripcion_rol" placeholder="Funciones asignadas"
                            class="w-full bg-slate-900 border border-slate-800 rounded-lg px-3 py-2 text-sm text-white placeholder-slate-700 focus:outline-none focus:border-violet-500 transition">
                    </div>
                    <button type="submit"
                        class="w-full bg-violet-600 hover:bg-violet-700 text-white py-2 rounded-lg text-sm font-semibold transition flex items-center justify-center gap-1.5 cursor-pointer shadow-lg shadow-violet-600/10">
                        <i class="fa-solid fa-plus text-xs"></i> Crear Rol
                    </button>
                </form>
            </div>

            <div class="bg-[#1e293b] rounded-xl border border-slate-800 p-5 shadow-xl">
                <h2 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-key text-amber-400"></i> Nuevo Permiso Individual
                </h2>
                <form method="POST" action="{{ route('roles-permisos.store-permiso') }}" class="space-y-3">
                    @csrf
                    <div>
                        <label for="nombre_permiso" class="text-xs text-slate-400 block mb-1">Nombre del permiso *</label>
                        <input type="text" name="nombre_permiso" id="nombre_permiso" placeholder="Ej: ver_reportes"
                            required
                            class="w-full bg-slate-900 border border-slate-800 rounded-lg px-3 py-2 text-sm text-white placeholder-slate-700 focus:outline-none focus:border-amber-500 transition">
                    </div>
                    <div>
                        <label for="descripcion_permiso" class="text-xs text-slate-400 block mb-1">Descripción</label>
                        <input type="text" name="descripcion" id="descripcion_permiso" placeholder="Qué acción autoriza"
                            class="w-full bg-slate-900 border border-slate-800 rounded-lg px-3 py-2 text-sm text-white placeholder-slate-700 focus:outline-none focus:border-amber-500 transition">
                    </div>
                    <button type="submit"
                        class="w-full bg-amber-600 hover:bg-amber-700 text-slate-950 py-2 rounded-lg text-sm font-bold transition flex items-center justify-center gap-1.5 cursor-pointer shadow-lg shadow-amber-600/10">
                        <i class="fa-solid fa-plus text-xs"></i> Crear Permiso
                    </button>
                </form>
            </div>

            <div class="bg-[#1e293b] rounded-xl border border-slate-800 p-5 shadow-xl">
                <h2 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3 flex items-center gap-2">
                    <i class="fa-solid fa-list-check text-slate-400"></i> Permisos en el Sistema
                </h2>
                <div class="max-h-[300px] overflow-y-auto pr-1 space-y-1">
                    @forelse($permisos as $permiso)
                        <div
                            class="flex items-center justify-between py-2 border-b border-slate-800/60 last:border-0 group">
                            <div class="min-w-0 flex-1 pr-2">
                                <p class="text-sm font-medium text-white truncate">{{ $permiso->nombre_permiso }}</p>
                                @if ($permiso->descripcion)
                                    <p class="text-xs text-slate-500 truncate">{{ $permiso->descripcion }}</p>
                                @endif
                            </div>
                            <form method="POST"
                                action="{{ route('roles-permisos.destroy-permiso', $permiso->id_permiso) }}"
                                class="flex-shrink-0">
                                @csrf @method('DELETE')
                                <button type="submit"
                                    onclick="return confirm('¿Seguro que deseas eliminar permanentemente este permiso?')"
                                    class="text-xs text-slate-500 hover:text-rose-400 transition p-1.5 rounded-md hover:bg-rose-500/5 cursor-pointer">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    @empty
                        <div class="text-center py-6 text-slate-500">
                            <i class="fa-solid fa-inbox block text-xl mb-2 text-slate-600"></i>
                            <p class="text-xs font-medium">No hay permisos registrados.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="lg:col-span-2 space-y-4">
            <div class="flex items-center justify-between px-1">
                <h2 class="text-xs font-semibold text-slate-400 uppercase tracking-wider flex items-center gap-2">
                    <i class="fa-solid fa-layer-group text-violet-400"></i> Matriz de Roles y Asignaciones
                </h2>
                <span
                    class="text-xs text-slate-500 font-mono bg-slate-900/60 border border-slate-800 px-2 py-0.5 rounded-md">{{ $roles->count() }}
                    Rol(es)</span>
            </div>

            @forelse($roles as $rol)
                <div
                    class="bg-[#1e293b] rounded-xl border border-slate-800 p-5 shadow-xl transition-all hover:border-slate-700/60">

                    <div class="flex items-start justify-between mb-4 pb-4 border-b border-slate-800/60">
                        <div>
                            <div class="flex items-center gap-2.5">
                                <span
                                    class="text-xs px-2.5 py-0.5 rounded-full font-medium border
                                    {{ $rol->nombre_rol === 'admin' ? 'bg-sky-500/10 text-sky-400 border-sky-500/20' : 'bg-violet-500/10 text-violet-400 border-violet-500/20' }}">
                                    {{ ucfirst($rol->nombre_rol) }}
                                </span>
                                <span class="text-xs text-slate-500 font-medium">
                                    <i class="fa-solid fa-key text-[10px] mr-1"></i>{{ $rol->permisos->count() }}
                                    permiso(s) otorgado(s)
                                </span>
                            </div>
                            @if ($rol->descripcion)
                                <p class="text-xs text-slate-400 mt-1.5">{{ $rol->descripcion }}</p>
                            @endif
                        </div>

                        <form method="POST" action="{{ route('roles-permisos.destroy-rol', $rol->id_rol) }}">
                            @csrf @method('DELETE')
                            <button type="submit"
                                onclick="return confirm('¿Eliminar por completo el rol {{ $rol->nombre_rol }}? Esto puede afectar a los usuarios vinculados.')"
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 text-slate-400 hover:text-rose-400 hover:bg-rose-500/5 rounded-lg text-xs font-medium border border-slate-800 hover:border-rose-500/20 transition cursor-pointer">
                                <i class="fa-solid fa-trash-can text-[10px]"></i> Eliminar Rol
                            </button>
                        </form>
                    </div>

                    <form method="POST" action="{{ route('roles-permisos.asignar', $rol->id_rol) }}">
                        @csrf @method('PUT')

                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 mb-4">
                            @foreach ($permisos as $permiso)
                                <label
                                    class="flex items-center gap-2 bg-slate-900/40 border border-slate-800/80 rounded-lg px-3 py-2 cursor-pointer hover:border-violet-500/30 transition group select-none">
                                    <input type="checkbox" name="permisos[]" value="{{ $permiso->id_permiso }}"
                                        {{ $rol->permisos->contains('id_permiso', $permiso->id_permiso) ? 'checked' : '' }}
                                        class="accent-violet-500 w-4 h-4 rounded">
                                    <span class="text-xs text-slate-400 group-hover:text-slate-200 transition truncate"
                                        title="{{ $permiso->nombre_permiso }}">
                                        {{ $permiso->nombre_permiso }}
                                    </span>
                                </label>
                            @endforeach
                        </div>

                        @if ($permisos->isEmpty())
                            <div class="bg-slate-900/30 border border-slate-800/80 rounded-lg p-3 text-center mb-3">
                                <p class="text-xs text-slate-500">
                                    <i class="fa-solid fa-circle-info mr-1 text-slate-600"></i>
                                    Primero registra un permiso en el bloque lateral para poder vincularlo.
                                </p>
                            </div>
                        @endif

                        <button type="submit"
                            class="bg-violet-600/10 hover:bg-violet-600/20 border border-violet-500/20 hover:border-violet-500/40 text-violet-400 px-3.5 py-1.5 rounded-lg text-xs font-semibold transition flex items-center gap-1.5 cursor-pointer">
                            <i class="fa-solid fa-floppy-disk text-[11px]"></i> Guardar Permisos
                        </button>
                    </form>
                </div>
            @empty
                <div class="bg-[#1e293b] rounded-xl border border-slate-800 p-12 text-center shadow-xl">
                    <i class="fa-solid fa-shield-halved text-4xl text-slate-700 mb-3 block"></i>
                    <p class="text-slate-500 text-sm font-medium">No se han encontrado roles configurados en la base de
                        datos.</p>
                </div>
            @endforelse
        </div>
    </div>
@endsection
