<div class="bg-[#0f172a] border-b border-slate-800 px-6 py-4">
    <div class="flex items-center justify-between flex-wrap gap-4">

        <div class="flex items-center gap-6 flex-wrap">
            <a href="{{ url('/') }}" class="flex items-center gap-3 group">
                <div
                    class="w-8 h-8 bg-sky-500 rounded-lg flex items-center justify-center group-hover:bg-sky-400 transition">
                    <i class="fa-solid fa-car text-slate-900 text-sm"></i>
                </div>
                <span class="text-xl font-bold text-white tracking-tight">Taller<span
                        class="text-sky-400">SGA</span></span>
            </a>

            <nav class="flex items-center gap-1 sm:gap-2 text-sm font-medium text-slate-400">
                <a href="{{ route('vehiculos.index') }}"
                    class="px-3 py-1.5 rounded-lg hover:text-white hover:bg-slate-800/50 transition flex items-center gap-1.5 {{ request()->routeIs('vehiculos.*') ? 'text-sky-400 bg-slate-800/40' : '' }}">
                    <i class="fa-solid fa-car-side text-xs"></i> Vehículos
                </a>
                <a href="{{ route('mantenimientos.index') }}"
                    class="px-3 py-1.5 rounded-lg hover:text-white hover:bg-slate-800/50 transition flex items-center gap-1.5 {{ request()->routeIs('mantenimientos.*') ? 'text-sky-400 bg-slate-800/40' : '' }}">
                    <i class="fa-solid fa-wrench text-xs"></i> Mantenimientos
                </a>
                <a href="{{ route('repuestos.index') }}"
                    class="px-3 py-1.5 rounded-lg hover:text-white hover:bg-slate-800/50 transition flex items-center gap-1.5 {{ request()->routeIs('repuestos.*') ? 'text-sky-400 bg-slate-800/40' : '' }}">
                    <i class="fa-solid fa-box text-xs"></i> Repuestos
                </a>
                <a href="{{ route('notificaciones.index') }}"
                    class="px-3 py-1.5 rounded-lg hover:text-white hover:bg-slate-800/50 transition flex items-center gap-1.5 {{ request()->routeIs('notificaciones.*') ? 'text-sky-400 bg-slate-800/40' : '' }}">
                    <i class="fa-solid fa-bell text-xs"></i> Alertas
                </a>
                <a href="{{ route('historial-vehiculo.index') }}"
                    class="px-3 py-1.5 rounded-lg hover:text-white hover:bg-slate-800/50 transition flex items-center gap-1.5 {{ request()->routeIs('historial-vehiculo.*') ? 'text-sky-400 bg-slate-800/40' : '' }}">
                    <i class="fa-solid fa-clock-rotate-left text-xs"></i> Historial
                </a>
                <a href="{{ route('reportes.placa') }}"
                    class="px-3 py-1.5 rounded-lg hover:text-white hover:bg-slate-800/50 transition flex items-center gap-1.5 {{ request()->routeIs('reportes.*') ? 'text-sky-400 bg-slate-800/40' : '' }}">
                    <i class="fa-solid fa-file-invoice text-xs"></i> Reportes
                </a>

                @if (Auth::check() && Auth::user()->id_rol === 1)
                    <div class="h-5 w-px bg-slate-800 mx-2 hidden md:block"></div>

                    <a href="{{ route('usuarios.index') }}"
                        class="px-3 py-1.5 rounded-lg hover:text-amber-400 hover:bg-slate-800/50 transition flex items-center gap-1.5 {{ request()->routeIs('usuarios.*') ? 'text-amber-400 bg-slate-800/40' : '' }}">
                        <i class="fa-solid fa-users text-xs"></i> Usuarios
                    </a>
                    <a href="{{ route('roles-permisos.index') }}"
                        class="px-3 py-1.5 rounded-lg hover:text-amber-400 hover:bg-slate-800/50 transition flex items-center gap-1.5 {{ request()->routeIs('roles-permisos.*') ? 'text-amber-400 bg-slate-800/40' : '' }}">
                        <i class="fa-solid fa-shield-halved text-xs"></i> Permisos
                    </a>
                @endif
            </nav>
        </div>

        <div class="flex items-center gap-4">
            <div class="text-right text-xs">
                <p class="font-medium text-white">{{ Auth::user()->username ?? 'Usuario' }}</p>
                <p class="text-slate-400">
                    {{ Auth::user()->id_rol === 1 ? 'Administrador' : 'Mecánico' }}
                </p>
            </div>

            <div class="w-px h-6 bg-slate-800"></div>

            <form method="POST" action="{{ route('logout') }}" class="inline">
                @csrf
                <button type="submit"
                    class="text-slate-400 hover:text-rose-400 transition text-sm flex items-center gap-1.5 font-medium">
                    <i class="fa-solid fa-sign-out-alt"></i> <span class="hidden sm:inline">Salir</span>
                </button>
            </form>
        </div>

    </div>
</div>
