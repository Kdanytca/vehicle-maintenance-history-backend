<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGA - Sistema de Gestión Automotriz</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- Tailwind CSS desde CDN para un entorno moderno -->
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <!-- Iconos de FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #0f172a; }
        ::-webkit-scrollbar-thumb { background: #334155; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #475569; }
    </style>
</head>
<body class="bg-[#020617] text-slate-100 min-h-screen flex flex-col antialiased">

    <!-- PANTALLA DE LOGIN (SPLIT SCREEN) -->
    <div class="flex min-h-screen w-full transition-all duration-500">
        <!-- Lado Izquierdo: Branding y Visual -->
        <div class="hidden lg:flex lg:w-1/2 bg-slate-900 justify-center items-center relative overflow-hidden p-12 border-r border-slate-800">
            <div class="absolute -top-40 -left-40 w-96 h-96 bg-sky-500/10 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-40 -right-40 w-96 h-96 bg-indigo-500/10 rounded-full blur-3xl"></div>
            
            <div class="max-w-md space-y-8 relative z-10">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-sky-500 rounded-xl flex items-center justify-center text-slate-900 font-bold text-xl shadow-lg shadow-sky-500/20">
                        <i class="fa-solid fa-wrench"></i>
                    </div>
                    <div>
                        <span class="font-bold text-white text-lg tracking-wide block">SGA SYSTEM</span>
                        <span class="text-[10px] text-sky-400 font-semibold tracking-wider uppercase block">Gestión e Ingeniería Automotriz</span>
                    </div>
                </div>

                <div class="space-y-4">
                    <h1 class="text-4xl font-extrabold text-white leading-tight tracking-tight">La evolución en el control de tu taller.</h1>
                    <p class="text-slate-400 text-sm leading-relaxed">
                        Optimiza el registro de flotas, la trazabilidad de los mantenimientos, el control de repuestos vinculados a facturación y la comunicación directa con tus clientes en una sola plataforma robusta.
                    </p>
                </div>

                <div class="space-y-3 pt-4 border-t border-slate-800">
                    <div class="flex items-center gap-3 text-xs text-slate-300">
                        <i class="fa-solid fa-circle-check text-emerald-400 text-sm"></i>
                        <span>Validación estricta de Placas Únicas (Sprint 1)</span>
                    </div>
                    <div class="flex items-center gap-3 text-xs text-slate-300">
                        <i class="fa-solid fa-circle-check text-emerald-400 text-sm"></i>
                        <span>Anulación lógica de órdenes para auditoría (Sprint 2)</span>
                    </div>
                    <div class="flex items-center gap-3 text-xs text-slate-300">
                        <i class="fa-solid fa-circle-check text-emerald-400 text-sm"></i>
                        <span>Gestión logística y carga de facturas PDF (Sprint 3)</span>
                    </div>
                </div>

                <div class="text-[11px] text-slate-500">
                    Propuesta de Diseño de Sistemas I - FIAUES 2026. Todos los derechos reservados.
                </div>
            </div>
        </div>

        <!-- Lado Derecho: Formulario de Login -->
        <div class="w-full lg:w-1/2 flex justify-center items-center p-8 sm:p-12">
            <div class="w-full max-w-md space-y-8">
                <div class="space-y-2">
                    <div class="lg:hidden flex items-center gap-2.5 mb-6">
                        <div class="w-8 h-8 bg-sky-500 rounded-lg flex items-center justify-center text-slate-900 font-bold text-sm">
                            <i class="fa-solid fa-wrench"></i>
                        </div>
                        <span class="font-bold text-white tracking-wide text-sm">SGA SYSTEM</span>
                    </div>
                    <h2 class="text-3xl font-bold text-white tracking-tight">Iniciar Sesión</h2>
                    <p class="text-sm text-slate-400">Por favor, introduce tus credenciales para ingresar.</p>
                </div>

                <!-- Alerta de Feedback de Error -->
                @if($errors->any())
                <div class="p-3.5 bg-rose-500/10 border border-rose-500/20 text-rose-400 text-xs rounded-lg flex items-start gap-2.5">
                    <i class="fa-solid fa-triangle-exclamation mt-0.5 shrink-0"></i>
                    <span>{{ $errors->first('login') }}</span>
                </div>
                @endif

                <!-- Formulario -->
                <form method="POST" action="{{ route('login') }}" class="space-y-5">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 mb-1.5 uppercase tracking-wider">Nombre de Usuario</label>
                        <div class="relative">
                            <i class="fa-solid fa-user absolute left-3.5 top-3.5 text-slate-500 text-sm"></i>
                            <input type="text" name="username" id="username" value="{{ old('username') }}" required 
                                   class="w-full bg-slate-900 border border-slate-800 rounded-lg pl-10 pr-4 py-3 text-sm text-slate-200 placeholder-slate-600 focus:outline-none focus:border-sky-500 transition-colors">
                        </div>
                    </div>

                    <div>
                        <div class="flex justify-between items-center mb-1.5">
                            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">Contraseña</label>
                            <a href="#" class="text-xs text-sky-400 hover:underline">¿La olvidó?</a>
                        </div>
                        <div class="relative">
                            <i class="fa-solid fa-lock absolute left-3.5 top-3.5 text-slate-500 text-sm"></i>
                            <input type="password" name="password" id="password" required 
                                   class="w-full bg-slate-900 border border-slate-800 rounded-lg pl-10 pr-10 py-3 text-sm text-slate-200 placeholder-slate-600 focus:outline-none focus:border-sky-500 transition-colors">
                            <button type="button" onclick="togglePasswordVisibility()" class="absolute right-3.5 top-3.5 text-slate-500 hover:text-slate-300">
                                <i class="fa-solid fa-eye" id="password-eye-icon"></i>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" name="remember" id="remember" class="accent-sky-500 rounded border-slate-800 bg-slate-950 text-sky-500 focus:ring-0 w-4 h-4 cursor-pointer">
                        <label for="remember" class="ml-2 text-xs text-slate-400 cursor-pointer select-none">Mantener sesión iniciada en este equipo</label>
                    </div>

                    <button type="submit" class="w-full bg-sky-500 hover:bg-sky-600 active:scale-[0.98] text-slate-900 font-bold py-3 px-4 rounded-lg text-sm transition-all shadow-lg shadow-sky-500/15">
                        Ingresar al Portal <i class="fa-solid fa-right-to-bracket ml-1.5 text-xs"></i>
                    </button>
                </form>

                <!-- Credenciales Rápidas para Demostración -->
                <div class="p-4 bg-slate-900/50 border border-slate-800/80 rounded-xl space-y-2">
                    <p class="text-xs font-bold text-slate-300 uppercase tracking-wider flex items-center gap-1.5">
                        <i class="fa-solid fa-circle-info text-sky-400"></i> Credenciales de demostración:
                    </p>
                    <div class="grid grid-cols-2 gap-3 text-xs text-slate-400">
                        <div class="p-2 bg-slate-950/40 rounded border border-slate-800/40 cursor-pointer hover:border-sky-500/30 transition-colors" onclick="autoFill('admin', '12345678')">
                            <span class="block font-bold text-sky-400">Administrador:</span>
                            <span class="block font-mono text-[10px]">User: admin</span>
                            <span class="block font-mono text-[10px]">Pass: 12345678</span>
                        </div>
                        <div class="p-2 bg-slate-950/40 rounded border border-slate-800/40 cursor-pointer hover:border-emerald-500/30 transition-colors" onclick="autoFill('mecanico', '12345678')">
                            <span class="block font-bold text-emerald-400">Mecánico:</span>
                            <span class="block font-mono text-[10px]">User: mecanico</span>
                            <span class="block font-mono text-[10px]">Pass: 12345678</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('password-eye-icon');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        }

        function autoFill(username, password) {
            document.getElementById('username').value = username;
            document.getElementById('password').value = password;
        }
    </script>
</body>
</html>