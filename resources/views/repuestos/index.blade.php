@extends('layouts.app')

@section('title', 'Gestión de Repuestos - SGA')

@section('content')

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/css/iziToast.min.css">

    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-white tracking-tight">Inventario de Repuestos y Reposición</h1>
                <p class="text-sm text-slate-400">Registro de Repuestos a través de facturas de compra.</p>
            </div>
            <button onclick="openModal('modal-repuesto')"
                class="bg-sky-500 hover:bg-sky-600 transition-all text-slate-900 px-4 py-2 rounded-lg font-semibold text-sm flex items-center gap-2 shadow-md cursor-pointer">
                <i class="fa-solid fa-file-arrow-up text-xs"></i> Cargar Factura de Compra
            </button>
        </div>

        <div class="bg-[#1e293b] rounded-xl border border-slate-800 overflow-hidden shadow-sm">
            <table class="w-full text-left text-sm border-collapse">
                <thead>
                    <tr
                        class="bg-slate-900/50 border-b border-slate-800 text-slate-400 text-xs font-semibold uppercase tracking-wider">
                        <th class="p-4">Código Pieza</th>
                        <th class="p-4">Nombre / Descripción</th>
                        <th class="p-4">Precio Unitario de Costo</th>
                        <th class="p-4">Factura Asociada (PDF)</th>
                    </tr>
                </thead>
                <tbody id="table-repuestos-body" class="divide-y divide-slate-800/60 text-slate-300">
                    @if ($repuestos->isEmpty())
                        <tr id="fila-vacia">
                            <td colspan="4" class="p-8 text-center text-slate-500 text-xs uppercase tracking-wider">No
                                hay repuestos registrados en la base de datos.</td>
                        </tr>
                    @else
                        @foreach ($repuestos as $repuesto)
                            <tr class="hover:bg-slate-800/30 transition-colors">
                                <td class="p-4 font-mono font-medium text-slate-400">{{ $repuesto->codigo_pieza ?? 'S/N' }}
                                </td>
                                <td class="p-4 text-white font-medium">{{ $repuesto->nombre_pieza }}</td>
                                <td class="p-4 font-mono">${{ number_format($repuesto->costo_unitario, 2) }}</td>
                                <td class="p-4 text-sky-400 cursor-pointer hover:underline">
                                    @if ($repuesto->factura)
                                        <a href="{{ asset('storage/' . $repuesto->factura->ruta_pdf_almacenamiento) }}"
                                            target="_blank">
                                            <i
                                                class="fa-solid fa-paperclip mr-1 text-xs"></i>{{ $repuesto->factura->numero_factura }}.pdf
                                        </a>
                                    @else
                                        <span class="text-slate-500">Sin documento</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    <div id="modal-repuesto"
        class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center hidden opacity-0 transition-opacity duration-300">
        <div
            class="bg-[#1e293b] border border-slate-800 w-full max-w-md rounded-xl overflow-hidden shadow-2xl transform scale-95 transition-transform duration-300">
            <div class="px-6 py-4 bg-slate-900 border-b border-slate-800 flex justify-between items-center">
                <h3 class="font-bold text-white text-base">Cargar Repuestos vía Factura PDF</h3>
                <button onclick="closeModal('modal-repuesto')" class="text-slate-400 hover:text-white text-sm">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1">Vehículo a Vincular *</label>
                    <select id="input-vehiculo"
                        class="w-full bg-slate-900 border border-slate-800 rounded-lg p-2 text-xs text-slate-200 focus:outline-none focus:border-sky-500 cursor-pointer">
                        <option value="">Seleccione el vehículo...</option>
                        @foreach ($vehiculos as $v)
                            <option value="{{ $v->id_vehiculo }}">{{ $v->placa }} - {{ $v->marca }}
                                {{ $v->modelo }}</option>
                        @endforeach
                    </select>
                </div>

                <div id="dropzone-contenedor"
                    class="border-2 border-dashed border-slate-700 hover:border-sky-500/50 rounded-xl p-6 text-center cursor-pointer transition-all bg-slate-900/30 relative">
                    <input type="file" id="comprobante_file" accept=".pdf"
                        class="absolute inset-0 opacity-0 cursor-pointer" onchange="actualizarNombreArchivo()">
                    <i id="icono-archivo" class="fa-solid fa-file-pdf text-3xl text-slate-500 mb-2 transition-colors"></i>
                    <p id="texto-archivo" class="text-xs font-medium text-slate-300">Selecciona o arrastra el archivo legal
                        (.pdf)</p>
                    <p id="subtexto-archivo" class="text-[10px] text-slate-500 mt-1">Tamaño máximo recomendado: 5MB</p>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 mb-1">Cantidad Recibida *</label>
                        <input type="number" id="input-cantidad" placeholder="0"
                            class="w-full bg-slate-900 border border-slate-800 rounded-lg p-2 text-xs text-slate-200 focus:outline-none focus:border-sky-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 mb-1">Costo Unitario ($) *</label>
                        <input type="text" id="input-costo" placeholder="0.00"
                            class="w-full bg-slate-900 border border-slate-800 rounded-lg p-2 text-xs text-slate-200 focus:outline-none focus:border-sky-500">
                    </div>
                </div>
            </div>

            <div class="px-6 py-4 bg-slate-900 border-t border-slate-800 flex justify-end gap-3">
                <button onclick="closeModal('modal-repuesto')"
                    class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold rounded-lg">Cancelar</button>
                <button onclick="procesarFacturaPdf()"
                    class="px-4 py-2 bg-sky-500 hover:bg-sky-600 text-slate-900 text-xs font-bold rounded-lg cursor-pointer">Vincular
                    al Stock</button>
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

        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.classList.remove('opacity-0');
                modal.querySelector('div > div').classList.remove('scale-95');
            }, 10);
        }

        // 🌟 RESETEA LOS INPUTS Y LA INTERFAZ VISUAL AL CERRAR
        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.classList.add('opacity-0');
            modal.querySelector('div > div').classList.add('scale-95');
            setTimeout(() => {
                modal.classList.add('hidden');

                // Limpieza de datos internos
                document.getElementById('comprobante_file').value = "";
                document.getElementById('input-cantidad').value = "";
                document.getElementById('input-costo').value = "";
                document.getElementById('input-vehiculo').value = "";

                // Forzar el reseteo visual del cuadro del archivo
                actualizarNombreArchivo();
            }, 300);
        }

        // 🌟 FUNCIÓN NUEVA: CAMBIA EL DISEÑO DEL DROPZONE AL CARGAR UN PDF
        function actualizarNombreArchivo() {
            const input = document.getElementById('comprobante_file');
            const contenedor = document.getElementById('dropzone-contenedor');
            const texto = document.getElementById('texto-archivo');
            const subtexto = document.getElementById('subtexto-archivo');
            const icono = document.getElementById('icono-archivo');

            if (input.files && input.files.length > 0) {
                const nombreArchivo = input.files[0].name;

                texto.innerText = "Archivo listo: " + nombreArchivo;
                texto.classList.remove('text-slate-300');
                texto.classList.add('text-sky-400', 'font-bold');

                contenedor.classList.remove('border-slate-700');
                contenedor.classList.add('border-sky-500', 'bg-sky-500/5');
                icono.classList.remove('text-slate-500');
                icono.classList.add('text-sky-400');

                subtexto.innerText = "¡Presiona Vincular al Stock para procesar!";
            } else {
                texto.innerText = "Selecciona o arrastra el archivo legal (.pdf)";
                texto.classList.remove('text-sky-400', 'font-bold');
                texto.classList.add('text-slate-300');

                contenedor.classList.remove('border-sky-500', 'bg-sky-500/5');
                contenedor.classList.add('border-slate-700');
                icono.classList.remove('text-sky-400');
                icono.classList.add('text-slate-500');

                subtexto.innerText = "Tamaño máximo recomendado: 5MB";
            }
        }

        function procesarFacturaPdf() {
            let pdfFile = document.getElementById('comprobante_file');
            let cantidad = document.getElementById('input-cantidad').value;
            let costo = document.getElementById('input-costo').value;
            let idVehiculo = document.getElementById('input-vehiculo').value;

            if (!idVehiculo || !pdfFile.files[0] || !cantidad || !costo) {
                iziToast.warning({
                    title: 'Atención',
                    message: 'Por favor complete todos los campos, incluyendo el vehículo.'
                });
                return;
            }

            iziToast.info({
                title: 'Conectando',
                message: 'Sincronizando con base de datos MySQL...',
                timeout: 1200
            });

            let form = new FormData();
            form.append('comprobante_pdf', pdfFile.files[0]);
            form.append('cantidad_recibida', cantidad);
            form.append('costo_unitario', costo);
            form.append('id_vehiculo', idVehiculo);
            form.append('_token', '{{ csrf_token() }}');

            fetch("{{ route('repuestos.storePdf') }}", {
                    method: 'POST',
                    body: form
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        closeModal('modal-repuesto');
                        iziToast.success({
                            title: 'Éxito',
                            message: data.message
                        });

                        let tbody = document.getElementById('table-repuestos-body');

                        // Quitar el aviso de "No hay repuestos registrados" si es la primera fila
                        let filaVacia = document.getElementById('fila-vacia');
                        if (filaVacia) {
                            filaVacia.remove();
                        }

                        let fila = `<tr class="hover:bg-slate-800/30 transition-colors bg-sky-500/5">
                            <td class="p-4 font-mono text-slate-400">${data.data.codigo_pieza}</td>
                            <td class="p-4 text-white font-medium">${data.data.nombre_pieza}</td>
                            <td class="p-4 font-mono">${data.data.costo_unitario}</td>
                            <td class="p-4 text-sky-400 cursor-pointer hover:underline">
                                <a href="${data.data.ruta_pdf}" target="_blank"><i class="fa-solid fa-paperclip mr-1 text-xs"></i>${data.data.factura}.pdf</a>
                            </td>
                        </tr>`;
                        tbody.insertAdjacentHTML('afterbegin', fila);
                    } else {
                        iziToast.error({
                            title: 'Error',
                            message: data.message
                        });
                    }
                }).catch(error => {
                    console.error("Error detallado del servidor:", error);
                    iziToast.error({
                        title: 'Error',
                        message: 'Fallo de conexión o error interno en el servidor.'
                    });
                });
        }
    </script>
@endsection
