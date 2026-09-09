@extends('layouts.app')

@section('title', 'Gestión de Usuarios - SGA')

@section('content')
    <div
        class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-[#0f172a] border border-slate-800/60 p-4 rounded-xl shadow-sm">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-sky-500/10 border border-sky-500/20 rounded-xl flex items-center justify-center">
                <i class="fa-solid fa-users text-sky-400 text-sm"></i>
            </div>
            <div>
                <h1 class="text-xl font-bold text-white">Gestión de Usuarios</h1>
                <p class="text-slate-400 text-xs mt-0.5">Listado de todos los usuarios registrados en el sistema.</p>
            </div>
        </div>

        <a href="{{ route('usuarios.create') }}"
            class="bg-sky-500 hover:bg-sky-600 transition text-slate-900 px-4 py-2.5 rounded-lg font-bold text-xs uppercase tracking-wider flex items-center justify-center gap-2 shadow-md shadow-sky-500/10 self-start sm:self-auto whitespace-nowrap">
            <i class="fa-solid fa-plus"></i> Nuevo Usuario
        </a>
    </div>

    @if (session('success'))
        <div
            class="bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 px-4 py-3 rounded-xl mb-6 flex items-center gap-2.5 text-sm shadow-sm">
            <i class="fa-solid fa-circle-check text-base"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if (session('error'))
        <div
            class="bg-rose-500/10 border border-rose-500/20 text-rose-400 px-4 py-3 rounded-xl mb-6 flex items-center gap-2.5 text-sm shadow-sm">
            <i class="fa-solid fa-triangle-exclamation text-base"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <div class="bg-[#1e293b] rounded-xl border border-slate-800 overflow-hidden shadow-xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="bg-slate-900/50 border-b border-slate-800 text-slate-400">
                        <th class="px-4 py-3.5 font-semibold text-xs uppercase tracking-wider">ID</th>
                        <th class="px-4 py-3.5 font-semibold text-xs uppercase tracking-wider">Usuario</th>
                        <th class="px-4 py-3.5 font-semibold text-xs uppercase tracking-wider">Rol</th>
                        <th class="px-4 py-3.5 font-semibold text-xs uppercase tracking-wider">Estado</th>
                        <th class="px-4 py-3.5 font-semibold text-xs uppercase tracking-wider text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 text-slate-300">
                    @forelse($usuarios as $usuario)
                        <tr class="hover:bg-slate-800/30 transition-colors">
                            <td class="px-4 py-3.5 text-slate-400 font-mono text-xs">{{ $usuario->id_usuario }}</td>
                            <td class="px-4 py-3.5 font-medium text-white">{{ $usuario->username }}</td>
                            <td class="px-4 py-3.5">
                                @if ($usuario->rol)
                                    <span
                                        class="text-xs px-2.5 py-0.5 rounded-full font-medium border
                                    @if ($usuario->rol->nombre_rol === 'admin') bg-sky-500/10 text-sky-400 border-sky-500/20
                                    @else bg-emerald-500/10 text-emerald-400 border-emerald-500/20 @endif">
                                        {{ ucfirst($usuario->rol->nombre_rol) }}
                                    </span>
                                @else
                                    <span class="text-xs text-slate-500">Sin rol</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5">
                                @if ($usuario->estado_activo)
                                    <span
                                        class="text-xs bg-emerald-500/10 text-emerald-400 px-2.5 py-0.5 rounded-full border border-emerald-500/20 font-medium inline-flex items-center gap-1.5">
                                        <i class="fa-solid fa-circle text-[6px]"></i> Activo
                                    </span>
                                @else
                                    <span
                                        class="text-xs bg-rose-500/10 text-rose-400 px-2.5 py-0.5 rounded-full border border-rose-500/20 font-medium inline-flex items-center gap-1.5">
                                        <i class="fa-solid fa-circle text-[6px]"></i> Inactivo
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-right space-x-1.5 whitespace-nowrap">
                                <a href="{{ route('usuarios.edit', $usuario->id_usuario) }}"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 bg-slate-800 hover:bg-sky-500/20 text-sky-400 rounded-lg text-xs font-medium border border-slate-700/60 transition">
                                    <i class="fa-solid fa-pen text-[10px]"></i> Editar
                                </a>

                                @if ($usuario->id_usuario !== auth()->id())
                                    <form action="{{ route('usuarios.destroy', $usuario->id_usuario) }}" method="POST"
                                        class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            onclick="return confirm('¿Estás seguro de deshabilitar este usuario?')"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 bg-slate-800 hover:bg-rose-500/20 text-rose-400 rounded-lg text-xs font-medium border border-slate-700/60 transition cursor-pointer">
                                            <i class="fa-solid fa-ban text-[10px]"></i> Deshabilitar
                                        </button>
                                    </form>
                                @else
                                    <span
                                        class="inline-flex items-center gap-1 px-2.5 py-1 bg-slate-800/40 text-slate-500 rounded-lg text-xs font-medium border border-slate-800/80 cursor-not-allowed">
                                        <i class="fa-solid fa-lock text-[10px]"></i> Cuenta actual
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-12 text-center text-slate-500">
                                <i class="fa-solid fa-users-slash text-3xl mb-3 block text-slate-600"></i>
                                <span class="text-sm font-medium">No hay usuarios registrados en el sistema.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($usuarios->hasPages())
            <div class="px-4 py-3 bg-slate-900/20 border-t border-slate-800">
                {{ $usuarios->links() }}
            </div>
        @endif
    </div>
@endsection
