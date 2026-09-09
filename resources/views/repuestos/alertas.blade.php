@extends('layouts.app')

@section('title', 'Centro de Alertas - SGA')

@section('content')

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/css/iziToast.min.css">

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-white tracking-tight">Módulo de Alertas Operativas</h1>
        <p class="text-sm text-slate-400">Gestión de comunicaciones, despacho manual y trazabilidad de notificaciones.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <div class="bg-[#1e293b] p-5 rounded-xl border border-slate-800 space-y-4 shadow-sm h-fit">
            <h3 class="font-bold text-slate-200 text-sm tracking-wide uppercase border-b border-slate-800 pb-2">
                <i class="fa-solid fa-pen-nib text-sky-400 mr-1.5"></i> Configurar Correo Manual
            </h3>
            <div class="space-y-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1">Destinatario (Cliente)</label>
                    <input type="email" id="correo-cliente" value="juan.mendoza@email.com"
                        class="w-full bg-slate-900 border border-slate-800 rounded-lg p-2 text-xs text-slate-200 focus:outline-none focus:border-sky-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1">Asunto predefinido</label>
                    <input type="text" id="asunto-cliente" value="Aviso de Revisión Preventiva - SGA"
                        class="w-full bg-slate-900 border border-slate-800 rounded-lg p-2 text-xs text-slate-200 focus:outline-none focus:border-sky-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1">Mensaje o Cuerpo de Plantilla</label>
                    <textarea id="mensaje-cliente" rows="5"
                        class="w-full bg-slate-900 border border-slate-800 rounded-lg p-2 text-xs text-slate-200 focus:outline-none focus:border-sky-500 font-sans">Estimado cliente, le saludamos del taller mecánico para informarle que su vehículo está listo para retiro...</textarea>
                </div>
            </div>
            <button onclick="enviarNotificacionMantenimiento()"
                class="w-full bg-sky-500 hover:bg-sky-600 text-slate-900 py-2 rounded-lg font-bold text-xs transition-all cursor-pointer">
                <i class="fa-solid fa-paper-plane mr-1"></i> Despachar Correo Manual
            </button>
        </div>

        <div class="bg-[#1e293b] p-5 rounded-xl border border-slate-800 lg:col-span-2 space-y-3 shadow-sm h-fit">
            <h3 class="font-bold text-slate-200 text-sm tracking-wide uppercase border-b border-slate-800 pb-2">
                <i class="fa-solid fa-clock-rotate-left text-amber-400 mr-1.5"></i> Registro de Trazabilidad de
                Notificaciones
            </h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-900/40 text-slate-400 border-b border-slate-800">
                            <th class="p-3">Fecha / Hora</th>
                            <th class="p-3">Tipo</th>
                            <th class="p-3">Destinatario</th>
                            <th class="p-3">Estado de Envío</th>
                        </tr>
                    </thead>
                    <tbody id="table-notificaciones-body" class="divide-y divide-slate-800/50 text-slate-300">
                        @foreach ($notificaciones as $noti)
                            <tr class="hover:bg-slate-800/20 transition-colors">
                                <td class="p-3 font-mono">{{ $noti->fecha_envio ?? $noti->created_at }}</td>
                                <td class="p-3">
                                    <span
                                        class="bg-purple-500/10 text-purple-400 px-1.5 py-0.5 rounded border border-purple-500/20 font-medium font-mono text-[10px]">
                                        {{ $noti->tipo_envio }}
                                    </span>
                                </td>
                                <td class="p-3">{{ $noti->destinatario }}</td>
                                <td class="p-3 text-emerald-400 font-semibold"><i
                                        class="fa-solid fa-circle-check mr-1 text-[10px]"></i>Exitoso</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/js/iziToast.min.js"></script>
    <script>
        iziToast.settings({
            timeout: 4000,
            position: 'topRight',
            transitionIn: 'fadeInDown',
            transitionOut: 'fadeOutUp'
        });

        function enviarNotificacionMantenimiento() {
            let payload = {
                destinatario: document.getElementById('correo-cliente').value,
                asunto: document.getElementById('asunto-cliente').value,
                mensaje: document.getElementById('mensaje-cliente').value,
                _token: '{{ csrf_token() }}'
            };

            fetch("{{ route('notificaciones.enviar') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        iziToast.success({
                            title: 'Enviado',
                            message: 'Notificación registrada e iziToast activo.',
                            icon: 'fa-solid fa-paper-plane'
                        });

                        let tbodyNoti = document.getElementById('table-notificaciones-body');
                        let filaNoti = `<tr class="bg-sky-500/5 transition-colors">
                            <td class="p-3 font-mono">${data.notificacion.fecha}</td>
                            <td class="p-3"><span class="bg-purple-500/10 text-purple-400 px-1.5 py-0.5 rounded border border-purple-500/20 font-medium text-[10px]">AUTOMÁTICO</span></td>
                            <td class="p-3">${data.notificacion.destinatario}</td>
                            <td class="p-3 text-emerald-400 font-semibold"><i class="fa-solid fa-circle-check mr-1 text-[10px]"></i>Exitoso</td>
                        </tr>`;
                        tbodyNoti.insertAdjacentHTML('afterbegin', filaNoti);
                    } else {
                        iziToast.error({
                            title: 'Error',
                            message: data.message
                        });
                    }
                }).catch(() => iziToast.error({
                    title: 'Error',
                    message: 'Fallo al despachar alerta.'
                }));
        }
    </script>

@endsection
